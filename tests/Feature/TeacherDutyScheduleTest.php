<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\SchoolAttendance;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherDutySchedule;
use App\Models\User;
use App\Services\TeacherDutyScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ADJUSTMENT C — Jadwal Guru Piket (Teacher Duty Schedule).
 *
 * Acuan pengujian:
 * - PRD 01 §22.6 AC-DUTY-01, AC-DUTY-02, AC-DUTY-03, AC-DUTY-04
 * - PRD 01 §6.11 ADM-PIK-001/002/004, §5.4 matriks role
 * - PRD 02 §4c alur TeacherDutyScheduleService, §6 rute /admin/duty-schedules
 * - PRD 04 §3.2 otorisasi server-side, §9.1 aksi DUTY_SCHEDULE_*,
 *   §9.2 penyaringan data sensitif, §16.5 butir 5-7, §19.A
 * - ADDENDUM §8 (piket bukan role), §9 (Admin/TU saja), §10 (larangan
 *   hardcode), §12 (revalidasi per request), §15-§16 (histori terkunci),
 *   §17 (audit), §22, §24 (tanpa WebSocket/Queue)
 *
 * TIDAK ada test scanner/browser: fitur scanner dibangun pada tahap
 * adjustment berikutnya (PRD 01 §7.4).
 */
class TeacherDutyScheduleTest extends TestCase
{
    use RefreshDatabase;

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
    private function makeTeacherUser(string $username, bool $active = true): array
    {
        $user = $this->makeUser(User::ROLE_TEACHER, $username);
        $user->update(['is_active' => $active]);

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'nip' => '1985' . str_pad((string) abs(crc32($username)) % 100000000, 8, '0', STR_PAD_LEFT) . '001',
            'full_name' => 'Guru ' . ucwords(str_replace('_', ' ', $username)),
            'gender' => 'L',
        ]);

        return [$user->fresh(), $teacher];
    }

    private function admin(): User
    {
        return $this->makeUser(User::ROLE_ADMIN, 'admin_tu_duty');
    }

    private function makeStudent(string $nis): Student
    {
        return Student::create([
            'nis' => $nis,
            'nisn' => '0098' . $nis,
            'barcode_code' => $nis,
            'full_name' => 'Siswa ' . $nis,
            'gender' => 'L',
            'status' => 'AKTIF',
        ]);
    }

    private function makeSchedule(Teacher $teacher, string $date, User $actor, string $notes = 'catatan awal'): TeacherDutySchedule
    {
        return TeacherDutySchedule::create([
            'schedule_date' => $date,
            'teacher_id' => $teacher->id,
            'created_by_user_id' => $actor->id,
            'notes' => $notes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Teacher $teacher, string $date, array $overrides = []): array
    {
        return array_merge([
            'teacher_id' => $teacher->id,
            'schedule_date' => $date,
            'notes' => 'Piket gerbang pagi',
        ], $overrides);
    }

    // =================================================================
    // 1. CRUD OLEH ADMIN/TU  (ADM-PIK-001 / ADM-PIK-002)
    // =================================================================

    public function test_admin_can_create_duty_schedule(): void
    {
        $admin = $this->admin();
        [$guru, $teacher] = $this->makeTeacherUser('guru_kreatif');

        $response = $this->actingAs($admin)->post(route('admin.duty-schedules.store'), $this->payload($teacher, '2026-09-28'));

        $response->assertRedirect(route('admin.duty-schedules.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $schedule = TeacherDutySchedule::whereDate('schedule_date', '2026-09-28')->first();
        $this->assertNotNull($schedule, 'Jadwal piket tersimpan (ADDENDUM §9).');
        $this->assertSame($teacher->id, (int) $schedule->teacher_id);
        $this->assertSame('Piket gerbang pagi', $schedule->notes);

        // created_by_user_id dari SESI login, bukan dari payload klien.
        $this->assertSame($admin->id, (int) $schedule->created_by_user_id);
    }

    public function test_admin_can_update_duty_schedule_teacher_and_date(): void
    {
        $admin = $this->admin();
        [$guruLama, $teacherLama] = $this->makeTeacherUser('guru_lama');
        [$guruBaru, $teacherBaru] = $this->makeTeacherUser('guru_baru');
        $schedule = $this->makeSchedule($teacherLama, '2026-09-28', $admin);

        $this->actingAs($admin)
            ->put(route('admin.duty-schedules.update', $schedule), $this->payload($teacherBaru, '2026-09-30'))
            ->assertRedirect(route('admin.duty-schedules.index'))
            ->assertSessionHasNoErrors();

        $schedule->refresh();
        $this->assertSame($teacherBaru->id, (int) $schedule->teacher_id, 'Ganti Guru (ADM-PIK-001).');
        $this->assertSame('2026-09-30', $schedule->schedule_date->toDateString(), 'Ganti Tanggal (ADM-PIK-001).');
    }

    public function test_admin_can_delete_duty_schedule(): void
    {
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_dihapus');
        $schedule = $this->makeSchedule($teacher, '2026-10-05', $admin);

        $this->actingAs($admin)
            ->delete(route('admin.duty-schedules.destroy', $schedule))
            ->assertRedirect(route('admin.duty-schedules.index'))
            ->assertSessionHas('status');

        $this->assertFalse(TeacherDutySchedule::whereKey($schedule->id)->exists());
    }

    public function test_create_and_edit_forms_render_teacher_options_from_database(): void
    {
        $admin = $this->admin();
        [, $scheduled] = $this->makeTeacherUser('guru_terjadwal');
        [, $unscheduled] = $this->makeTeacherUser('guru_belum_ada_jadwal');

        $this->makeSchedule($scheduled, '2026-09-28', $admin);

        $index = $this->actingAs($admin)->get(route('admin.duty-schedules.index'));
        $index->assertOk()
            ->assertSee($scheduled->full_name)
            ->assertSee('28/09/2026')
            // Kolom wajib index: tanggal, Guru, catatan, created by, action.
            ->assertSee('Dibuat oleh')
            ->assertSee('Catatan')
            ->assertSee($admin->username);

        // Formulir TIDAK hardcoded: guru yang belum punya jadwal pun muncul sebagai opsi.
        $this->actingAs($admin)
            ->get(route('admin.duty-schedules.create'))
            ->assertOk()
            ->assertSee($unscheduled->full_name)
            ->assertSee('name="teacher_id"', false);

        $this->actingAs($admin)
            ->get(route('admin.duty-schedules.edit', TeacherDutySchedule::first()))
            ->assertOk()
            ->assertSee($scheduled->full_name);
    }

    // =================================================================
    // 2. VALIDASI JADWAL  (PRD 02 §4c butir 2, constraint uq_duty_schedule_entry)
    // =================================================================

    public function test_duplicate_schedule_is_rejected_with_clear_message_not_raw_exception(): void
    {
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_duplikat');
        $this->makeSchedule($teacher, '2026-09-28', $admin);

        // Tidak boleh 500/exception mentah: harus kembali sebagai error validasi.
        $this->actingAs($admin)
            ->from(route('admin.duty-schedules.create'))
            ->post(route('admin.duty-schedules.store'), $this->payload($teacher, '2026-09-28'))
            ->assertRedirect(route('admin.duty-schedules.create'))
            ->assertSessionHasErrors('teacher_id');

        $this->assertSame(1, TeacherDutySchedule::count(), 'Duplikat tidak menambah baris.');
        $this->assertStringContainsString(
            'sudah memiliki jadwal piket',
            session('errors')->first('teacher_id')
        );
    }

    public function test_duplicate_schedule_is_rejected_on_update(): void
    {
        $admin = $this->admin();
        [, $teacherA] = $this->makeTeacherUser('guru_a_tujuan');
        [, $teacherB] = $this->makeTeacherUser('guru_b_tujuan');
        $occupied = $this->makeSchedule($teacherB, '2026-11-11', $admin);
        $target = $this->makeSchedule($teacherA, '2026-11-12', $admin);

        $this->actingAs($admin)
            ->put(route('admin.duty-schedules.update', $target), $this->payload($teacherB, '2026-11-11'))
            ->assertSessionHasErrors('teacher_id');

        $this->assertSame('2026-11-12', $target->fresh()->schedule_date->toDateString(), 'Data lama tidak berubah.');
        $this->assertSame($teacherA->id, (int) $target->fresh()->teacher_id);
        unset($occupied);
    }

    public function test_updating_a_schedule_to_its_own_values_is_allowed(): void
    {
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_tetap');
        $schedule = $this->makeSchedule($teacher, '2026-12-01', $admin);

        // Idempotent update (hanya catatan) tidak boleh dianggap duplikat.
        $this->actingAs($admin)
            ->put(route('admin.duty-schedules.update', $schedule), $this->payload($teacher, '2026-12-01', ['notes' => 'catatan direvisi']))
            ->assertSessionHasNoErrors();

        $this->assertSame('catatan direvisi', $schedule->fresh()->notes);
    }

    public function test_non_existent_teacher_is_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.duty-schedules.store'), [
                'teacher_id' => 999999,
                'schedule_date' => '2026-09-28',
            ])
            ->assertSessionHasErrors('teacher_id');

        $this->assertSame(0, TeacherDutySchedule::count());
    }

    public function test_teacher_without_profile_is_rejected_by_service(): void
    {
        $admin = $this->admin();
        $service = app(TeacherDutyScheduleService::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $service->create(
            ['teacher_id' => 12345, 'schedule_date' => '2026-09-28'],
            $admin,
            request()
        );
    }

    public function test_teacher_with_inactive_account_cannot_be_scheduled(): void
    {
        $admin = $this->admin();
        [$guruNonaktif, $teacherNonaktif] = $this->makeTeacherUser('guru_nonaktif', active: false);

        $this->actingAs($admin)
            ->post(route('admin.duty-schedules.store'), $this->payload($teacherNonaktif, '2026-09-28'))
            ->assertSessionHasErrors('teacher_id');

        $this->assertSame(0, TeacherDutySchedule::count(), 'PRD 02 §4c: guru harus berstatus aktif.');
        unset($guruNonaktif);
    }

    public function test_invalid_date_format_is_rejected(): void
    {
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_tanggal_salah');

        foreach (['28-09-2026', '2026/09/28', '2026-13-45', 'lusa', ''] as $bad) {
            // Bersihkan error bag agar hasil satu nilai tidak menular ke nilai lain.
            session()->forget('errors');

            $this->actingAs($admin)
                ->post(route('admin.duty-schedules.store'), $this->payload($teacher, $bad))
                ->assertSessionHasErrors('schedule_date');
        }

        $this->assertSame(0, TeacherDutySchedule::count());
    }

    public function test_impossible_calendar_date_is_rejected_by_service_not_shifted(): void
    {
        // Jalur service LANGSUNG (tanpa FormRequest). Carbon sendirian akan
        // menggeser tanggal tak nyata: 2026-02-30 menjadi 2026-03-02.
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_tanggal_mustahil');
        $service = app(TeacherDutyScheduleService::class);

        foreach (['2026-02-30', '2026-09-31', '2026-04-31', '2027-02-29'] as $impossible) {
            try {
                $service->create(
                    ['teacher_id' => $teacher->id, 'schedule_date' => $impossible],
                    $admin,
                    request()
                );
                $this->fail("Tanggal tidak nyata {$impossible} seharusnya ditolak.");
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->assertArrayHasKey('schedule_date', $e->errors());
            }
        }

        $this->assertSame(0, TeacherDutySchedule::count());

        // Tanggal nyata justru harus diterima: 2028-02-29 = hari kabisat.
        $made = $service->create(['teacher_id' => $teacher->id, 'schedule_date' => '2028-02-29'], $admin, request());
        $this->assertSame('2028-02-29', $made->schedule_date->toDateString());
    }

    public function test_mass_assignment_cannot_forge_actor_or_invent_columns(): void
    {
        $admin = $this->admin();
        $other = $this->makeUser(User::ROLE_SUPERVISOR, 'supervisor_pengirim');
        [, $teacher] = $this->makeTeacherUser('guru_mass_assign');

        $this->actingAs($admin)->post(route('admin.duty-schedules.store'), $this->payload($teacher, '2027-01-05') + [
            // Upaya pemalsuan lewat form field/hidden field (PRD 04 §3.2.1).
            'created_by_user_id' => $other->id,
            'id' => 5555,
            'is_piket' => true,
            'role' => 'piket',
        ]);

        $schedule = TeacherDutySchedule::first();
        $this->assertNotNull($schedule);
        $this->assertSame($admin->id, (int) $schedule->created_by_user_id, 'Actor tidak bisa dipalsukan dari klien.');
        $this->assertNotSame(5555, (int) $schedule->id, 'Primary key tidak bisa di-inject.');
        $this->assertFalse(Schema::hasColumn('teacher_duty_schedules', 'is_piket'));
    }

    // =================================================================
    // 3. AC-DUTY-01 — Perubahan jadwal memengaruhi status duty
    // =================================================================

    public function test_ac_duty_01_changing_schedule_changes_duty_status_for_that_date(): void
    {
        $admin = $this->admin();
        [, $fauzan] = $this->makeTeacherUser('guru_f');
        [, $budi] = $this->makeTeacherUser('guru_b');
        $service = app(TeacherDutyScheduleService::class);
        $date = '2026-09-28';

        $schedule = $this->makeSchedule($fauzan, $date, $admin);
        $this->assertTrue($service->isOnDuty($fauzan, \Carbon\Carbon::parse($date)), 'Guru terjadwal = piket.');
        $this->assertFalse($service->isOnDuty($budi, \Carbon\Carbon::parse($date)));

        // Admin mengganti Guru Piket pada tanggal yang sama.
        $this->actingAs($admin)
            ->put(route('admin.duty-schedules.update', $schedule), $this->payload($budi, $date))
            ->assertSessionHasNoErrors();

        // AC-DUTY-01: guru lama TIDAK lagi berhak, guru baru berhak.
        $this->assertFalse($service->isOnDuty($fauzan, \Carbon\Carbon::parse($date)));
        $this->assertTrue($service->isOnDuty($budi, \Carbon\Carbon::parse($date)));
    }

    public function test_duty_status_is_reevaluated_against_database_per_request(): void
    {
        // ADDENDUM §12 / PRD 02 §21: tanpa cache, WebSocket, Redis, atau Queue.
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_revalidasi');
        $service = app(TeacherDutyScheduleService::class);
        $schedule = $this->makeSchedule($teacher, '2026-09-28', $admin);

        $this->assertTrue($service->isOnDuty($teacher, \Carbon\Carbon::parse('2026-09-28')));

        $schedule->delete();

        // Request berikutnya langsung mencabut status (halaman scanner yang
        // masih terbuka akan ditolak pada scan berikutnya — AC-DUTY-03).
        $this->assertFalse($service->isOnDuty($teacher, \Carbon\Carbon::parse('2026-09-28')));
        $this->assertNull($service->dutyScheduleFor((int) $teacher->id, \Carbon\Carbon::parse('2026-09-28')));
    }

    // =================================================================
    // 4. AC-DUTY-02 — Histori attendance tidak berubah
    // =================================================================

    public function test_ac_duty_02_changing_schedule_does_not_modify_attendance_history(): void
    {
        $admin = $this->admin();
        [, $fauzan] = $this->makeTeacherUser('guru_operator_lama');
        [, $budi] = $this->makeTeacherUser('guru_operator_baru');
        $student = $this->makeStudent('262707901');

        $schedule = $this->makeSchedule($fauzan, '2026-09-28', $admin);

        // Record absensi sekolah hasil scan Fauzan (sebelum perubahan jadwal).
        $attendance = SchoolAttendance::create([
            'student_id' => $student->id,
            'attendance_date' => '2026-09-28',
            'check_in_time' => '2026-09-28 06:30:00',
            'check_out_time' => '2026-09-28 13:05:00',
            'check_in_by_teacher_id' => $fauzan->id,
            'check_out_by_teacher_id' => $fauzan->id,
            'scan_mode_in' => 'MASUK',
            'scan_mode_out' => 'PULANG',
        ]);

        $before = $attendance->toArray();
        $beforeUpdatedAt = DB::table('student_school_attendances')
            ->where('id', $attendance->id)->value('updated_at');

        // Admin mengganti & kemudian menghapus jadwal piket.
        $this->actingAs($admin)->put(
            route('admin.duty-schedules.update', $schedule),
            $this->payload($budi, '2026-09-28')
        )->assertSessionHasNoErrors();

        $this->actingAs($admin)->delete(route('admin.duty-schedules.destroy', $schedule))
            ->assertSessionHasNoErrors();

        $after = DB::table('student_school_attendances')->where('id', $attendance->id)->first();

        $this->assertNotNull($after, 'Record absensi historis tidak boleh hilang.');
        $this->assertSame($fauzan->id, (int) $after->check_in_by_teacher_id, 'Operator scan historis terkunci.');
        $this->assertSame($fauzan->id, (int) $after->check_out_by_teacher_id);
        $this->assertSame('2026-09-28 06:30:00', (string) $after->check_in_time);
        $this->assertSame('2026-09-28 13:05:00', (string) $after->check_out_time);
        $this->assertSame($beforeUpdatedAt, (string) $after->updated_at, 'updated_at tidak tersentuh.');
        $this->assertSame($before['student_id'], (int) $after->student_id);
    }

    public function test_service_never_touches_attendance_tables(): void
    {
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_tanpa_sentuh');
        $student = $this->makeStudent('262707902');

        SchoolAttendance::create([
            'student_id' => $student->id,
            'attendance_date' => '2027-02-02',
            'check_in_by_teacher_id' => $teacher->id,
            'scan_mode_in' => 'MASUK',
        ]);

        $snapshot = DB::table('student_school_attendances')->get()->map(fn ($r) => (array) $r)->all();

        $service = app(TeacherDutyScheduleService::class);
        $schedule = $service->create(['teacher_id' => $teacher->id, 'schedule_date' => '2027-02-02'], $admin, request());
        $service->update($schedule, ['teacher_id' => $teacher->id, 'schedule_date' => '2027-02-03'], $admin, request());
        $service->delete($schedule, $admin, request());

        $after = DB::table('student_school_attendances')->get()->map(fn ($r) => (array) $r)->all();
        $this->assertEquals($snapshot, $after, 'Tabel absensi sekolah tidak boleh berubah (ADDENDUM §15-§16).');
    }

    // =================================================================
    // 5. AC-DUTY-04 — Jadwal masa depan berlaku saat tanggalnya tiba
    // =================================================================

    public function test_ac_duty_04_future_schedule_becomes_effective_when_its_date_arrives(): void
    {
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_masa_depan');
        $service = app(TeacherDutyScheduleService::class);

        $this->travelTo('2026-09-27 21:00:00');

        $this->actingAs($admin)
            ->post(route('admin.duty-schedules.store'), $this->payload($teacher, '2026-10-10'))
            ->assertSessionHasNoErrors();

        // Sebelum tanggalnya tiba: belum Guru Piket.
        $this->assertFalse($service->isOnDuty($teacher));
        $this->assertSame('2026-09-27', $service->currentDutyDate());

        // Saat tanggalnya tiba: akses mengikuti jadwal terbaru tanpa perubahan kode.
        $this->travelTo('2026-10-10 06:15:00');
        $this->assertTrue($service->isOnDuty($teacher));
        $this->assertSame('2026-10-10', $service->currentDutyDate());

        // Sehari setelahnya: status piket berakhir (bukan flag permanen).
        $this->travelTo('2026-10-11 06:15:00');
        $this->assertFalse($service->isOnDuty($teacher));
    }

    public function test_running_date_comes_from_server_not_client(): void
    {
        $service = app(TeacherDutyScheduleService::class);

        $this->travelTo('2026-10-03 23:30:00');

        // Tanggal berjalan = tanggal aplikasi (Asia/Jakarta), bukan input klien.
        $this->assertSame('2026-10-03', $service->currentDutyDate());
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame(now()->toDateString(), $service->currentDutyDate());

        // Klien mengirim tanggal berbeda -> tidak mengubah penentuan "hari berjalan".
        $this->actingAs($this->admin())
            ->post(route('admin.duty-schedules.store'), [
                'teacher_id' => 1,
                'schedule_date' => '2026-10-04',
                // upaya menyetel "tanggal sistem" dari klien, harus diabaikan:
                'today' => '2026-01-01',
                'timezone' => 'UTC',
            ])
            ->assertSessionHasErrors('teacher_id');

        $this->assertSame('2026-10-03', $service->currentDutyDate());
    }

    // =================================================================
    // 6. AC-DUTY-03 / Otorisasi — teacher & supervisor ditolak 403
    // =================================================================

    public function test_teacher_is_forbidden_on_every_duty_schedule_endpoint(): void
    {
        $admin = $this->admin();
        [$guruOwner, $teacher] = $this->makeTeacherUser('guru_ingin_jadwalkan');
        $schedule = $this->makeSchedule($teacher, '2026-09-28', $admin);

        $otherGuru = $this->makeTeacherUser('guru_lain')[0];

        $endpoints = [
            ['get', route('admin.duty-schedules.index')],
            ['get', route('admin.duty-schedules.create')],
            ['post', route('admin.duty-schedules.store')],
            ['get', route('admin.duty-schedules.edit', $schedule)],
            ['put', route('admin.duty-schedules.update', $schedule)],
            ['delete', route('admin.duty-schedules.destroy', $schedule)],
        ];

        foreach ($endpoints as [$verb, $url]) {
            $this->actingAs($otherGuru)->{$verb}($url)->assertForbidden();
        }

        $this->actingAs($guruOwner)->get(route('admin.duty-schedules.index'))->assertForbidden();
        $this->assertSame(1, TeacherDutySchedule::count(), 'Tidak ada mutasi oleh guru.');
    }

    public function test_supervisor_is_forbidden_on_every_duty_schedule_endpoint(): void
    {
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_dijaga');
        $schedule = $this->makeSchedule($teacher, '2026-09-28', $admin);
        $supervisor = $this->makeUser(User::ROLE_SUPERVISOR, 'supervisor_duty');

        foreach ([
            ['get', route('admin.duty-schedules.index')],
            ['get', route('admin.duty-schedules.create')],
            ['post', route('admin.duty-schedules.store')],
            ['get', route('admin.duty-schedules.edit', $schedule)],
            ['put', route('admin.duty-schedules.update', $schedule)],
            ['delete', route('admin.duty-schedules.destroy', $schedule)],
        ] as [$verb, $url]) {
            $this->actingAs($supervisor)->{$verb}($url)->assertForbidden();
        }

        // Supervisor READ-ONLY (PRD 01 §5.4) -> tidak punya hak mutasi.
        $this->assertTrue($supervisor->can('viewAny', TeacherDutySchedule::class));
        $this->assertFalse($supervisor->can('create', TeacherDutySchedule::class));
        $this->assertFalse($supervisor->can('update', $schedule));
        $this->assertFalse($supervisor->can('delete', $schedule));
        $this->assertSame(1, TeacherDutySchedule::count());
    }

    public function test_guest_is_redirected_to_login_on_duty_schedule_routes(): void
    {
        $this->get(route('admin.duty-schedules.index'))->assertRedirect();
        $this->get(route('admin.duty-schedules.create'))->assertRedirect();
        $this->post(route('admin.duty-schedules.store'), ['teacher_id' => 1, 'schedule_date' => '2026-09-28'])
            ->assertRedirect();
        $this->assertGuest();
    }

    public function test_direct_url_access_does_not_bypass_role_middleware(): void
    {
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_direct_url');
        $schedule = $this->makeSchedule($teacher, '2026-09-28', $admin);
        $supervisor = $this->makeUser(User::ROLE_SUPERVISOR, 'supervisor_direct');

        // Akses URL langsung + upaya bypass lewat header/field.
        $this->actingAs($supervisor)
            ->withHeaders(['X-Role' => 'admin'])
            ->put(route('admin.duty-schedules.update', $schedule) . '?role=admin', $this->payload($teacher, '2026-09-29'))
            ->assertForbidden();

        $this->assertSame('2026-09-28', $schedule->fresh()->schedule_date->toDateString());
        unset($admin);
    }

    public function test_only_admin_has_duty_schedule_management_capability(): void
    {
        $admin = $this->admin();
        $service = app(TeacherDutyScheduleService::class);
        $supervisor = $this->makeUser(User::ROLE_SUPERVISOR, 'supervisor_matrix');
        [$guruOwner, $teacher] = $this->makeTeacherUser('guru_matrix');
        $teacherOther = $this->makeTeacherUser('guru_matrix_lain')[0];
        $schedule = $this->makeSchedule($teacher, '2026-09-28', $admin);

        $this->assertTrue($admin->can('create', TeacherDutySchedule::class));
        $this->assertTrue($admin->can('update', $schedule));
        $this->assertTrue($admin->can('delete', $schedule));
        $this->assertTrue($admin->can('viewAny', TeacherDutySchedule::class));

        foreach ([$supervisor, $teacherOther] as $user) {
            $this->assertFalse($user->can('create', TeacherDutySchedule::class), get_class($user) . ':' . $user->role);
        }

        // Objek lain milik Admin: tetap ditolak untuk guru (bukan jadwal dirinya).
        $this->assertFalse($teacherOther->can('update', $schedule));
        $this->assertFalse($teacherOther->can('delete', $schedule));
        $this->assertFalse($teacherOther->can('view', $schedule));

        // Objek dirinya sendiri: guru boleh MEMBACA (banner status piket,
        // PRD 01 §7.1) tetapi TIDAK boleh mengubah apa pun (ADDENDUM §9).
        $freshTeacher = $guruOwner->fresh();
        $this->assertTrue($freshTeacher->can('view', $schedule));
        $this->assertFalse($freshTeacher->can('update', $schedule));
        $this->assertFalse($freshTeacher->can('delete', $schedule));

        // Admin/TU TIDAK otomatis menjadi Guru Piket: akun admin tidak punya
        // profil guru, sehingga tidak pernah bisa dijadwalkan (ADDENDUM §8).
        $this->assertNull($admin->teacherId());
        $this->assertFalse($service->isOnDuty((int) $admin->id));
    }

    public function test_inactive_admin_is_signed_out_on_duty_schedule_route(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.duty-schedules.index'))->assertOk();

        $admin->update(['is_active' => false]);

        $this->get(route('admin.duty-schedules.index'))
            ->assertRedirect('/login')
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    // =================================================================
    // 7. CSRF + validasi view
    // =================================================================

    public function test_duty_schedule_routes_are_protected_by_csrf_middleware(): void
    {
        $expected = [
            ['POST', 'admin/duty-schedules'],
            ['PUT', 'admin/duty-schedules/{dutySchedule}'],
            ['DELETE', 'admin/duty-schedules/{dutySchedule}'],
        ];

        $router = app('router');

        // Definisi group `web` baru digabung ke Router setelah request pertama
        // melewati HTTP Kernel (sama seperti runtime produksi). Satu request
        // pancingan diperlukan agar pipeline yang diperiksa benar-benar
        // merupakan pipeline yang dieksekusi.
        $this->get('/login')->assertOk();

        foreach ($expected as [$verb, $uri]) {
            $route = collect(Route::getRoutes()->getRoutes())
                ->first(fn ($r) => $r->uri() === $uri && in_array($verb, $r->methods(), true));

            $this->assertNotNull($route, "Rute {$verb} {$uri} harus ada.");

            $resolved = $router->gatherRouteMiddleware($route);

            // CSRF (PRD 04 §5.1) — grup `web` wajib menempel pada rute mutasi.
            $this->assertContains(
                PreventRequestForgery::class,
                $resolved,
                "Rute {$verb} {$uri} wajib dilindungi CSRF (PRD 04 §5.1)."
            );

            // Lapisan otorisasi Prompt 25 harus menempel pada rute mutasi.
            $this->assertContains(
                \Illuminate\Auth\Middleware\Authenticate::class,
                $resolved,
                "Rute {$verb} {$uri} wajib middleware auth."
            );
            // Middleware role harus terspesifikasi `admin` (bukan role lain).
            $this->assertContains(
                \App\Http\Middleware\EnsureUserHasRole::class . ':admin',
                $resolved,
                "Rute {$verb} {$uri} wajib dibatasi role:admin."
            );
        }
    }

    public function test_duty_schedule_forms_include_csrf_token(): void
    {
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_csrf');

        $create = $this->actingAs($admin)->get(route('admin.duty-schedules.create'))->assertOk();
        $token = session()->token();
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $create->assertSee('name="_token"', false)->assertSee($token, false);

        $edit = $this->actingAs($admin)
            ->get(route('admin.duty-schedules.edit', $this->makeSchedule($teacher, '2026-09-28', $admin)))
            ->assertOk();
        $edit->assertSee('name="_token"', false)
            ->assertSee('name="_method" value="PUT"', false);

        $this->actingAs($admin)->get(route('admin.duty-schedules.index'))
            ->assertSee('name="_token"', false);
    }

    public function test_validation_errors_are_rendered_escaped(): void
    {
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_xss');
        $this->makeSchedule($teacher, '2026-09-28', $admin);

        $xss = '<script>alert(1)</script>';

        // Duplikat -> validasi gagal; old input + error ter-flash ke session.
        $this->actingAs($admin)
            ->from(route('admin.duty-schedules.create'))
            ->post(route('admin.duty-schedules.store'), $this->payload($teacher, '2026-09-28', ['notes' => $xss]))
            ->assertRedirect(route('admin.duty-schedules.create'))
            ->assertSessionHasErrors('teacher_id');

        // Render ulang form: old input berisi payload XSS.
        $this->actingAs($admin)
            ->get(route('admin.duty-schedules.create'))
            ->assertOk()
            // Blade {{ }} meng-escape: tag mentah tidak pernah keluar ke HTML.
            ->assertDontSee($xss, false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    // =================================================================
    // 8. AUDIT  (ADM-PIK-004 / ADDENDUM §17 / PRD 04 §9.1)
    // =================================================================

    public function test_audit_create_is_recorded_with_actor_date_teacher_and_timestamp(): void
    {
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_audit_create');

        $this->actingAs($admin)->post(route('admin.duty-schedules.store'), $this->payload($teacher, '2026-09-28'));

        $log = AuditLog::where('action', AuditLog::ACTION_DUTY_SCHEDULE_CREATE)->first();
        $this->assertNotNull($log, 'DUTY_SCHEDULE_CREATE wajib tercatat.');
        $this->assertSame($admin->id, (int) $log->user_id, 'actor');
        $this->assertSame(User::ROLE_ADMIN, $log->role);
        $this->assertSame(TeacherDutySchedule::class, $log->auditable_type);
        $this->assertNotNull($log->auditable_id);
        $this->assertSame('2026-09-28', $log->new_values['schedule_date'], 'tanggal jadwal');
        $this->assertSame($teacher->id, (int) $log->new_values['guru_baru_id'], 'guru baru');
        $this->assertSame($teacher->full_name, $log->new_values['guru_baru_nama']);
        $this->assertNotNull($log->created_at, 'timestamp server');
        $this->assertTrue(
            $log->created_at->between(now()->subSeconds(30), now()->addSeconds(30)),
            'Timestamp audit memakai waktu server.'
        );
    }

    public function test_audit_update_records_old_and_new_teacher(): void
    {
        $admin = $this->admin();
        [, $lama] = $this->makeTeacherUser('guru_audit_lama');
        [, $baru] = $this->makeTeacherUser('guru_audit_baru');
        $schedule = $this->makeSchedule($lama, '2026-09-28', $admin);

        $this->actingAs($admin)->put(route('admin.duty-schedules.update', $schedule), $this->payload($baru, '2026-09-29'));

        $log = AuditLog::where('action', AuditLog::ACTION_DUTY_SCHEDULE_UPDATE)->first();
        $this->assertNotNull($log, 'DUTY_SCHEDULE_UPDATE wajib tercatat.');
        $this->assertSame($admin->id, (int) $log->user_id);
        $this->assertSame($lama->id, (int) $log->old_values['guru_lama_id'], 'guru lama');
        $this->assertSame($lama->full_name, $log->old_values['guru_lama_nama']);
        $this->assertSame('2026-09-28', $log->old_values['schedule_date_lama']);
        $this->assertSame($baru->id, (int) $log->new_values['guru_baru_id'], 'guru baru');
        $this->assertSame($baru->full_name, $log->new_values['guru_baru_nama']);
        $this->assertSame('2026-09-29', $log->new_values['schedule_date_baru']);
        $this->assertEquals($schedule->id, (int) $log->auditable_id);
    }

    public function test_audit_delete_is_recorded_and_targets_the_deleted_row(): void
    {
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_audit_hapus');
        $schedule = $this->makeSchedule($teacher, '2026-09-28', $admin);

        $this->actingAs($admin)->delete(route('admin.duty-schedules.destroy', $schedule));

        $log = AuditLog::where('action', AuditLog::ACTION_DUTY_SCHEDULE_DELETE)->first();
        $this->assertNotNull($log, 'DUTY_SCHEDULE_DELETE wajib tercatat.');
        $this->assertSame($admin->id, (int) $log->user_id);
        $this->assertSame(TeacherDutySchedule::class, $log->auditable_type);
        $this->assertEquals($schedule->id, (int) $log->auditable_id);
        $this->assertSame($teacher->id, (int) $log->old_values['guru_lama_id']);
        $this->assertSame('2026-09-28', $log->old_values['schedule_date']);
        $this->assertNull($log->new_values);
    }

    public function test_failed_mutations_do_not_write_audit_entries(): void
    {
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_audit_gagal');
        $this->makeSchedule($teacher, '2026-09-28', $admin);

        $before = AuditLog::whereIn('action', [
            AuditLog::ACTION_DUTY_SCHEDULE_CREATE,
            AuditLog::ACTION_DUTY_SCHEDULE_UPDATE,
            AuditLog::ACTION_DUTY_SCHEDULE_DELETE,
        ])->count();

        // Duplikat -> gagal, tidak boleh menambah jejak perubahan.
        $this->actingAs($admin)->post(route('admin.duty-schedules.store'), $this->payload($teacher, '2026-09-28'))
            ->assertSessionHasErrors('teacher_id');

        $this->assertSame($before, AuditLog::whereIn('action', [
            AuditLog::ACTION_DUTY_SCHEDULE_CREATE,
            AuditLog::ACTION_DUTY_SCHEDULE_UPDATE,
            AuditLog::ACTION_DUTY_SCHEDULE_DELETE,
        ])->count(), 'Audit hanya untuk perubahan yang benar-benar terjadi.');
    }

    public function test_transaction_rolls_back_row_when_audit_cannot_be_written(): void
    {
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_rollback');

        // Simulasi kegagalan penulisan audit pada langkah akhir transaksi.
        // Sengaja TIDAK memakai DDL (DROP/TRUNCATE) karena DDL memicu implicit
        // commit MySQL dan merusak isolasi transaksi RefreshDatabase.
        // Flag dinonaktifkan di `finally` agar event listener tidak bocor ke test lain.
        $failAudit = true;
        AuditLog::creating(function () use (&$failAudit) {
            if ($failAudit) {
                throw new \RuntimeException('audit backend gagal');
            }
        });

        try {
            // Dipanggil lewat service (bukan HTTP) agar exception propagates,
            // tidak dikonversi handler menjadi response 500.
            app(TeacherDutyScheduleService::class)->create(
                ['teacher_id' => $teacher->id, 'schedule_date' => '2026-09-28'],
                $admin,
                request()
            );
            $this->fail('Diharapkan terjadi kegagalan pada jalur audit.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('audit backend gagal', $e->getMessage());
            $this->assertSame(
                0,
                TeacherDutySchedule::whereDate('schedule_date', '2026-09-28')->count(),
                'Perubahan di-rollback bila audit gagal (ADM-PIK-004 atomik).'
            );
        } finally {
            $failAudit = false;
            $this->assertSame(
                0,
                AuditLog::where('action', AuditLog::ACTION_DUTY_SCHEDULE_CREATE)->count(),
                'Tidak ada jejak audit untuk perubahan yang di-rollback.'
            );
        }
    }

    public function test_duty_audit_contains_no_sensitive_data(): void
    {
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_sensitive');
        $schedule = $this->makeSchedule($teacher, '2026-09-28', $admin);

        $this->actingAs($admin)->put(route('admin.duty-schedules.update', $schedule), $this->payload($teacher, '2026-09-30', ['notes' => 'piket sore']));
        $this->actingAs($admin)->delete(route('admin.duty-schedules.destroy', $schedule));

        $forbidden = ['password', 'password_hash', 'remember_token', 'session_id', 'api_key', 'token', 'secret'];

        $payloads = AuditLog::where('action', 'like', 'DUTY_SCHEDULE_%')
            ->get()
            ->map(fn ($l) => array_merge((array) $l->old_values, (array) $l->new_values));

        $this->assertNotEmpty($payloads);
        foreach ($payloads as $payload) {
            foreach ($forbidden as $key) {
                $this->assertArrayNotHasKey($key, $payload, 'PRD 04 §9.2: data sensitif dilarang di audit.');
            }
        }
    }

    public function test_login_audit_from_prompt_24_is_still_written(): void
    {
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_audit_login');

        // Audit login Prompt 24 dihasilkan oleh request login sungguhan.
        $this->post('/login', ['username' => 'admin_tu_duty', 'password' => 'salahsekali'])
            ->assertSessionHasErrors('username');
        $this->post('/login', ['username' => 'admin_tu_duty', 'password' => 'rahasia123'])
            ->assertRedirect();
        $this->post('/logout')->assertRedirect(route('login'));

        // Mutasi jadwal piket (ADJUSTMENT C) tetap tercatat.
        $this->actingAs($admin->fresh())
            ->post(route('admin.duty-schedules.store'), $this->payload($teacher, '2026-12-24'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::ACTION_LOGIN_SUCCESS]);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::ACTION_LOGIN_FAILED]);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::ACTION_LOGOUT]);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::ACTION_DUTY_SCHEDULE_CREATE]);
    }

    // =================================================================
    // 9. GUARD ANTI-REGRESI LARANGAN PRD (ADDENDUM §8/§10, §24)
    // =================================================================

    public function test_no_is_piket_column_exists_anywhere(): void
    {
        foreach (['users', 'teachers', 'teacher_duty_schedules', 'student_school_attendances'] as $table) {
            $this->assertFalse(
                Schema::hasColumn($table, 'is_piket'),
                "Kolom {$table}.is_piket dilarang ada (ADDENDUM §8/§10)."
            );
        }

        $columns = Schema::getColumnListing('teacher_duty_schedules');
        $this->assertNotContains('is_piket', $columns);
        $this->assertNotContains('role', $columns);
    }

    public function test_official_roles_remain_exactly_three(): void
    {
        $this->assertSame(['admin', 'teacher', 'supervisor'], User::OFFICIAL_ROLES);
        $this->assertFalse(User::isValidRole('piket'));
        $this->assertFalse(User::isValidRole('guru_piket'));
        $this->assertFalse(User::isValidRole('PIKET'));

        // Tidak ada konstanta role piket di model User.
        $reflection = new \ReflectionClass(User::class);
        foreach (array_keys($reflection->getConstants()) as $name) {
            $this->assertStringNotContainsString('PIKET', strtoupper($name));
        }
    }

    public function test_source_code_contains_no_piket_role_or_flag_identifier(): void
    {
        // ADDENDUM §10: dilarang users.is_piket = true, if user == "...",
        // if today == "Monday".
        //
        // Diperiksa pada TOKEN (bukan substring) karena kata "piket" adalah
        // bahasa Indonesia yang SAH muncul pada pesan UI/service (mis.
        // "Tanggal piket wajib diisi."). Yang dilarang adalah identifier,
        // nilai role, dan kolom bertema piket.
        $forbiddenLiterals = [
            'piket', 'guru_piket', 'is_piket', 'role_piket', 'piket_admin',
            'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
            'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu',
            'Budi Santoso', // nama guru demo tidak boleh jadi kondisi kode
        ];

        $hits = [];

        foreach ($this->phpSourceFiles() as $path) {
            foreach (token_get_all(file_get_contents($path)) as $token) {
                if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    continue; // komentar = dokumentasi larangan, boleh
                }

                $value = is_array($token) ? $token[1] : $token;

                if (is_array($token) && in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_STRING, T_VARIABLE, T_INLINE_HTML], true)) {
                    $bare = trim($value, "\"'` ");

                    if (in_array(strtolower($bare), array_map('strtolower', $forbiddenLiterals), true)) {
                        $hits[] = basename($path) . ': ' . $value;
                    }
                }
            }
        }

        $this->assertSame([], $hits, 'ADDENDUM §10 dilanggar: ' . implode(' | ', $hits));
    }

    /** @return array<int, string> */
    private function phpSourceFiles(): array
    {
        $files = [];

        foreach ([base_path('app'), base_path('routes')] as $dir) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    public function test_blade_views_do_not_implement_authorization_or_hardcoded_guru(): void
    {
        $views = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path('resources/views'))
        );

        $checked = 0;
        foreach ($views as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            // Buang komentar Blade.
            $code = preg_replace('/\{\{--.*?--\}\}/s', '', $contents);

            $this->assertStringNotContainsStringIgnoringCase(
                'is_piket',
                $code,
                'Blade tidak boleh mengenal flag piket: ' . $file->getFilename()
            );

            // Tidak boleh ada keputusan akses berbasis NAMA guru/hari di view.
            $this->assertDoesNotMatchRegularExpression(
                '/\b(if|@if|@can)\s*\([^)]*(Monday|Sunday|Fauzan)/i',
                $code,
                'Akses berbasis nama hari/nama guru dilarang di view: ' . $file->getFilename()
            );

            $checked++;
        }

        $this->assertGreaterThan(0, $checked);
    }

    public function test_duty_schedule_feature_is_admin_area_only(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($r) => $r->uri())
            ->filter(fn ($uri) => str_contains($uri, 'duty'))
            ->values()
            ->all();

        $this->assertNotEmpty($uris, 'Rute duty-schedules harus terdaftar.');
        foreach ($uris as $uri) {
            $this->assertStringStartsWith('admin/', $uri, "Rute jadwal piket harus di bawah /admin: {$uri}");
        }

        // PRD: scanner bukan bagian tahap ini.
        $this->assertFalse(Route::has('guru.school-attendance-scanner'));
        foreach (Route::getRoutes() as $route) {
            $this->assertStringNotContainsString('scanner', $route->uri());
            $this->assertStringNotContainsString('barcode', $route->uri());
        }
    }

    public function test_duty_schedule_unique_constraint_is_still_enforced_at_database(): void
    {
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_db_constraint');

        // PRD 04 §19.A: uq_duty_schedule_entry harus tetap ada di MySQL.
        $indexes = collect(DB::select('SHOW INDEX FROM teacher_duty_schedules'))
            ->where('Key_name', 'uq_duty_schedule_entry')
            ->sortBy('Seq_in_index');

        $this->assertNotEmpty($indexes->all(), 'Constraint UNIQUE(schedule_date, teacher_id) wajib ada.');
        $this->assertSame(
            ['schedule_date', 'teacher_id'],
            $indexes->pluck('Column_name')->all()
        );
        $this->assertSame(0, (int) $indexes->first()->Non_unique, 'Indeks harus UNIQUE.');

        // Lewati service -> constraint DB harus tetap menahan duplikat.
        DB::table('teacher_duty_schedules')->insert([
            'schedule_date' => '2026-09-28',
            'teacher_id' => $teacher->id,
            'created_by_user_id' => $admin->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('teacher_duty_schedules')->insert([
            'schedule_date' => '2026-09-28',
            'teacher_id' => $teacher->id,
            'created_by_user_id' => $admin->id,
        ]);
    }

    // =================================================================
    // 10. SERVICE CONTRACTS
    // =================================================================

    public function test_service_reports_duty_schedule_row_for_a_date(): void
    {
        $admin = $this->admin();
        [, $teacher] = $this->makeTeacherUser('guru_kontrak');
        $made = $this->makeSchedule($teacher, '2026-09-28', $admin);
        $service = app(TeacherDutyScheduleService::class);

        $found = $service->dutyScheduleFor((int) $teacher->id, \Carbon\Carbon::parse('2026-09-28'));
        $this->assertNotNull($found);
        $this->assertEquals($made->id, $found->id);
        $this->assertNull($service->dutyScheduleFor((int) $teacher->id, \Carbon\Carbon::parse('2026-09-29')));
        $this->assertNull($service->dutyScheduleFor(0));
        $this->assertNull($service->dutyScheduleFor(-5));
    }

    public function test_index_can_be_filtered_by_date_and_teacher(): void
    {
        $admin = $this->admin();
        [, $teacherA] = $this->makeTeacherUser('guru_filter_a');
        [, $teacherB] = $this->makeTeacherUser('guru_filter_b');

        // Penanda unik pada kolom `notes` (hanya tampil di baris tabel,
        // tidak di dropdown filter) agar hasil filter benar-benar teruji.
        $this->makeSchedule($teacherA, '2026-09-28', $admin, 'CATATAN-A-UNIK');
        $this->makeSchedule($teacherB, '2026-09-29', $admin, 'CATATAN-B-UNIK');

        $this->actingAs($admin)->get(route('admin.duty-schedules.index', ['date' => '2026-09-28']))
            ->assertOk()
            ->assertSee('CATATAN-A-UNIK')
            ->assertDontSee('CATATAN-B-UNIK');

        $this->actingAs($admin)->get(route('admin.duty-schedules.index', ['teacher_id' => $teacherB->id]))
            ->assertOk()
            ->assertSee('CATATAN-B-UNIK')
            ->assertDontSee('CATATAN-A-UNIK');
    }
}
