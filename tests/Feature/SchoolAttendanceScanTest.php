<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTeacherOnDuty;
use App\Http\Middleware\EnsureUserHasRole;
use App\Models\AuditLog;
use App\Models\SchoolAttendance;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherDutySchedule;
use App\Models\User;
use App\Services\AttendanceCorrectionService;
use App\Services\SchoolAttendanceScanService;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * ADJUSTMENT D — Student School Attendance Scanner.
 *
 * Matriks pengujian (PRD 01 §7.4 GUR-PIK-001..007 & §22 AC-SCAN-01..06,
 * ADDENDUM §1–§7 & §11–§16, PRD 02 §4b/§5/§6, PRD 04 §3.2.4/§5.1/§5.3/§8/
 * §9.1/§9.2/§10/§16.5, PRD 05 §5.10 & §5.10b, ADR 18 & ADR 20):
 *
 *  A. Gerbang autentikasi + role (guest, admin, supervisor)
 *  B. 403 + teks persis "Anda hari ini bukan Guru Piket."
 *  C. Jadwal dicabut di tengah sesi -> 403 teks "...telah berubah..."
 *  D. Barcode: dikenal+AKTIF / tidak dikenal / non-AKTIF / mode wajib eksplisit
 *  E. MASUK tersimpan + duplikat MASUK ditolak dengan jam tercatat
 *  F. PULANG tersimpan + [FINAL — KEPUTUSAN A1] + duplikat PULANG
 *  G. Pemalsuan identitas/tanggal/waktu dari klien diabaikan
 *  H. CSRF: rute dalam pipeline CSRF, form membawa token, dan token salah
 *     benar-benar ditolak oleh middleware (bukan sekadar diasumsikan)
 *  I. Balapan duplikat -> ditahan UNIQUE, tanpa HTTP 500, rollback, tanpa audit palsu
 *  J. Monitoring + koreksi Admin/TU (alasan >= 10, audit SCHOOL_ATT_CORRECT,
 *     Supervisor read-only, tanggal record tidak dapat dipindah)
 *  K. Histori terkunci, header keamanan, dan tanpa penyimpanan di klien
 *
 * CATATAN: tidak ada tes kamera/getUserMedia sungguhan di sini. html5-qrcode
 * diverifikasi lewat build Vite (bundle terbit) + keberadaan elemen reader dan
 * kontrolnya, bukan lewat simulasi izin kamera browser.
 */
class SchoolAttendanceScanTest extends TestCase
{
    use RefreshDatabase;

    /** Waktu server dibekukan agar seluruh assertion jam deterministik. */
    private const NOW = '2026-09-28 07:15:00';

    private const SERVER_DATE = '2026-09-28';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(self::NOW);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function makeUser(string $role, string $username): User
    {
        return User::create([
            'username' => $username,
            'email' => "{$username}@sekolah.demo",
            'password' => 'rahasia123',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    /** @return array{0: User, 1: Teacher} */
    private function makeTeacherUser(string $username): array
    {
        $user = $this->makeUser(User::ROLE_TEACHER, $username);

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'nip' => '1990' . str_pad((string) (abs(crc32($username)) % 100000000), 8, '0', STR_PAD_LEFT),
            'full_name' => 'Guru ' . ucwords(str_replace('_', ' ', $username)),
            'gender' => 'L',
        ]);

        return [$user->fresh(), $teacher];
    }

    private function makeStudent(string $nis, string $status = 'AKTIF'): Student
    {
        return Student::create([
            'nis' => $nis,
            'nisn' => '0098' . $nis,
            'barcode_code' => $nis,
            'full_name' => 'Siswa ' . $nis,
            'gender' => 'L',
            'status' => $status,
        ]);
    }

    private function makeSchedule(Teacher $teacher, string $date, User $actor): TeacherDutySchedule
    {
        return TeacherDutySchedule::create([
            'schedule_date' => $date,
            'teacher_id' => $teacher->id,
            'created_by_user_id' => $actor->id,
            'notes' => 'Piket gerbang',
        ]);
    }

    /**
     * Guru dengan jadwal piket SAH pada tanggal server berjalan.
     *
     * @return array{0: User, 1: Teacher, 2: TeacherDutySchedule}
     */
    private function onDutyTeacher(string $username): array
    {
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_tu_' . $username);
        [$teacherUser, $teacher] = $this->makeTeacherUser($username);

        return [$teacherUser, $teacher, $this->makeSchedule($teacher, self::SERVER_DATE, $admin)];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function scanPayload(string $barcode, string $mode, array $extra = []): array
    {
        return array_merge(['barcode' => $barcode, 'mode' => $mode], $extra);
    }

    /** Satu kali POST scan (referer diset agar redirect-back kembali ke scanner). */
    private function scan(User $as, string $barcode, string $mode, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($as)->post(
            route('guru.school-attendance-scanner.store'),
            $this->scanPayload($barcode, $mode, $extra),
            ['Referer' => route('guru.school-attendance-scanner.index')]
        );
    }

    /** @return array<int, string> */
    private function scannerViewFiles(): array
    {
        return glob(base_path('resources/views/guru/school-attendance-scanner/*.blade.php')) ?: [];
    }

    /** Buang komentar (JS + Blade/HTML) sebelum memeriksa pemakaian API. */
    private function stripComments(string $code): string
    {
        $code = preg_replace('#/\*.*?\*/#s', '', $code) ?? $code;
        $code = preg_replace('#//[^\n\r]*#', '', $code) ?? $code;
        $code = preg_replace('#\{\{--.*?--\}\}#s', '', $code) ?? $code;

        return preg_replace('#<!--.*?-->#s', '', $code) ?? $code;
    }

    // =================================================================
    // A. GERBANG AUTENTIKASI & ROLE
    // =================================================================

    public function test_guest_is_redirected_to_login_by_scanner(): void
    {
        $this->get(route('guru.school-attendance-scanner.index'))->assertRedirect(route('login'));
    }

    public function test_admin_and_supervisor_cannot_open_or_post_scanner(): void
    {
        // Scanner BUKAN kewenangan area mereka (PRD 01 §5.4) -> role:teacher 403.
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_scanner_forbidden');
        $supervisor = $this->makeUser(User::ROLE_SUPERVISOR, 'supervisor_scanner_forbidden');

        foreach ([$admin, $supervisor] as $user) {
            $this->actingAs($user)
                ->get(route('guru.school-attendance-scanner.index'))
                ->assertForbidden();

            $this->scan($user, '26270701', 'MASUK')->assertForbidden();

            $this->assertSame(
                0,
                SchoolAttendance::count(),
                'Tebakan POST dari role non-guru tidak boleh menghasilkan record.'
            );
            $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_SCHOOL_ATT_SCAN)->count());
        }
    }

    // =================================================================
    // B. GUR-PIK-001 / AC-SCAN-05 — guru tanpa jadwal piket
    // =================================================================

    public function test_teacher_without_duty_schedule_gets_locked_page_with_exact_text(): void
    {
        [$teacherUser] = $this->makeTeacherUser('guru_bukan_piket');

        $response = $this->actingAs($teacherUser)
            ->get(route('guru.school-attendance-scanner.index'));

        $response->assertStatus(403)
            ->assertSee(SchoolAttendanceScanService::MESSAGE_NOT_ON_DUTY, false)
            ->assertSee(self::SERVER_DATE);

        // Teks PERSIS dari PRD, dan tidak ada panel scan di halaman terkunci.
        $this->assertStringContainsString('Anda hari ini bukan Guru Piket.', $response->getContent());
        $this->assertStringNotContainsString('id="qr-reader"', $response->getContent());
        $this->assertStringNotContainsString('name="barcode"', $response->getContent());
    }

    public function test_teacher_without_duty_schedule_cannot_post_scan(): void
    {
        [$teacherUser] = $this->makeTeacherUser('guru_nyoba_scan');
        $student = $this->makeStudent('26270711');

        $this->scan($teacherUser, $student->barcode_code, 'MASUK')
            ->assertStatus(403)
            ->assertSee(SchoolAttendanceScanService::MESSAGE_NOT_ON_DUTY, false);

        $this->assertSame(0, SchoolAttendance::count(), 'Scan tanpa jadwal tidak menyimpan apa pun.');
        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_SCHOOL_ATT_SCAN)->count());
    }

    // =================================================================
    // C. ADDENDUM §12–§14 — jadwal dicabut di tengah sesi
    // =================================================================

    public function test_revoked_duty_schedule_mid_session_locks_scanner_with_changed_text(): void
    {
        [$teacherUser, $teacher, $schedule] = $this->onDutyTeacher('guru_dicabut');
        $student = $this->makeStudent('26270712');

        // Sesi terbuka saat guru masih bertugas.
        $this->actingAs($teacherUser)
            ->get(route('guru.school-attendance-scanner.index'))
            ->assertOk()
            ->assertSee($teacher->full_name);

        $this->assertSame(
            self::SERVER_DATE,
            session()->get(SchoolAttendanceScanService::SESSION_DUTY_KEY),
            'Penanda sesi mencatat tanggal server saat akses diberikan.'
        );

        // Admin mencabut jadwal (ADDENDUM §13).
        $schedule->delete();

        $response = $this->scan($teacherUser, $student->barcode_code, 'MASUK');

        $response->assertStatus(403)
            ->assertSee(SchoolAttendanceScanService::MESSAGE_DUTY_CHANGED, false);

        $this->assertStringContainsString(
            'Jadwal piket Anda telah berubah. Anda tidak lagi bertugas sebagai Guru Piket pada tanggal ini.',
            $response->getContent()
        );

        $this->assertSame(0, SchoolAttendance::count());
    }

    public function test_teacher_gets_access_when_scheduled_on_a_different_date(): void
    {
        // Jadwal untuk tanggal lain TIDAK memberi akses hari ini (tanggal server).
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_jadwal_besok');
        [$teacherUser, $teacher] = $this->makeTeacherUser('guru_besok_saja');
        $this->makeSchedule($teacher, '2026-09-29', $admin);

        $this->actingAs($teacherUser)
            ->get(route('guru.school-attendance-scanner.index'))
            ->assertForbidden()
            ->assertSee(SchoolAttendanceScanService::MESSAGE_NOT_ON_DUTY, false);
    }

    // =================================================================
    // D. RESOLUSI BARCODE & MODE
    // =================================================================

    public function test_valid_barcode_of_active_student_is_scanned(): void
    {
        [$teacherUser, $teacher] = $this->onDutyTeacher('guru_barcode_ok');
        $student = $this->makeStudent('26270713');

        $this->scan($teacherUser, $student->barcode_code, 'MASUK')
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $attendance = SchoolAttendance::whereDate('attendance_date', self::SERVER_DATE)->sole();

        $this->assertSame($student->id, (int) $attendance->student_id);
        $this->assertSame($teacher->id, (int) $attendance->check_in_by_teacher_id, 'Operator dari sesi login.');
        $this->assertSame('MASUK', $attendance->scan_mode_in);
        $this->assertSame(self::NOW, $attendance->check_in_time->format('Y-m-d H:i:s'), 'Waktu dari clock server.');

        // Pesan flash + nama siswa dirender ter-escape pada load berikutnya.
        $this->actingAs($teacherUser)
            ->get(route('guru.school-attendance-scanner.index'))
            ->assertOk()
            ->assertSee('MASUK tercatat untuk ' . $student->full_name . ' pukul 07:15.', false);

        $this->assertSame(1, AuditLog::where('action', AuditLog::ACTION_SCHOOL_ATT_SCAN)->count());
    }

    public function test_unknown_barcode_is_rejected(): void
    {
        [$teacherUser] = $this->onDutyTeacher('guru_barcode_asing');

        $this->scan($teacherUser, 'TIDAKADA99', 'MASUK')->assertSessionHasErrors('barcode');

        $this->assertSame(0, SchoolAttendance::count());
    }

    public function test_non_active_student_barcode_is_rejected(): void
    {
        [$teacherUser] = $this->onDutyTeacher('guru_barcode_nonaktif');
        $student = $this->makeStudent('26270714', 'MUTASI');

        $this->scan($teacherUser, $student->barcode_code, 'MASUK')->assertSessionHasErrors('barcode');

        $this->assertStringContainsString(
            'tidak berstatus aktif',
            (string) session('errors')->first('barcode')
        );
        $this->assertSame(0, SchoolAttendance::count(), 'Siswa non-AKTIF ditolak (fail closed).');
    }

    public function test_mode_must_be_explicit_and_valid(): void
    {
        // ADDENDUM §1 / ADR 18: tanpa auto-detect; nilai di luar enum ditolak.
        [$teacherUser] = $this->onDutyTeacher('guru_mode_kosong');
        $student = $this->makeStudent('26270715');

        $this->scan($teacherUser, $student->barcode_code, '')->assertSessionHasErrors('mode');
        $this->scan($teacherUser, $student->barcode_code, 'AUTO')->assertSessionHasErrors('mode');
        $this->scan($teacherUser, '', 'MASUK')->assertSessionHasErrors('barcode');

        $this->assertSame(0, SchoolAttendance::count());
    }

    public function test_overlong_barcode_is_rejected_before_lookup(): void
    {
        // Batas teknis kolom varchar(50); tidak ada kueri LIKE/partial.
        [$teacherUser] = $this->onDutyTeacher('guru_barcode_kepanjangan');
        $student = $this->makeStudent('26270716');

        $this->scan($teacherUser, $student->barcode_code . str_repeat('9', 60), 'MASUK')
            ->assertSessionHasErrors('barcode');

        // Pencocokan harus PERSIS: awalan barcode tidak diterima.
        $this->scan($teacherUser, substr($student->barcode_code, 0, 4), 'MASUK')
            ->assertSessionHasErrors('barcode');

        $this->assertSame(0, SchoolAttendance::count());
    }

    // =================================================================
    // E. MASUK + duplikat (GUR-PIK-004 / AC-SCAN-01 / AC-SCAN-03)
    // =================================================================

    public function test_duplicate_masuk_is_rejected_with_recorded_time_and_no_extra_row(): void
    {
        [$teacherUser] = $this->onDutyTeacher('guru_duplikat_masuk');
        $student = $this->makeStudent('26270717');

        $this->scan($teacherUser, $student->barcode_code, 'MASUK')->assertSessionHasNoErrors();

        $this->travelTo('2026-09-28 07:40:00');

        // Teks persis PRD (ABSENSI SUDAH TERCATAT + jam yang sudah tercatat).
        // Diantisipasi LANGSUNG pada response POST: memanggil session('errors')
        // atau assert sebelumnya akan "meng-aged" flash sebelum GET berikutnya,
        // jadi render halaman diuji terpisah (test_rejected_scan_messages...).
        $this->scan($teacherUser, $student->barcode_code, 'MASUK')
            ->assertRedirect(route('guru.school-attendance-scanner.index'))
            ->assertSessionHasErrors([
                'check_in_time' => 'ABSENSI SUDAH TERCATAT. Sudah tercatat masuk pukul 07:15.',
            ]);

        $attendance = SchoolAttendance::whereDate('attendance_date', self::SERVER_DATE)->sole();
        $this->assertSame('2026-09-28 07:15:00', $attendance->check_in_time->format('Y-m-d H:i:s'), 'Jam lama tidak tertimpa.');

        // Scan yang ditolak TIDAK tercatat sebagai sukses, tapi tetap diaudit
        // dengan outcome eksplisit (PRD 04 §9.1).
        $audits = AuditLog::where('action', AuditLog::ACTION_SCHOOL_ATT_SCAN)
            ->orderBy('id')
            ->pluck('new_values')
            ->map(fn ($v) => (array) $v)
            ->all();

        $this->assertCount(2, $audits, 'Satu baris audit per percobaan scan: sukses + ditolak.');
        $this->assertTrue((bool) $audits[0]['attendance_changed']);
        $this->assertSame('SCAN_SUCCESS', $audits[0]['outcome']);
        $this->assertFalse((bool) $audits[1]['attendance_changed'], 'Penolakan tidak mengubah data.');
        $this->assertSame('DUPLICATE_MASUK', $audits[1]['outcome']);
        $this->assertSame(1, DB::table('student_school_attendances')->count(), 'Audit gagal tidak ikut menyalakan baris absensi.');
    }

    // =================================================================
    // F. PULANG + Keputusan A1 (GUR-PIK-005/006, AC-SCAN-02/04/06)
    // =================================================================

    public function test_pulang_is_recorded_on_the_same_row_after_masuk(): void
    {
        [$teacherUser, $teacher] = $this->onDutyTeacher('guru_pulang_ok');
        $student = $this->makeStudent('26270718');

        $this->scan($teacherUser, $student->barcode_code, 'MASUK')->assertSessionHasNoErrors();

        $this->travelTo('2026-09-28 13:05:00');

        $this->scan($teacherUser, $student->barcode_code, 'PULANG')
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $attendance = SchoolAttendance::whereDate('attendance_date', self::SERVER_DATE)->sole();

        $this->assertSame('2026-09-28 07:15:00', $attendance->check_in_time->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-28 13:05:00', $attendance->check_out_time->format('Y-m-d H:i:s'));
        $this->assertSame('PULANG', $attendance->scan_mode_out);
        $this->assertSame($teacher->id, (int) $attendance->check_out_by_teacher_id);
        $this->assertSame(1, DB::table('student_school_attendances')->count(), 'MASUK + PULANG memakai baris yang sama.');

        $this->actingAs($teacherUser)
            ->get(route('guru.school-attendance-scanner.index'))
            ->assertOk()
            ->assertSee('PULANG tercatat untuk ' . $student->full_name . ' pukul 13:05.', false);
    }

    public function test_pulang_without_masuk_is_rejected_and_never_autocreates_record(): void
    {
        // [FINAL — KEPUTUSAN A1] (PRD 05 §5.10, PRD 01 GUR-PIK-006/AC-SCAN-06).
        [$teacherUser] = $this->onDutyTeacher('guru_pulang_tanpa_masuk');
        $student = $this->makeStudent('26270719');

        $this->scan($teacherUser, $student->barcode_code, 'PULANG')
            ->assertRedirect(route('guru.school-attendance-scanner.index'))
            ->assertSessionHasErrors([
                'check_out_time' => SchoolAttendanceScanService::MESSAGE_NO_CHECK_IN,
            ]);

        $this->assertSame(
            0,
            SchoolAttendance::count(),
            'Dilarang auto-create record atau membuat check_in palsu saat scan PULANG.'
        );

        $audit = AuditLog::where('action', AuditLog::ACTION_SCHOOL_ATT_SCAN)->sole();
        $outcome = (array) $audit->new_values;

        $this->assertSame('PULANG_WITHOUT_MASUK', $outcome['outcome']);
        $this->assertFalse((bool) $outcome['attendance_changed']);
        $this->assertSame('no_attendance_row_today', $outcome['alasan']);
    }

    public function test_rejected_scan_messages_are_rendered_on_the_next_page_load(): void
    {
        // Bukti RENDER (bukan hanya flash): GET setelah POST yang ditolak
        // menampilkan pesan persis PRD pada panel "$errors" layout scanner.
        // Test ini SENGAJA tidak menyentuh session()/assertSession*() sebelum
        // GET karena assertion lebih dulu meng-aged flash (prilaku harness).
        [$teacherUser] = $this->onDutyTeacher('guru_render_pesan');
        $sudah = $this->makeStudent('26270740');
        $belum = $this->makeStudent('26270741');

        // (1) duplikat MASUK.
        $this->scan($teacherUser, $sudah->barcode_code, 'MASUK');
        $this->travelTo('2026-09-28 07:40:00');
        $this->scan($teacherUser, $sudah->barcode_code, 'MASUK');

        $this->actingAs($teacherUser)
            ->get(route('guru.school-attendance-scanner.index'))
            ->assertOk()
            ->assertSee('Scan tidak tersimpan')
            ->assertSee('ABSENSI SUDAH TERCATAT. Sudah tercatat masuk pukul 07:15.', false);

        // (2) PULANG tanpa MASUK -> teks Keputusan A1.
        $this->travelTo('2026-09-28 13:05:00');
        $this->scan($teacherUser, $belum->barcode_code, 'PULANG');

        $this->actingAs($teacherUser)
            ->get(route('guru.school-attendance-scanner.index'))
            ->assertOk()
            ->assertSee('Belum ada catatan MASUK hari ini. Hubungi Admin/TU untuk koreksi.', false);

        $this->assertSame(1, DB::table('student_school_attendances')->count(), 'Tidak ada baris tambahan dari scan yang ditolak.');
    }

    public function test_duplicate_pulang_keeps_original_time(): void
    {
        [$teacherUser] = $this->onDutyTeacher('guru_duplikat_pulang');
        $student = $this->makeStudent('26270720');

        $this->scan($teacherUser, $student->barcode_code, 'MASUK');

        $this->travelTo('2026-09-28 13:05:00');
        $this->scan($teacherUser, $student->barcode_code, 'PULANG');

        $this->travelTo('2026-09-28 13:30:00');
        $this->scan($teacherUser, $student->barcode_code, 'PULANG')->assertSessionHasErrors('check_out_time');

        $this->assertSame(
            'ABSENSI PULANG SUDAH TERCATAT. Sudah tercatat pulang pukul 13:05.',
            (string) session('errors')->first('check_out_time')
        );

        $attendance = SchoolAttendance::sole();
        $this->assertSame('2026-09-28 13:05:00', $attendance->check_out_time->format('Y-m-d H:i:s'));

        $outcomes = AuditLog::where('action', AuditLog::ACTION_SCHOOL_ATT_SCAN)
            ->orderBy('id')
            ->pluck('new_values')
            ->map(fn ($v) => is_string($v) ? json_decode($v, true) : (array) $v)
            ->pluck('outcome')
            ->all();

        $this->assertSame(
            ['SCAN_SUCCESS', 'SCAN_SUCCESS', 'DUPLICATE_PULANG'],
            $outcomes,
            'Dua scan sukses + satu penolakan duplikat, masing-masing dengan outcome sendiri.'
        );
    }

    // =================================================================
    // G. PEMALSUAN INPUT KLIEN (PRD 04 §3.2.4)
    // =================================================================

    public function test_client_supplied_identity_date_and_timestamp_are_ignored(): void
    {
        [$teacherUser, $teacher] = $this->onDutyTeacher('guru_spoof');
        [, $guruLain] = $this->makeTeacherUser('guru_lain_dipalsukan');
        $student = $this->makeStudent('26270721');

        $this->scan($teacherUser, $student->barcode_code, 'MASUK', [
            'teacher_id' => $guruLain->id,
            'operator_id' => $guruLain->id,
            'check_in_by_teacher_id' => $guruLain->id,
            'attendance_date' => '2026-01-01',
            'check_in_time' => '2026-01-01 00:00:00',
            'timestamp' => '1970-01-01 00:00:00',
            'role' => 'admin',
            'is_corrected' => '1',
        ])->assertSessionHasNoErrors();

        $attendance = SchoolAttendance::sole();

        $this->assertSame(self::SERVER_DATE, $attendance->attendance_date->toDateString(), 'Tanggal dari server.');
        $this->assertSame('2026-09-28 07:15:00', $attendance->check_in_time->format('Y-m-d H:i:s'), 'Jam dari server.');
        $this->assertSame($teacher->id, (int) $attendance->check_in_by_teacher_id, 'Operator = guru sesi login.');
        $this->assertNotSame($guruLain->id, (int) $attendance->check_in_by_teacher_id);
        $this->assertNull($attendance->check_out_by_teacher_id);
        $this->assertFalse((bool) $attendance->is_corrected, 'Flag koreksi tidak dapat diset dari payload.');

        $audit = AuditLog::where('action', AuditLog::ACTION_SCHOOL_ATT_SCAN)->sole();
        $this->assertSame($teacherUser->id, (int) $audit->user_id);
        $this->assertSame('teacher', $audit->role);
    }

    public function test_masuk_fills_an_existing_row_without_a_check_in(): void
    {
        // Baris hari ini sudah ada (mis. hasil koreksi yang mengosongkan MASUK)
        // -> scan MASUK mengisi baris yang sama, bukan membuat baris kedua.
        [$teacherUser, $teacher] = $this->onDutyTeacher('guru_isi_baris_kosong');
        $student = $this->makeStudent('26270722');

        $row = SchoolAttendance::create([
            'student_id' => $student->id,
            'attendance_date' => self::SERVER_DATE,
            'check_in_time' => null,
        ]);

        $this->scan($teacherUser, $student->barcode_code, 'MASUK')->assertSessionHasNoErrors();

        $this->assertSame(1, DB::table('student_school_attendances')->count());
        $row->refresh();
        $this->assertSame('2026-09-28 07:15:00', $row->check_in_time->format('Y-m-d H:i:s'));
        $this->assertSame($teacher->id, (int) $row->check_in_by_teacher_id);
    }

    public function test_two_students_on_the_same_date_get_their_own_rows(): void
    {
        [$teacherUser] = $this->onDutyTeacher('guru_dua_siswa');
        $satu = $this->makeStudent('26270723');
        $dua = $this->makeStudent('26270724');

        $this->scan($teacherUser, $satu->barcode_code, 'MASUK')->assertSessionHasNoErrors();
        $this->scan($teacherUser, $dua->barcode_code, 'MASUK')->assertSessionHasNoErrors();

        $this->assertSame(2, SchoolAttendance::count());
        $this->assertSame(2, AuditLog::where('action', AuditLog::ACTION_SCHOOL_ATT_SCAN)->count());
    }

    // =================================================================
    // H. CSRF (PRD 04 §5.1)
    // =================================================================

    public function test_scanner_post_route_is_inside_csrf_and_authorization_pipeline(): void
    {
        // Definisi group `web` baru digabung ke Router setelah request pertama.
        $this->get('/login')->assertOk();

        $route = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($r) => $r->uri() === 'guru/school-attendance-scanner' && in_array('POST', $r->methods(), true));

        $this->assertNotNull($route, 'Rute POST scanner harus terdaftar.');

        $resolved = app('router')->gatherRouteMiddleware($route);

        $this->assertContains(PreventRequestForgery::class, $resolved, 'POST scanner wajib CSRF (PRD 04 §5.1).');
        $this->assertContains(Authenticate::class, $resolved);
        $this->assertContains(EnsureUserHasRole::class . ':teacher', $resolved);
        $this->assertContains(EnsureTeacherOnDuty::class, $resolved);
    }

    public function test_scanner_page_form_carries_session_csrf_token_and_no_identity_fields(): void
    {
        [$teacherUser, $teacher] = $this->onDutyTeacher('guru_form_csrf');

        $response = $this->actingAs($teacherUser)
            ->get(route('guru.school-attendance-scanner.index'))
            ->assertOk()
            ->assertSee('name="_token"', false)
            ->assertSee('name="barcode"', false)
            ->assertSee('name="mode"', false)
            ->assertSee('id="qr-reader"', false);

        $this->assertIsString(session()->token());
        $this->assertNotEmpty(session()->token());
        $response->assertSee(session()->token(), false);

        $response->assertSee('[ MASUK ]')->assertSee('[ PULANG ]')->assertSee($teacher->full_name);

        // Tidak ada field identitas/tanggal/waktu yang bisa diplintir klien.
        foreach (['name="teacher_id"', 'name="operator_id"', 'name="attendance_date"', 'name="timestamp"', 'name="check_in_by_teacher_id"'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $response->getContent());
        }
    }

    public function test_csrf_middleware_rejects_scanner_post_without_matching_token(): void
    {
        // Laravel melewati cek token saat berjalan sebagai unit test
        // (PreventRequestForgery::runningUnitTests()). Agar penegakan CSRF tidak
        // sekadar diasumsikan, middleware yang sama dijalankan dalam mode ketat
        // terhadap request POST nyata menuju rute scanner.
        $middleware = $this->strictCsrfMiddleware();

        $session = $this->app['session']->driver();
        $session->start();
        $session->put('_token', 'token-asli-di-sesi');

        $request = Request::create(route('guru.school-attendance-scanner.store'), 'POST', [
            'barcode' => '26270701',
            'mode' => 'MASUK',
        ]);
        $request->setLaravelSession($session);

        $this->expectException(TokenMismatchException::class);

        $middleware->handle($request, fn () => response('ok'));
    }

    public function test_csrf_middleware_accepts_scanner_post_with_matching_token(): void
    {
        // Kontrol positif: penolakan pada test di atas bukan efek samping setup.
        $middleware = $this->strictCsrfMiddleware();

        $session = $this->app['session']->driver();
        $session->start();
        $session->put('_token', 'token-asli-di-sesi');

        $request = Request::create(route('guru.school-attendance-scanner.store'), 'POST', [
            '_token' => 'token-asli-di-sesi',
            'barcode' => '26270701',
            'mode' => 'MASUK',
        ]);
        $request->setLaravelSession($session);

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
    }

    private function strictCsrfMiddleware(): PreventRequestForgery
    {
        return new class($this->app, $this->app[Encrypter::class]) extends PreventRequestForgery {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        };
    }

    // =================================================================
    // I. BALAPAN DUPlikat (ADDENDUM §3/§5 — UNIQUE = penjaga terakhir)
    // =================================================================

    public function test_duplicate_race_is_caught_by_database_constraint_without_http_500(): void
    {
        [$teacherUser] = $this->onDutyTeacher('guru_race');
        $student = $this->makeStudent('26270725');

        // Hook meniru "permintaan paralel yang sudah INSERT lebih dulu": berjalan
        // tepat saat INSERT model akan dieksekusi, yaitu SETELAH application
        // guard (lock + cek check_in_time) lolos, jadi guard tidak bisa melihat
        // baris pesaing.
        $events = app('events');
        $ranOnce = false;

        $events->listen('eloquent.creating: ' . SchoolAttendance::class, function () use ($student, &$ranOnce): void {
            if ($ranOnce) {
                return;
            }

            $ranOnce = true;

            DB::table('student_school_attendances')->insert([
                'student_id' => $student->id,
                'attendance_date' => self::SERVER_DATE,
                'check_in_time' => '2026-09-28 07:14:00',
                'scan_mode_in' => 'MASUK',
            ]);
        });

        $thrown = null;

        try {
            $response = $this->scan($teacherUser, $student->barcode_code, 'MASUK');
        } catch (\Throwable $e) {
            $thrown = $e;
        } finally {
            $events->forget('eloquent.creating: ' . SchoolAttendance::class);
        }

        $this->assertTrue($ranOnce, 'Skenario balapan harus benar-benar terpicu.');

        if ($thrown !== null) {
            $this->fail('Balapan duplikat harus menjadi penolakan aplikasi, bukan exception mentah: ' . $thrown::class);
        }

        // BUKAN HTTP 500.
        $this->assertNotSame(500, $response->getStatusCode());
        $response->assertRedirect()->assertSessionHasErrors('check_in_time');

        $this->assertSame(
            'ABSENSI SUDAH TERCATAT. Perubahan tidak disimpan.',
            (string) session('errors')->first('check_in_time')
        );

        // JAMINAN: transaksi atomik -> baris pesaing ikut ter-rollback.
        $this->assertSame(0, DB::table('student_school_attendances')->count(), 'Permintaan yang kalah di-rollback.');

        // Peristiwa scan-nya TETAP teraudit (write-after-rollback), tapi tidak
        // pernah tercatat sebagai perubahan absensi.
        $audit = AuditLog::where('action', AuditLog::ACTION_SCHOOL_ATT_SCAN)->sole();
        $outcome = is_string($audit->new_values) ? json_decode($audit->new_values, true) : (array) $audit->new_values;

        $this->assertSame('DUPLICATE_RACE_DATABASE_CONSTRAINT', $outcome['outcome']);
        $this->assertFalse((bool) $outcome['attendance_changed']);
        $this->assertSame(
            0,
            DB::table('student_school_attendances')->count(),
            'Menulis audit setelah rollback tidak boleh ikut menyalakan baris absensi.'
        );

        // Log aplikasi mencatat penyebabnya TANPA menampilkannya ke klien (PRD 04 §10).
        $this->assertStringNotContainsString('UniqueConstraintViolation', (string) session('errors')->first('check_in_time'));
    }

    public function test_lock_statement_is_used_inside_transaction(): void
    {
        // Rekan concurrency: `SELECT ... FOR UPDATE` benar-benar diterbitkan pada
        // driver MySQL (bukan klaim kosong). Diamati lewat DB listen.
        [$teacherUser] = $this->onDutyTeacher('guru_lock');
        $student = $this->makeStudent('26270726');

        $queries = [];

        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->scan($teacherUser, $student->barcode_code, 'MASUK')->assertSessionHasNoErrors();

        $locked = array_values(array_filter($queries, fn ($sql) => str_contains($sql, 'for update')));

        $this->assertNotEmpty($locked, 'Scanner wajib mengunci baris hari ini (lockForUpdate).');
        $this->assertStringContainsString('student_school_attendances', $locked[0]);
    }

    // =================================================================
    // J. MONITORING & KOREKSI ADMIN/TU
    // =================================================================

    public function test_admin_monitoring_page_lists_records_and_stats(): void
    {
        [$teacherUser, $teacher] = $this->onDutyTeacher('guru_monitor');
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_monitor');
        $supervisor = $this->makeUser(User::ROLE_SUPERVISOR, 'supervisor_monitor');
        $student = $this->makeStudent('26270727');

        $this->scan($teacherUser, $student->barcode_code, 'MASUK');
        $this->travelTo('2026-09-28 13:05:00');
        $this->scan($teacherUser, $student->barcode_code, 'PULANG');

        foreach ([$admin, $supervisor] as $viewer) {
            $this->actingAs($viewer)
                ->get(route('admin.school-attendances.index'))
                ->assertOk()
                ->assertSee($student->full_name)
                ->assertSee($teacher->full_name)
                ->assertSee('Sudah MASUK')
                ->assertSee('Belum MASUK')
                ->assertSee(self::SERVER_DATE);
        }

        // Tautan koreksi dirender HANYA untuk pemegang hak (Policy).
        $this->actingAs($admin)
            ->get(route('admin.school-attendances.index'))
            ->assertOk()
            ->assertSee('Koreksi');

        $this->actingAs($supervisor)
            ->get(route('admin.school-attendances.index'))
            ->assertOk()
            ->assertDontSee('Koreksi', false);
    }

    public function test_monitoring_filters_by_date_and_status(): void
    {
        [$teacherUser] = $this->onDutyTeacher('guru_filter');
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_filter');
        $belum = $this->makeStudent('26270728');
        $masuk = $this->makeStudent('26270729');
        $pulang = $this->makeStudent('26270730');

        $this->scan($teacherUser, $masuk->barcode_code, 'MASUK');
        $this->scan($teacherUser, $pulang->barcode_code, 'MASUK');
        $this->travelTo('2026-09-28 13:05:00');
        $this->scan($teacherUser, $pulang->barcode_code, 'PULANG');

        // Siswa tanpa scan sama sekali TIDAK punya baris record; ia dihitung
        // pada statistik "belum masuk", bukan muncul sebagai baris kosong.
        $this->assertSame(0, SchoolAttendance::where('student_id', $belum->id)->count());
        $this->assertSame(2, SchoolAttendance::count());

        $this->actingAs($admin)
            ->get(route('admin.school-attendances.index', ['status' => 'SUDAH_PULANG']))
            ->assertOk()
            ->assertSee($pulang->full_name)
            ->assertDontSee($masuk->full_name, false);

        $this->actingAs($admin)
            ->get(route('admin.school-attendances.index', ['status' => 'MASUK']))
            ->assertOk()
            ->assertSee($masuk->full_name)
            ->assertDontSee($pulang->full_name, false);

        // Tanggal lain -> kosong (data tidak terbocor antar-tanggal).
        $this->actingAs($admin)
            ->get(route('admin.school-attendances.index', ['date' => '2026-01-01']))
            ->assertOk()
            ->assertDontSee($masuk->full_name, false);

        // Nilai filter asing tidak pernah masuk ke SQL (tidak ada raw SQL).
        $this->actingAs($admin)
            ->get(route('admin.school-attendances.index', [
                'date' => "2026-09-28' OR 1=1 --",
                'status' => 'DROP TABLE',
            ]))
            ->assertOk()
            ->assertSee($masuk->full_name);
    }

    public function test_guru_has_no_monitoring_or_correction_access(): void
    {
        [$teacherUser] = $this->onDutyTeacher('guru_coba_koreksi');
        $student = $this->makeStudent('26270731');

        $this->scan($teacherUser, $student->barcode_code, 'MASUK');
        $attendance = SchoolAttendance::sole();

        // Guru (termasuk Guru Piket operator scan) tidak punya hak di area ini.
        $this->actingAs($teacherUser)->get(route('admin.school-attendances.index'))->assertForbidden();
        $this->actingAs($teacherUser)
            ->get(route('admin.school-attendances.correct', $attendance))
            ->assertForbidden();
        $this->actingAs($teacherUser)->patch(route('admin.school-attendances.update', $attendance), [
            'check_in_time' => '2026-09-28 06:00',
            'reason' => 'mencoba mengoreksi sebagai guru',
        ])->assertForbidden();

        $this->assertSame('2026-09-28 07:15:00', $attendance->fresh()->check_in_time->format('Y-m-d H:i:s'));
        $this->assertFalse((bool) $attendance->fresh()->is_corrected);

        // Tidak ada rute riwayat/koreksi/hapus pada area guru.
        foreach (['PUT', 'PATCH', 'DELETE'] as $verb) {
            $this->assertEmpty(
                collect(Route::getRoutes()->getRoutes())
                    ->filter(fn ($r) => str_contains($r->uri(), 'school-attendance') && in_array($verb, $r->methods(), true))
                    ->filter(fn ($r) => ! str_starts_with($r->uri(), 'admin/'))
                    ->all(),
                "Metode {$verb} dilarang pada rute scanner."
            );
        }
    }

    public function test_correction_requires_reason_of_at_least_ten_characters(): void
    {
        [$teacherUser] = $this->onDutyTeacher('guru_koreksi_alasan');
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_koreksi_alasan');
        $student = $this->makeStudent('26270732');

        $this->scan($teacherUser, $student->barcode_code, 'MASUK');
        $attendance = SchoolAttendance::sole();

        // 8 karakter -> ditolak (PRD 01 §6.9 ADM-ABS-003).
        $this->actingAs($admin)->patch(route('admin.school-attendances.update', $attendance), [
            'check_in_time' => '2026-09-28 06:50',
            'reason' => 'sembilan',
        ])->assertSessionHasErrors('reason');

        $this->assertSame('2026-09-28 07:15:00', $attendance->fresh()->check_in_time->format('Y-m-d H:i:s'));
        $this->assertFalse((bool) $attendance->fresh()->is_corrected);
        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_SCHOOL_ATT_CORRECT)->count());

        // Alasan kosong -> ditolak.
        $this->actingAs($admin)->patch(route('admin.school-attendances.update', $attendance), [
            'check_in_time' => '2026-09-28 06:50',
        ])->assertSessionHasErrors('reason');

        // 10+ karakter -> sah.
        $this->actingAs($admin)->patch(route('admin.school-attendances.update', $attendance), [
            'check_in_time' => '2026-09-28 06:50',
            'reason' => 'sepuluh karakter',
        ])->assertSessionHasNoErrors();

        $attendance->refresh();
        $this->assertSame('2026-09-28 06:50:00', $attendance->check_in_time->format('Y-m-d H:i:s'));
        $this->assertTrue((bool) $attendance->is_corrected);
        $this->assertSame('sepuluh karakter', $attendance->correction_reason);
        $this->assertSame(1, AuditLog::where('action', AuditLog::ACTION_SCHOOL_ATT_CORRECT)->count());
    }

    public function test_correction_writes_audit_with_actor_server_time_old_and_new_values(): void
    {
        [$teacherUser, $teacher] = $this->onDutyTeacher('guru_koreksi_audit');
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_koreksi_audit');
        $student = $this->makeStudent('26270733');

        $this->scan($teacherUser, $student->barcode_code, 'MASUK');
        $attendance = SchoolAttendance::sole();
        $this->assertSame(1, AuditLog::where('action', AuditLog::ACTION_SCHOOL_ATT_SCAN)->count());

        $this->travelTo('2026-09-28 08:00:00');

        $this->actingAs($admin)->patch(route('admin.school-attendances.update', $attendance), [
            'check_in_time' => '2026-09-28 06:40',
            'reason' => 'Siswa scan di gerbang belakang, jam masuk ditulis ulang oleh TU.',
        ])->assertRedirect(route('admin.school-attendances.index', ['date' => self::SERVER_DATE]))
            ->assertSessionHas('status');

        $audit = AuditLog::where('action', AuditLog::ACTION_SCHOOL_ATT_CORRECT)->sole();

        $this->assertSame($admin->id, (int) $audit->user_id, 'Actor dari sesi login, bukan payload.');
        $this->assertSame('admin', $audit->role);
        $this->assertSame('2026-09-28 08:00:00', $audit->created_at->format('Y-m-d H:i:s'), 'Waktu audit dari server.');
        $this->assertSame('2026-09-28 07:15:00', $audit->old_values['check_in_time']);
        $this->assertSame('2026-09-28 06:40:00', $audit->new_values['check_in_time']);
        $this->assertStringContainsString('gerbang belakang', $audit->new_values['alasan_koreksi']);

        // Payload audit bebas data sensitif (PRD 04 §9.2).
        $raw = strtolower(json_encode([$audit->old_values, $audit->new_values], JSON_THROW_ON_ERROR));
        foreach (['password', 'remember_token', 'rahasia123'] as $secret) {
            $this->assertStringNotContainsString($secret, $raw);
        }

        $attendance->refresh();
        $this->assertSame($admin->id, (int) $attendance->corrected_by_user_id);
        $this->assertSame('2026-09-28 08:00:00', $attendance->corrected_at->format('Y-m-d H:i:s'));

        // Jejak operator scan ASLI tidak ditimpa actor koreksi (Admin bukan guru;
        // kolom ini FK ke teachers).
        $this->assertSame($teacher->id, (int) $attendance->check_in_by_teacher_id);
        $this->assertSame('MASUK', $attendance->scan_mode_in);
    }

    public function test_supervisor_is_read_only_and_cannot_correct(): void
    {
        [$teacherUser] = $this->onDutyTeacher('guru_supervisor_koreksi');
        $supervisor = $this->makeUser(User::ROLE_SUPERVISOR, 'supervisor_koreksi');
        $student = $this->makeStudent('26270734');

        $this->scan($teacherUser, $student->barcode_code, 'MASUK');
        $attendance = SchoolAttendance::sole();

        // Boleh membaca rekap, tidak boleh membuka/menyimpan koreksi.
        $this->actingAs($supervisor)->get(route('admin.school-attendances.index'))->assertOk();

        $this->actingAs($supervisor)
            ->get(route('admin.school-attendances.correct', $attendance))
            ->assertForbidden();

        $this->actingAs($supervisor)->patch(route('admin.school-attendances.update', $attendance), [
            'check_in_time' => '2026-09-28 06:30',
            'reason' => 'coba koreksi oleh supervisor',
        ])->assertForbidden();

        $this->assertSame('2026-09-28 07:15:00', $attendance->fresh()->check_in_time->format('Y-m-d H:i:s'));
        $this->assertFalse((bool) $attendance->fresh()->is_corrected);
        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_SCHOOL_ATT_CORRECT)->count());
    }

    public function test_correction_rules_guard_row_coherence(): void
    {
        [$teacherUser] = $this->onDutyTeacher('guru_koreksi_aturan');
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_koreksi_aturan');
        $student = $this->makeStudent('26270735');

        $this->scan($teacherUser, $student->barcode_code, 'MASUK');
        $attendance = SchoolAttendance::sole();

        // PULANG lebih awal dari MASUK -> ditolak.
        $this->actingAs($admin)->patch(route('admin.school-attendances.update', $attendance), [
            'check_out_time' => '2026-09-28 06:00',
            'reason' => 'menit pulang lebih awal',
        ])->assertSessionHasErrors('check_out_time');

        // Isi PULANG yang sah.
        $this->actingAs($admin)->patch(route('admin.school-attendances.update', $attendance), [
            'check_out_time' => '2026-09-28 13:00',
            'reason' => 'mengisi pulang lebih dulu',
        ])->assertSessionHasNoErrors();

        // Mengosongkan MASUK sementara PULANG terisi -> ditolak.
        $this->actingAs($admin)->patch(route('admin.school-attendances.update', $attendance), [
            'check_in_time' => '',
            'reason' => 'kosongkan masuk saja',
        ])->assertSessionHasErrors('check_in_time');

        // Format waktu asing -> ditolak, nilai tidak berubah.
        $this->actingAs($admin)->patch(route('admin.school-attendances.update', $attendance), [
            'check_in_time' => '28-09-2026 07:00',
            'reason' => 'format tanggal salah',
        ])->assertSessionHasErrors('check_in_time');

        $attendance->refresh();
        $this->assertSame('2026-09-28 07:15:00', $attendance->check_in_time->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-28 13:00:00', $attendance->check_out_time->format('Y-m-d H:i:s'));
    }

    public function test_pulang_can_be_corrected_when_masuk_was_never_scanned(): void
    {
        // Rantai kasus A1: scan PULANG ditolak -> Admin/TU mengisi MASUK + PULANG
        // lewat koreksi (jalur resmi, dengan alasan + audit).
        [$teacherUser] = $this->onDutyTeacher('guru_koreksi_a1');
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_koreksi_a1');
        $student = $this->makeStudent('26270736');

        $this->scan($teacherUser, $student->barcode_code, 'PULANG')->assertSessionHasErrors('check_out_time');
        $this->assertSame(0, SchoolAttendance::count());

        // Admin membuat baris + mengisinya lewat jalur koreksi resmi.
        $attendance = SchoolAttendance::create([
            'student_id' => $student->id,
            'attendance_date' => self::SERVER_DATE,
        ]);

        $this->actingAs($admin)->patch(route('admin.school-attendances.update', $attendance), [
            'check_in_time' => '2026-09-28 07:05',
            'check_out_time' => '2026-09-28 13:00',
            'reason' => 'Kartu siswa rusak, kedua jam dicatat manual oleh TU.',
        ])->assertSessionHasNoErrors();

        $attendance->refresh();
        $this->assertSame('2026-09-28 07:05:00', $attendance->check_in_time->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-28 13:00:00', $attendance->check_out_time->format('Y-m-d H:i:s'));
        $this->assertTrue((bool) $attendance->is_corrected);
        $this->assertNull($attendance->check_in_by_teacher_id, 'Koreksi tidak mengaku-aku operator scan.');

        // Setelah MASUK sah ada, scan PULANG di hari yang sama bisa lewat scanner
        // (dan menolak duplikat).
        $this->travelTo('2026-09-28 13:20:00');
        $this->scan($teacherUser, $student->barcode_code, 'PULANG')->assertSessionHasErrors('check_out_time');
    }

    public function test_attendance_date_cannot_be_moved_by_correction(): void
    {
        [$teacherUser] = $this->onDutyTeacher('guru_koreksi_tanggal');
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_koreksi_tanggal');
        $student = $this->makeStudent('26270737');

        $this->scan($teacherUser, $student->barcode_code, 'MASUK');
        $attendance = SchoolAttendance::sole();

        $this->actingAs($admin)->patch(route('admin.school-attendances.update', $attendance), [
            'attendance_date' => '2026-01-01',
            'check_in_time' => '2026-09-28 06:35',
            'reason' => 'mencoba memindah tanggal record',
        ])->assertSessionHasNoErrors();

        $attendance->refresh();
        $this->assertSame(self::SERVER_DATE, $attendance->attendance_date->toDateString(), 'Tanggal record tidak dapat dipindah.');
    }

    // =================================================================
    // K. HISTORI TERKUNCI, HEADER, DAN TANPA PENYIMPANAN KLIEN
    // =================================================================

    public function test_attendance_history_is_not_modified_by_duty_schedule_changes(): void
    {
        [$teacherUser, $teacher, $schedule] = $this->onDutyTeacher('guru_histori');
        [, $guruBaru] = $this->makeTeacherUser('guru_pengganti_histori');
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_ubah_jadwal');
        $student = $this->makeStudent('26270738');

        $this->scan($teacherUser, $student->barcode_code, 'MASUK');
        $this->travelTo('2026-09-28 13:05:00');
        $this->scan($teacherUser, $student->barcode_code, 'PULANG');

        $before = DB::table('student_school_attendances')->where('id', SchoolAttendance::sole()->id)->first();
        $this->assertNotNull($before);

        // ADDENDUM §15–§16: jadwal dipindah lalu dihapus -> absensi tidak tersentuh.
        $schedule->update(['teacher_id' => $guruBaru->id]);
        $this->makeSchedule($guruBaru, '2026-09-29', $admin);
        $schedule->delete();

        $after = DB::table('student_school_attendances')->where('id', $before->id)->first();

        $this->assertNotNull($after, 'Record absensi historis tidak boleh hilang.');
        $this->assertEquals($before, $after, 'Perubahan jadwal piket tidak menyentuh histori absensi.');
        $this->assertSame($teacher->id, (int) $after->check_in_by_teacher_id, 'Operator scan terkunci.');
        $this->assertSame($teacher->id, (int) $after->check_out_by_teacher_id);
    }

    public function test_scanner_response_carries_security_headers(): void
    {
        [$teacherUser] = $this->onDutyTeacher('guru_headers');

        $response = $this->actingAs($teacherUser)
            ->get(route('guru.school-attendance-scanner.index'))
            ->assertOk();

        // PRD 04 §5.3 + ADR 20 [FINAL — Keputusan B2]: kamera origin sendiri.
        $this->assertSame('camera=(self), microphone=(), geolocation=()', $response->headers->get('Permissions-Policy'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));

        // HSTS TIDAK diklaim terverifikasi di sini: environment test bukan HTTPS
        // (penegakannya ada di SecurityHeadersTest + catatan produksi).
        $this->assertNull($response->headers->get('Strict-Transport-Security'), 'HTTP lokal tidak boleh mengirim HSTS.');

        // Respons 403 (penolakan jadwal) tetap membawa header yang sama.
        [$lain] = $this->makeTeacherUser('guru_headers_tolak');
        $denied = $this->actingAs($lain)->get(route('guru.school-attendance-scanner.index'))->assertForbidden();
        $this->assertSame('camera=(self), microphone=(), geolocation=()', $denied->headers->get('Permissions-Policy'));
        $this->assertSame('nosniff', $denied->headers->get('X-Content-Type-Options'));
    }

    public function test_scanner_does_not_persist_scan_data_on_the_client(): void
    {
        // Bukti statis (bukan klaim): tidak ada penyimpanan lokal / koneksi
        // langsung pada JS scanner maupun view-nya (PRD 04 §10).
        $js = $this->stripComments((string) file_get_contents(base_path('resources/js/scanner.js')));

        foreach (['localStorage', 'sessionStorage', 'indexedDB', 'WebSocket', 'navigator.geolocation', 'fetch(', 'XMLHttpRequest'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $js, "Pemakaian {$forbidden} dilarang pada scanner.");
        }

        $views = $this->scannerViewFiles();
        $this->assertNotEmpty($views, 'View scanner harus ada.');

        foreach ($views as $view) {
            $code = $this->stripComments((string) file_get_contents($view));

            $this->assertStringNotContainsString('localStorage', $code);
            $this->assertStringNotContainsString('{!!', $code, 'Output tak ter-escape dilarang (PRD 04 §8).');
            $this->assertStringNotContainsStringIgnoringCase('is_piket', $code);
        }
    }

    public function test_correction_form_and_layout_render_escaped_and_are_admin_only(): void
    {
        $this->assertSame(10, AttendanceCorrectionService::MIN_REASON_LENGTH);

        [$teacherUser] = $this->onDutyTeacher('guru_form_koreksi');
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_form_koreksi');
        $student = $this->makeStudent('26270739');

        $this->scan($teacherUser, $student->barcode_code, 'MASUK');

        $form = $this->actingAs($admin)
            ->get(route('admin.school-attendances.correct', SchoolAttendance::sole()))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('minlength="10"', (string) $form);
        $this->assertStringContainsString('name="reason"', (string) $form);
        $this->assertStringContainsString('name="_method"', (string) $form);
        $this->assertStringContainsString('PATCH', (string) $form);
        $this->assertStringNotContainsString('name="attendance_date"', (string) $form);
        $this->assertStringNotContainsString('name="corrected_by_user_id"', (string) $form);
        $this->assertStringNotContainsString('{!!', (string) $form);
    }

    public function test_no_piket_role_or_flag_is_introduced_by_the_scanner(): void
    {
        // ADDENDUM §8/§10: status piket tetap berbasis jadwal, bukan role/flag.
        $this->assertFalse(User::isValidRole('PIKET'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('users', 'is_piket'));

        $this->assertEmpty(
            collect(Route::getRoutes()->getRoutes())
                ->filter(fn ($r) => str_contains(strtolower($r->uri()), 'piket'))
                ->all(),
            'Tidak boleh ada rute berbasis nama "piket".'
        );

        // Sumber kebenaran otoritas = tabel jadwal.
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_flag_piket');
        [$teacherUser, $teacher] = $this->makeTeacherUser('guru_tanpa_flag');

        $this->assertFalse(app(\App\Services\TeacherDutyScheduleService::class)->isOnDuty($teacher));

        $this->makeSchedule($teacher, self::SERVER_DATE, $admin);

        $this->assertTrue(app(\App\Services\TeacherDutyScheduleService::class)->isOnDuty($teacher));
        $this->actingAs($teacherUser)->get(route('guru.school-attendance-scanner.index'))->assertOk();
    }

    public function test_teacher_attendance_feature_stays_dead(): void
    {
        // Larangan Adjustment D: Teacher Attendance tidak dihidupkan kembali.
        $this->assertFalse(Route::has('guru.teacher-attendance'));
        $this->assertFalse(class_exists(\App\Models\TeacherAttendance::class));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('teacher_attendances'));

        $this->assertEmpty(
            collect(Route::getRoutes()->getRoutes())
                ->filter(fn ($r) => str_contains(strtolower($r->uri()), 'teacher-attendance'))
                ->all()
        );
    }

    // =================================================================
    // L. SEMANTIK AUDIT SCHOOL_ATT_SCAN (koreksi Adjustment D)
    //
    // PRD 04 §9.1 mendefinisikan event-nya sebagai TINDAKAN "Guru Piket
    // melakukan scan barcode siswa", BUKAN sebagai hasil mutasi. Karena itu
    // setiap percobaan scan dari guru yang SAH secara otorisasi menghasilkan
    // satu baris audit dengan `outcome` eksplisit, dan kolom
    // `attendance_changed` membedakan "ada perubahan absensi" dari "tidak".
    // Penolakan SEBELUM event scan (bukan Guru Piket / role lain / tamu)
    // tetap TIDAK menghasilkan audit scan apa pun (PRD 02 §4b butir 1).
    // =================================================================

    /** @return array<string, mixed> outcome yang tercatat,urut pemanggilan */
    private function scanOutcomes(): array
    {
        return AuditLog::where('action', AuditLog::ACTION_SCHOOL_ATT_SCAN)
            ->orderBy('id')
            ->pluck('new_values')
            ->map(fn ($v) => (array) $v)
            ->pluck('outcome')
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function scanAuditPayloads(): array
    {
        return AuditLog::where('action', AuditLog::ACTION_SCHOOL_ATT_SCAN)
            ->orderBy('id')
            ->pluck('new_values')
            ->map(fn ($v) => (array) $v)
            ->all();
    }

    public function test_every_scan_event_from_an_authorized_teacher_is_audited_with_its_outcome(): void
    {
        [$teacherUser] = $this->onDutyTeacher('guru_matriks_outcome');
        $aktif = $this->makeStudent('26270750');
        $nonAktif = $this->makeStudent('26270751', 'MUTASI');
        $belumMasuk = $this->makeStudent('26270752');

        // 1. scan berhasil (MASUK).
        $this->scan($teacherUser, $aktif->barcode_code, 'MASUK')->assertSessionHasNoErrors();
        // 2. duplicate MASUK.
        $this->scan($teacherUser, $aktif->barcode_code, 'MASUK')->assertSessionHasErrors('check_in_time');
        // 3. scan berhasil (PULANG).
        $this->travelTo('2026-09-28 13:05:00');
        $this->scan($teacherUser, $aktif->barcode_code, 'PULANG')->assertSessionHasNoErrors();
        // 4. duplicate PULANG.
        $this->scan($teacherUser, $aktif->barcode_code, 'PULANG')->assertSessionHasErrors('check_out_time');
        // 5. barcode tidak ditemukan.
        $this->scan($teacherUser, 'TIDAKADA99', 'MASUK')->assertSessionHasErrors('barcode');
        // 6. siswa non-aktif.
        $this->scan($teacherUser, $nonAktif->barcode_code, 'MASUK')->assertSessionHasErrors('barcode');
        // 7. PULANG tanpa MASUK (Keputusan A1).
        $this->scan($teacherUser, $belumMasuk->barcode_code, 'PULANG')->assertSessionHasErrors('check_out_time');
        // 8. mode invalid -> ditahan FormRequest SEBELUM service (jalur audit terpisah).
        $this->scan($teacherUser, $aktif->barcode_code, 'AUTO')->assertSessionHasErrors('mode');
        // 9. barcode kosong -> juga ditahan FormRequest.
        $this->scan($teacherUser, '', 'MASUK')->assertSessionHasErrors('barcode');
        // 10. barcode terlalu panjang -> juga ditahan FormRequest.
        $this->scan($teacherUser, str_repeat('9', 60), 'MASUK')->assertSessionHasErrors('barcode');

        $this->assertSame(
            [
                'SCAN_SUCCESS',
                'DUPLICATE_MASUK',
                'SCAN_SUCCESS',
                'DUPLICATE_PULANG',
                'BARCODE_NOT_FOUND',
                'STUDENT_NOT_ACTIVE',
                'PULANG_WITHOUT_MASUK',
                'MODE_INVALID',
                'BARCODE_EMPTY',
                'BARCODE_TOO_LONG',
            ],
            $this->scanOutcomes(),
            'Setiap peristiwa scan Guru Piket wajib menghasilkan SATU audit dengan outcome sendiri.'
        );

        // Outcome gagal tidak pernah tercatat sebagai perubahan absensi.
        foreach ($this->scanAuditPayloads() as $payload) {
            $changed = array_key_exists('attendance_changed', $payload)
                ? (bool) $payload['attendance_changed']
                : null;

            $this->assertNotNull($changed, 'Setiap baris audit scan wajib punya flag attendance_changed.');
            $this->assertSame($payload['outcome'] === 'SCAN_SUCCESS', $changed, 'Hanya SCAN_SUCCESS yang mengubah absensi.');
        }

        // Tidak ada baris absensi tambahan dari seluruh penolakan: hanya siswa yang
        // scan-nya sukses (MASUK + PULANG) yang punya baris.
        $this->assertSame(
            1,
            DB::table('student_school_attendances')->count(),
            'Hanya peristiwa SCAN_SUCCESS yang boleh meninggalkan baris absensi.'
        );

        $this->assertSame(
            [$aktif->id],
            DB::table('student_school_attendances')->pluck('student_id')->map(fn ($v) => (int) $v)->all(),
            'Baris yang tersisa harus milik siswa yang discan sukses.'
        );

        // Keputusan A1 tetap utuh: siswa tanpa MASUK tidak dapat baris hasil PULANG.
        $this->assertSame(0, DB::table('student_school_attendances')->where('student_id', $belumMasuk->id)->count());
        $this->assertSame(0, DB::table('student_school_attendances')->where('student_id', $nonAktif->id)->count());
    }

    public function test_rejected_scan_audits_never_contain_the_scanned_barcode_or_secrets(): void
    {
        [$teacherUser] = $this->onDutyTeacher('guru_audit_sanitasi');
        $student = $this->makeStudent('26270753');

        // Barcode sengaja DIBEDAKAN dari NIS agar bisa dibuktikan: yang dilarang
        // tersimpan adalah barcode mentah, bukan identitas siswa yang sah.
        $barcode = 'BCG-26270753';
        $student->update(['barcode_code' => $barcode]);

        // Semua jalur penolakan sekaligus: unknown / non-aktif / kepanjangan / kosong / mode.
        $this->scan($teacherUser, 'TIDAKADA99', 'MASUK');
        $this->scan($teacherUser, $barcode . 'X', 'PULANG');
        $this->scan($teacherUser, $barcode, 'AUTO');
        $this->scan($teacherUser, '', 'PULANG');
        $this->scan($teacherUser, str_repeat('9', 60), 'MASUK');
        $this->scan($teacherUser, $barcode, 'MASUK')->assertSessionHasNoErrors();

        $raw = strtolower((string) json_encode($this->scanAuditPayloads(), JSON_THROW_ON_ERROR));

        // Barcode penuh (milik siswa yang dikenal maupun tebakan) DILARANG tersimpan.
        foreach ([$barcode, $barcode . 'x', 'tidakada99', str_repeat('9', 60)] as $leak) {
            $this->assertStringNotContainsString($leak, $raw, 'Payload audit memuat nilai klien: ' . $leak);
        }

        foreach (['password', 'rahasia123', 'remember_token', '_token', 'app_key', 'cookie', 'bearer'] as $secret) {
            $this->assertStringNotContainsString($secret, $raw, 'Payload audit memuat kata sensitif: ' . $secret);
        }

        $this->assertSame(1, DB::table('student_school_attendances')->count(), 'Penolakan tidak membuat baris absensi.');
    }

    public function test_requests_refused_before_the_scan_event_never_write_a_scan_audit(): void
    {
        // (a) guru TANPA jadwal piket mencoba scan -> 403, nol audit.
        [$bukanPiket] = $this->makeTeacherUser('guru_nol_audit');
        $this->scan($bukanPiket, '26270754', 'MASUK')->assertStatus(403);

        // (b) role non-guru -> role:teacher menolak sebelum apa pun terjadi.
        $admin = $this->makeUser(User::ROLE_ADMIN, 'admin_nol_audit');
        $supervisor = $this->makeUser(User::ROLE_SUPERVISOR, 'kepsek_nol_audit');
        $this->scan($admin, '26270754', 'MASUK')->assertForbidden();
        $this->scan($supervisor, '26270754', 'MASUK')->assertForbidden();

        $this->assertSame(
            0,
            AuditLog::where('action', AuditLog::ACTION_SCHOOL_ATT_SCAN)->count(),
            'Penolakan sebelum event scan Guru Piket tidak boleh menghasilkan audit scan.'
        );
        $this->assertSame(0, SchoolAttendance::count());
    }

    /**
     * Berlari sebagai test TERSENDIRI karena actingAs() pada test lain
     * meninggalkan user pada guard, sehingga request "tamu" tidak lagi
     * benar-benar tamu.
     */
    public function test_a_guest_scan_post_is_redirected_to_login_without_any_scan_audit(): void
    {
        // Tamu -> redirect login, bukan peristiwa scan Guru Piket.
        $this->post(route('guru.school-attendance-scanner.store'), ['barcode' => '26270754', 'mode' => 'MASUK'])
            ->assertRedirect(route('login'));

        $this->assertSame(
            0,
            AuditLog::where('action', AuditLog::ACTION_SCHOOL_ATT_SCAN)->count(),
            'Permintaan tamu bukan peristiwa scan Guru Piket dan tidak boleh diaudit.'
        );
        $this->assertSame(0, SchoolAttendance::count());
    }

    public function test_scan_attempt_by_a_non_on_duty_teacher_is_not_audited_even_when_form_validation_fails(): void
    {
        // Jalur FormRequest (failedValidation) juga wajib menghormati gerbang
        // otorisasi: mode invalid dari guru non-piket -> 403, nol audit scan.
        [$bukanPiket] = $this->makeTeacherUser('guru_mode_invalid_non_duty');

        $this->scan($bukanPiket, '26270755', 'AUTO')->assertStatus(403);

        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_SCHOOL_ATT_SCAN)->count());
    }

    public function test_rejection_audit_survives_the_attendance_transaction_rollback(): void
    {
        // Inti desain transaction-safe: penolakan terjadi DI DALAM
        // DB::transaction(), jadi audit ditulis SETELAH rollback. Baris absensi
        // tetap tidak bertambah, audit tetap ada, dan tidak ada HTTP 500.
        [$teacherUser] = $this->onDutyTeacher('guru_audit_tahan_rollback');
        $student = $this->makeStudent('26270756');

        $this->scan($teacherUser, $student->barcode_code, 'MASUK')->assertSessionHasNoErrors();
        $rowsAfterSuccess = DB::table('student_school_attendances')->count();

        $this->travelTo('2026-09-28 07:45:00');
        $rejected = $this->scan($teacherUser, $student->barcode_code, 'MASUK');

        $this->assertNotSame(500, $rejected->getStatusCode(), 'Penolakan wajib menjadi 422/redirect, bukan 500.');
        $rejected->assertRedirect()->assertSessionHasErrors('check_in_time');

        $this->assertSame($rowsAfterSuccess, DB::table('student_school_attendances')->count(), 'Rollback tetap utuh.');
        $this->assertSame(['SCAN_SUCCESS', 'DUPLICATE_MASUK'], $this->scanOutcomes());

        $penolakan = $this->scanAuditPayloads()[1];
        $this->assertFalse((bool) $penolakan['attendance_changed']);
        $this->assertSame('2026-09-28', $penolakan['attendance_date']);
        $this->assertGreaterThan(0, (int) $penolakan['operator_teacher_id'], 'Operator tercatat sebagai guru sesi.');
    }

    // =================================================================
    // M. ERROR RESPONSE TIDAK BOLEH MEMBOCORKAN DETAIL INTERNAL
    //
    // Root cause terukur: response 405 pada APP_DEBUG=true = ±867 kB dan
    // memuat 358 path file framework. Framework hanya punya view error untuk
    // 401/402/403/404/419/429/500/503, jadi 405 jatuh ke Symfony
    // HtmlErrorRenderer. Perbaikan: view errors/4xx + errors/5xx milik
    // aplikasi (menang atas vendor) + App\Support\ErrorMask saat debug=false.
    //
    // config('app.debug') DI-TOGGLE di dalam test (bukan .env pengguna).
    // =================================================================

    /**
     * @return array<string, string>
     *
     * Delimiter `~` dipakai karena pola ini memuat URL (mysql://, sqlite://)
     * yang akan memutus delimiter `/`.
     */
    private function forbiddenLeakPatterns(): array
    {
        return [
            'stack trace frame' => '~#\d+\s+.{0,200}\.php(\(|:)~i',
            'vendor path' => '~vendor[\\\\/](laravel|symfony|composer)~i',
            'app source path' => '~(?:app|routes|bootstrap|resources|storage)[\\\\/][\w\\/.+-]*\.php~i',
            'workspace path' => '~[A-Za-z]:[\\\\/](?:anti|Users)[\\\\/]~',
            'framework debug page' => '~MethodNotAllowedHttpException|NotFoundHttpException|AccessDeniedHttpException|HttpException~',
            'framework class' => '~Illuminate\\\\|Symfony\\\\Component|Carbon\\\\~',
            'APP_KEY' => '~APP_KEY|base64:[A-Za-z0-9+/=]{20,}~',
            'db credential' => '~DB_PASSWORD|DB_USERNAME|mysql://|sqlite://~i',
            'session token' => '~"_token"\s*:\s*"[A-Za-z0-9]{20,}|eyJpdiI6~',
            'php config' => '~phpinfo\(\)|PHP Version \d|Zend Engine~i',
        ];
    }

    private function assertNoInternalLeak(\Illuminate\Testing\TestResponse | \Symfony\Component\HttpFoundation\Response $response, string $label): void
    {
        $body = (string) $response->getContent();

        $this->assertNotEmpty($body, "Response {$label} kosong.");

        foreach ($this->forbiddenLeakPatterns() as $name => $pattern) {
            $this->assertDoesNotMatchRegularExpression(
                $pattern,
                $body,
                "Response {$label} membocorkan {$name}."
            );
        }
    }

    public function test_method_not_allowed_response_never_renders_the_framework_debug_page(): void
    {
        // Rute scanner HANYA menerima GET/POST; PUT wajib menghasilkan 405.
        $url = route('guru.school-attendance-scanner.index');

        foreach ([true, false] as $debug) {
            config(['app.debug' => $debug]);

            $response = $this->put($url, ['barcode' => '26270760', 'mode' => 'MASUK']);

            $this->assertSame(405, $response->getStatusCode(), 'PUT pada rute scanner wajib 405.');

            // Header Allow = metadata HTTP standar (daftar metode), BUKAN detail internal.
            $allow = (string) $response->headers->get('allow');
            $this->assertStringContainsString('POST', $allow, 'Rute scanner menerima POST; header Allow wajib informatif.');
            $this->assertDoesNotMatchRegularExpression('/php|illuminate|symfony/i', $allow);

            // Body halaman debug terukur 866.845 byte sebelum perbaikan.
            $this->assertLessThan(
                20_000,
                strlen((string) $response->getContent()),
                'Response 405 (debug=' . var_export($debug, true) . ') masih berupa halaman debug framework.'
            );

            $this->assertNoInternalLeak($response, '405 debug=' . var_export($debug, true));
        }
    }

    public function test_production_like_error_responses_do_not_leak_internal_details(): void
    {
        // production-like: debug=false, TANPA menyentuh .env development.
        config(['app.debug' => false]);

        // 404: rute tidak dikenal.
        $notFound = $this->get('/rute-yang-tidak-ada-xyz');
        $this->assertSame(404, $notFound->getStatusCode());
        $this->assertNoInternalLeak($notFound, '404');

        // 405: metode tidak diizinkan untuk rute yang ada.
        $methodNotAllowed = $this->put(route('guru.school-attendance-scanner.index'));
        $this->assertSame(405, $methodNotAllowed->getStatusCode());
        $this->assertNoInternalLeak($methodNotAllowed, '405');

        // 419: TokenMismatchException dipetakan framework ke status 419.
        // (middleware CSRF melewati mode unit-test, jadi exception-nya
        // dirender lewat handler nyata untuk menguji view status 419.)
        $csrfRequest = Request::create(route('guru.school-attendance-scanner.store'), 'POST');
        $csrf = app(\Illuminate\Contracts\Debug\ExceptionHandler::class)
            ->render($csrfRequest, new TokenMismatchException('CSRF token mismatch.'));
        $this->assertSame(419, $csrf->getStatusCode());
        $this->assertNoInternalLeak($csrf, '419');

        // 500: exception non-HTTP dari rute uji sementara.
        Route::get('/__test/boom', function () {
            throw new \RuntimeException('RAHASIA-INTERNAL-BOOM-DETAIL');
        });
        $serverError = $this->get('/__test/boom');
        $this->assertSame(500, $serverError->getStatusCode());
        $this->assertNoInternalLeak($serverError, '500');
        $this->assertStringNotContainsString('RAHASIA-INTERNAL-BOOM-DETAIL', (string) $serverError->getContent(), 'Pesan exception wajib tertutup.');

        // Header keamanan tetap menempel pada seluruh response error (PRD 04 §5.3).
        foreach ([$notFound, $methodNotAllowed, $serverError] as $i => $response) {
            $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'), 'Header keamanan hilang pada error #' . $i);
            $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        }
    }

    public function test_error_mask_replaces_a_framework_debug_page_and_keeps_application_views_intact(): void
    {
        config(['app.debug' => false]);

        // (a) Body bergaya halaman debug framework -> wajib diganti total.
        $debugPage = new \Symfony\Component\HttpFoundation\Response(
            '<h1>Symfony\\Component\\HttpKernel\\Exception\\MethodNotAllowedHttpException</h1>'
            . '<p>#0 D:\\anti\\portal-sekolah\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(120): x()</p>'
            . '<pre>APP_KEY=base64:RAHASIARAHASIA</pre>',
            405
        );
        $masked = \App\Support\ErrorMask::apply($debugPage);
        $body = (string) $masked->getContent();

        $this->assertStringNotContainsString('vendor', $body);
        $this->assertStringNotContainsString('Router.php', $body);
        $this->assertStringNotContainsString('base64:RAHASIA', $body);
        $this->assertStringNotContainsString('MethodNotAllowedHttpException', $body);
        $this->assertStringContainsString('405', $body, 'Angka status tetap informatif bagi pengguna.');
        $this->assertSame(405, $masked->getStatusCode(), 'Status code tidak boleh berubah.');

        // (b) Payload JSON debug -> key internal dibuang, pesan UX dipertahankan.
        $json = new \Illuminate\Http\JsonResponse([
            'message' => 'The given data was invalid.',
            'exception' => 'Illuminate\\Validation\\ValidationException',
            'file' => 'D:\\anti\\portal-sekolah\\app\\Services\\X.php',
            'line' => 42,
            'trace' => [['file' => 'D:\\secret.php']],
            'errors' => ['barcode' => ['Barcode wajib dipindai atau diisi.']],
        ], 422);
        $payload = \App\Support\ErrorMask::apply($json)->getData(true);

        foreach (['exception', 'file', 'line', 'trace'] as $noise) {
            $this->assertArrayNotHasKey($noise, $payload, 'Key debug JSON wajib dibuang: ' . $noise);
        }
        $this->assertSame('The given data was invalid.', $payload['message'], 'Pesan UX wajib dipertahankan.');
        $this->assertSame(['barcode' => ['Barcode wajib dipindai atau diisi.']], $payload['errors']);

        // (c) Response sukses / status < 400 TIDAK boleh disentuh.
        $ok = new \Symfony\Component\HttpFoundation\Response('vendor/laravel biasa', 200);
        $this->assertSame('vendor/laravel biasa', \App\Support\ErrorMask::apply($ok)->getContent());

        // (d) debug=true -> helper tidak aktif (pengalaman development utuh),
        //     dan view errors/4xx aplikasi tetap yang menjadi lapis pertama.
        config(['app.debug' => true]);
        $untouched = new \Symfony\Component\HttpFoundation\Response('#0 D:\\anti\\x.php(1): y()', 500);
        $this->assertSame('#0 D:\\anti\\x.php(1): y()', \App\Support\ErrorMask::apply($untouched)->getContent());
    }

    public function test_scanner_locked_page_message_is_preserved_in_production_mode(): void
    {
        // Regression lawan dari over-masking: teks PRD yang aman & diperlukan UX
        // wajib TETAP ada pada response 403 walau debug=false (PRD 04 §16.5).
        config(['app.debug' => false]);

        [$teacherUser] = $this->makeTeacherUser('guru_teks_utuh');

        $response = $this->actingAs($teacherUser)->get(route('guru.school-attendance-scanner.index'));

        $response->assertStatus(403)
            ->assertSee('Anda hari ini bukan Guru Piket.', false)
            ->assertSee(self::SERVER_DATE);

        $this->assertNoInternalLeak($response, '403 scanner terkunci');
    }
}
