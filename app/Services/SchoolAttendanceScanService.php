<?php

namespace App\Services;

use App\Exceptions\ScanRejectionException;
use App\Models\AuditLog;
use App\Models\SchoolAttendance;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherDutySchedule;
use App\Models\User;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

/**
 * SchoolAttendanceScanService — pemproses scan barcode absensi MASUK/PULANG
 * sekolah oleh Guru Piket (PRD 02 §4b alur butir 1–8, PRD 01 §13 & §7.4
 * GUR-PIK-001..008, ADDENDUM §1–§7 & §11–§16, AC-SCAN-01..06).
 *
 * ## Server adalah SATU-SATUNYA sumber kebenaran
 * | Field           | Sumber                                             |
 * |-----------------|-----------------------------------------------------|
 * | operator        | sesi login -> users -> teachers (FK)                |
 * | tanggal & waktu | clock server (config('app.timezone') = Asia/Jakarta)|
 * | status piket    | tabel teacher_duty_schedules, dinilai ULANG tiap    |
 * |                 | request (ADDENDUM §12)                              |
 *
 * Klien hanya boleh mengirim DUA hal: `barcode` dan `mode`. Nilai apa pun yang
 * dikirim klien untuk `teacher_id`, `operator_id`, `check_in_by_teacher_id`,
 * tanggal, timestamp, `role`, atau status piket SELALU diabaikan — field-field
 * itu tidak pernah dibaca oleh kelas ini (PRD 04 §3.2.4 "nilai dari klien
 * diabaikan").
 *
 * ## Aturan final PRD yang ditegakkan di sini
 * - Mode EKSPISIT `MASUK`/`PULANG` (ADDENDUM §1, ADR 18). DILARANG auto-detect
 *   "scan pertama = MASUK, scan kedua = PULANG".
 * - `PULANG` tanpa catatan `MASUK` pada tanggal berjalan DITOLAK
 *   `[FINAL — KEPUTUSAN A1]`: tidak membuat check_in palsu, tidak auto-create
 *   baris baru. Jalur resmi = koreksi Admin/TU (`AttendanceCorrectionService`,
 *   audit `SCHOOL_ATT_CORRECT`).
 * - Anti-duplikasi berlapis (ADDENDUM §3 & §5): row lock -> application guard
 *   -> `UNIQUE(student_id, attendance_date)` = `uq_student_school_att_entry`
 *   sebagai penjaga terakhir.
 * - Perubahan jadwal piket TIDAK BOLEH menyentuh histori absensi
 *   (ADDENDUM §15–§16): kelas ini tidak pernah menulis `teacher_duty_schedules`.
 * - DILARANG: role "piket", `users.is_piket`, RFID/NFC/chip/GPS,
 *   WebSocket/queue/Redis.
 *
 * Teks pesan mengikuti PRD FINAL (PRD 01 §7.4 GUR-PIK-001/004/005/006/007,
 * PRD 04 §3.2.4 & §16.5, PRD 05 §5.10/§5.10b) — hasil keputusan bersama saat
 * inspeksi Adjustment D.
 */
class SchoolAttendanceScanService
{
    /** Mode scanner eksplisit — enum yang sama dengan CHECK constraint DB (ADR 18). */
    public const MODE_MASUK = 'MASUK';

    public const MODE_PULANG = 'PULANG';

    /** @var array<int, string> */
    public const MODES = [self::MODE_MASUK, self::MODE_PULANG];

    /** Batas teknis panjang barcode hasil decode (kolom students.barcode_code = varchar 50). */
    public const MAX_BARCODE_LENGTH = 50;

    /**
     * Outcome SCHOOL_ATT_SCAN (PRD 04 §9.1: event-nya adalah "Guru Piket
     * MELAKUKAN scan barcode siswa", jadi aksi scan dicatat lengkap dengan
     * HASILNYA - sama seperti AUTH_LOGIN_SUCCESS / AUTH_LOGIN_FAILED yang sama
     *-sama mencatat percobaan yang gagal).
     *
     * outcome selalu disertai attendance_changed agar mustahil membaca sebuah
     * baris audit sebagai perubahan data kalau memang tidak ada perubahan.
     */
    public const OUTCOME_SCAN_SUCCESS = 'SCAN_SUCCESS';

    public const OUTCOME_DUPLICATE_MASUK = 'DUPLICATE_MASUK';

    public const OUTCOME_DUPLICATE_PULANG = 'DUPLICATE_PULANG';

    public const OUTCOME_DUPLICATE_RACE = 'DUPLICATE_RACE_DATABASE_CONSTRAINT';

    public const OUTCOME_BARCODE_NOT_FOUND = 'BARCODE_NOT_FOUND';

    public const OUTCOME_STUDENT_NOT_ACTIVE = 'STUDENT_NOT_ACTIVE';

    public const OUTCOME_PULANG_WITHOUT_MASUK = 'PULANG_WITHOUT_MASUK';

    public const OUTCOME_MODE_INVALID = 'MODE_INVALID';

    public const OUTCOME_BARCODE_EMPTY = 'BARCODE_EMPTY';

    public const OUTCOME_BARCODE_TOO_LONG = 'BARCODE_TOO_LONG';

    /**
     * Penanda sesi: tanggal SERVER pada saat guru TERBUKTI diberi akses scanner.
     * Dipakai hanya untuk MEMILIH PESAN 403 yang benar menurut PRD (bukan untuk
     * otorisasi — otoritas selalu dibaca dari database), dan dibersihkan begitu
     * akses ditolak.
     */
    public const SESSION_DUTY_KEY = 'scanner_duty_date';

    /**
     * Penanda sesi: mode scanner EKSPISIT terakhir yang dipakai guru pada scan
     * BERHASIL (ADDENDUM §7 — mode bertahan antar-scan tanpa logout). Ditulis
     * HANYA oleh server (redirect store) dan dibaca ulang oleh controller untuk
     * menandai tombol aktif; nilainya tetap dinormalisasi terhadap MODES.
     */
    public const SESSION_ACTIVE_MODE_KEY = 'scanner_active_mode';

    /** Akses awal oleh guru yang memang tidak terjadwal (PRD 01 GUR-PIK-001, AC-SCAN-05). */
    public const MESSAGE_NOT_ON_DUTY = 'Anda hari ini bukan Guru Piket.';

    /** Jadwal dicabut/dipindah saat halaman masih terbuka (PRD 04 §3.2.4 & §16.5, GUR-PIK-007). */
    public const MESSAGE_DUTY_CHANGED = 'Jadwal piket Anda telah berubah. Anda tidak lagi bertugas sebagai Guru Piket pada tanggal ini.';

    /** Akun guru tidak punya profil Teacher valid (fail closed, PRD 02 §10.1). */
    public const MESSAGE_NO_PROFILE = 'Profil Guru untuk akun Anda belum terdaftar. Hubungi Admin/TU.';

    /** `[FINAL — KEPUTUSAN A1]` (PRD 05 §5.10, PRD 01 GUR-PIK-006 & AC-SCAN-06). */
    public const MESSAGE_NO_CHECK_IN = 'Belum ada catatan MASUK hari ini. Hubungi Admin/TU untuk koreksi.';

    public function __construct(
        private readonly TeacherDutyScheduleService $duty,
    ) {}

    // -----------------------------------------------------------------
    // 1. Otorisasi scanner per request (PRD 02 §4b butir 1)
    // -----------------------------------------------------------------

    /**
     * Guru yang SEDANG INI terbukti bertugas pada tanggal server, atau null.
     *
     * Tidak melempar apa pun: dipakai middleware untuk membangun halaman
     * terkunci (PRD 01 GUR-PIK-001) dengan pesan yang tepat. Status piket
     * dibaca ULANG dari database pada SETIAP pemanggilan — tidak ada caching,
     * tidak ada flag role, tidak ada JWT/cookie khusus (ADDENDUM §12).
     */
    public function findOnDutyTeacher(Request $request, User $user): ?Teacher
    {
        $evaluation = $this->evaluateDuty($request, $user);

        return $evaluation['teacher'];
    }

    /**
     * Pesan penolakan yang benar untuk kondisi saat ini.
     *
     * PRD membedakan dua kasus 403:
     * - akses awal oleh guru non-piket -> MESSAGE_NOT_ON_DUTY;
     * - guru yang tadi terbukti bertugas lalu jadwalnya dicabut/dipindah
     *   sementara halaman terbuka -> MESSAGE_DUTY_CHANGED (ADDENDUM §13–§14).
     *
     * Pembeda = tanggal server ketika akses terakhir kali DIBERIKAN (bukan
     * tanggal yang dikirim klien). Tidak ada jejak sesi yang tersisa saat
     * penolakan terjadi.
     */
    public function denialMessage(Request $request, User $user): string
    {
        return $this->evaluateDuty($request, $user)['message'] ?? self::MESSAGE_NOT_ON_DUTY;
    }

    /**
     * Sama seperti findOnDutyTeacher() tapi MENOLAK dengan HTTP 403.
     *
     * Dipanggil ulang di dalam scan() dan oleh middleware: dua-duanya wajib
     * lewat jalur ini agar tidak ada satu pun cara memanggil service scan
     * tanpa revalidasi jadwal (defense in depth, PRD 04 §3.2.4).
     *
     * @throws AccessDeniedHttpException
     */
    public function authorizeOrDeny(Request $request, User $user): Teacher
    {
        $evaluation = $this->evaluateDuty($request, $user);

        if ($evaluation['teacher'] === null) {
            throw new AccessDeniedHttpException($evaluation['message']);
        }

        return $evaluation['teacher'];
    }

    /**
     * @return array{teacher: ?Teacher, message: ?string}
     */
    private function evaluateDuty(Request $request, User $user): array
    {
        $teacherId = $user->teacherId();

        // Deny by default: akun tanpa profil guru (FK users -> teachers tidak
        // ada) tidak pernah dapat akses, termasuk akun ber-role teacher yang
        // profilnya belum dibuat.
        if ($teacherId === null) {
            $this->forgetDutySession($request);

            return ['teacher' => null, 'message' => self::MESSAGE_NO_PROFILE];
        }

        $teacher = Teacher::query()->find($teacherId);

        if ($teacher === null) {
            $this->forgetDutySession($request);

            return ['teacher' => null, 'message' => self::MESSAGE_NO_PROFILE];
        }

        $schedule = $this->duty->dutyScheduleFor($teacherId);

        if ($schedule !== null) {
            $request->session()->put(self::SESSION_DUTY_KEY, $this->duty->currentDutyDate());

            return ['teacher' => $teacher, 'message' => null];
        }

        // Tidak terjadwal hari ini -> tolak. Penanda sesi SENGAJA tidak dihapus:
        // ADDENDUM §13-§14 menghendaki pesan "Jadwal piket Anda telah berubah."
        // tetap dipakai pada scan-scan BERIKUTNYA selama halaman masih terbuka
        // pada hari server yang sama. Penanda akan tertimpa sendiri begitu akses
        // diberikan kembali, dan tidak berlaku lagi bila tanggal server berganti
        // (dibandingkan dengan currentDutyDate() di denialMessageFor()).
        return ['teacher' => null, 'message' => $this->denialMessageFor($request)];
    }

    private function denialMessageFor(Request $request): string
    {
        $grantedOn = $request->session()->get(self::SESSION_DUTY_KEY);

        $isSameServerDay = is_string($grantedOn) && $grantedOn === $this->duty->currentDutyDate();

        return $isSameServerDay ? self::MESSAGE_DUTY_CHANGED : self::MESSAGE_NOT_ON_DUTY;
    }

    private function forgetDutySession(Request $request): void
    {
        $request->session()->forget(self::SESSION_DUTY_KEY);
    }

    /** Tanggal berjalan SERVER (Asia/Jakarta) — bukan jam perangkat klien (ADDENDUM §2). */
    public function serverDate(): string
    {
        return $this->duty->currentDutyDate();
    }

    /** Baris jadwal piket pada tanggal berjalan (null bila tidak bertugas). */
    public function dutyScheduleFor(Teacher|int $teacher, ?DateTimeInterface $date = null): ?TeacherDutySchedule
    {
        $teacherId = $teacher instanceof Teacher ? (int) $teacher->getKey() : (int) $teacher;

        return $this->duty->dutyScheduleFor($teacherId, $date);
    }

    // -----------------------------------------------------------------
    // 2. Proses scan (PRD 02 §4b butir 2–8)
    // -----------------------------------------------------------------

    /**
     * Satu kali pemrosesan scan.
     *
     * @param  string|null  $rawBarcode  Nilai hasil decode barcode (input klien).
     * @param  string|null  $rawMode     'MASUK' | 'PULANG' (dipilih eksplisit oleh guru).
     *
     * @throws AccessDeniedHttpException  HTTP 403 — status piket tidak sah pada request ini.
     * @throws ValidationException        HTTP 422 / redirect-back — penolakan bisnis yang aman.
     */
    public function scan(Request $request, User $user, ?string $rawBarcode, ?string $rawMode): SchoolAttendance
    {
        // (1) Revalidasi otoritas pada SETIAP request scan (bukan hanya saat
        // halaman dibuka): status piket + profil guru, dibaca ulang dari DB.
        //
        // 403 di sini SENGAJA berada di luar blok try di bawah: permintaan yang
        // ditolak SEBELUM event scan Guru Piket benar-benar terjadi tidak
        // menghasilkan audit SCHOOL_ATT_SCAN apa pun (PRD 02 §4b butir 1
        // "jangan simpan apa pun" + PRD 04 §9.1).
        $teacher = $this->authorizeOrDeny($request, $user);

        try {
            // (2) Mode tervalidasi eksplisit; (3) siswa resolusi dari barcode.
            $mode = $this->resolveMode($rawMode);
            $barcode = $this->resolveBarcode($rawBarcode);

            // Tanggal & waktu SELALU dari server. Klien tidak pernah ditanya, dan
            // field tanggal/timestamp mana pun yang dikirimnya tidak dibaca.
            $dateString = $this->duty->currentDutyDate();
            $moment = now();

            $student = $this->resolveStudent($barcode);

            try {
                // (4) Satu transaksi untuk "ambil atau isi" record
                // (student_id, attendance_date).
                return DB::transaction(function () use ($request, $user, $teacher, $student, $mode, $dateString, $moment): SchoolAttendance {
                    $existing = $this->lockTodaysRecord((int) $student->getKey(), $dateString);

                    return $mode === self::MODE_MASUK
                        ? $this->recordCheckIn($existing, $student, $teacher, $user, $request, $dateString, $moment)
                        : $this->recordCheckOut($existing, $student, $teacher, $user, $request, $dateString, $moment);
                });
            } catch (UniqueConstraintViolationException $e) {
                // (7) Penjaga terakhir: dua request MASUK hampir bersamaan untuk
                // siswa & tanggal sama -> hanya satu baris yang boleh ada.
                throw $this->raceDuplicateException($student, $dateString, $e);
            }
        } catch (ScanRejectionException $e) {
            // (8) Audit PRD 04 §9.1 dengan outcome eksplisit.
            //
            // Ditulis DI SINI (setelah DB::transaction() di atas di-rollback),
            // bukan di dalam transaksi: kalau di dalam, baris audit-nya ikut
            // ter-rollback sehingga event scan yang benar-benar terjadi hilang
            // dari jejak. audit_logs bersifat append-only dan tidak pernah
            // menjadi bagian transaksi absensi (PRD 04 §9.2).
            //
            // Exception tetap dilempar ulang -> perilaku HTTP tidak berubah
            // (422 redirect-back dengan pesan $errors, bukan 500).
            $this->auditRejectedScan($user, $teacher, $request, $e);

            throw $e;
        }
    }

    // -----------------------------------------------------------------
    // Mode MASUK (GUR-PIK-004 / AC-SCAN-01 / AC-SCAN-03)
    // -----------------------------------------------------------------

    private function recordCheckIn(
        ?SchoolAttendance $existing,
        Student $student,
        Teacher $teacher,
        User $user,
        Request $request,
        string $dateString,
        DateTimeInterface $moment,
    ): SchoolAttendance {
        if ($existing !== null && $existing->check_in_time !== null) {
            // ADDENDUM §3: record baru TIDAK dibuat. Jam yang sudah tercatat
            // dikembalikan agar guru tahu siswa ini sudah absen (AC-SCAN-03).
            //
            // Ditolak -> transaksi di-rollback, lalu scan() mengaudit outcome ini
            // di luar transaksi (attendance_changed = false).
            throw ScanRejectionException::reject(
                outcome: self::OUTCOME_DUPLICATE_MASUK,
                field: 'check_in_time',
                message: 'ABSENSI SUDAH TERCATAT. Sudah tercatat masuk pukul '
                    . $existing->check_in_time->format('H:i') . '.',
                scanContext: [
                    'attendance_id' => (int) $existing->getKey(),
                    'waktu_tercatat' => $existing->check_in_time->format('Y-m-d H:i:s'),
                ],
            );
        }

        if ($existing !== null) {
            // Baris hari ini sudah ada tetapi belum punya jam masuk (mis. hasil
            // koreksi Admin/TU). ISI check_in pada baris yang sama — jangan
            // membuat baris kedua (menabrak UNIQUE).
            $existing->update([
                'check_in_time' => $moment,
                'check_in_by_teacher_id' => (int) $teacher->getKey(),
                'scan_mode_in' => self::MODE_MASUK,
            ]);

            $this->auditScan($user, $existing, self::MODE_MASUK, $teacher, $student, $request);

            return $existing->refresh();
        }

        $attendance = SchoolAttendance::create([
            'student_id' => (int) $student->getKey(),
            'attendance_date' => $dateString,
            'check_in_time' => $moment,
            // Operator dari sesi login (server-side), BUKAN dari payload klien.
            'check_in_by_teacher_id' => (int) $teacher->getKey(),
            'scan_mode_in' => self::MODE_MASUK,
        ]);

        $this->auditScan($user, $attendance, self::MODE_MASUK, $teacher, $student, $request);

        return $attendance;
    }

    // -----------------------------------------------------------------
    // Mode PULANG (GUR-PIK-005/006 / AC-SCAN-02/04/06)
    // -----------------------------------------------------------------

    private function recordCheckOut(
        ?SchoolAttendance $existing,
        Student $student,
        Teacher $teacher,
        User $user,
        Request $request,
        string $dateString,
        DateTimeInterface $moment,
    ): SchoolAttendance {
        // `[FINAL — KEPUTUSAN A1]`: tanpa catatan MASUK hari ini scan PULANG
        // DITOLAK. Dilarang membuat check_in palsu / auto-create record MASUK,
        // karena itu jalur ini TIDAK PERNAH memanggil SchoolAttendance::create().
        if ($existing === null || $existing->check_in_time === null) {
            throw ScanRejectionException::reject(
                outcome: self::OUTCOME_PULANG_WITHOUT_MASUK,
                field: 'check_out_time',
                message: self::MESSAGE_NO_CHECK_IN,
                scanContext: $existing === null
                    ? ['alasan' => 'no_attendance_row_today']
                    : ['attendance_id' => (int) $existing->getKey(), 'alasan' => 'check_in_time_null'],
            );
        }

        if ($existing->check_out_time !== null) {
            // ADDENDUM §5: update kedua ditolak, nilai lama tetap utuh.
            throw ScanRejectionException::reject(
                outcome: self::OUTCOME_DUPLICATE_PULANG,
                field: 'check_out_time',
                message: 'ABSENSI PULANG SUDAH TERCATAT. Sudah tercatat pulang pukul '
                    . $existing->check_out_time->format('H:i') . '.',
                scanContext: [
                    'attendance_id' => (int) $existing->getKey(),
                    'waktu_tercatat' => $existing->check_out_time->format('Y-m-d H:i:s'),
                ],
            );
        }

        $existing->update([
            'check_out_time' => $moment,
            'check_out_by_teacher_id' => (int) $teacher->getKey(),
            'scan_mode_out' => self::MODE_PULANG,
        ]);

        $this->auditScan($user, $existing, self::MODE_PULANG, $teacher, $student, $request);

        return $existing->refresh();
    }

    // -----------------------------------------------------------------
    // Resolusi & validasi input (PRD 04 §8 — input tidak terpercaya)
    // -----------------------------------------------------------------

    /**
     * Kunci baris `(student_id, attendance_date)` dengan `SELECT ... FOR UPDATE`.
     *
     * Lock ini mem-serialisasi dua request PULANG paralel pada baris yang sama
     * (yang kedua melihat `check_out_time` sudah terisi) dan membuat PULANG
     * menunggu MASUK yang sedang berjalan. Pada driver tanpa dukungan lock
     * (session/array) statement ini tidak efektif tetapi tetap benar — UNIQUE
     * constraint tetap penjaga terakhir.
     */
    private function lockTodaysRecord(int $studentId, string $dateString): ?SchoolAttendance
    {
        return SchoolAttendance::query()
            ->where('student_id', $studentId)
            ->whereDate('attendance_date', $dateString)
            ->lockForUpdate()
            ->first();
    }

    /** Mode harus EKSPISIT — tidak disimpulkan dari jumlah/kondisi scan. */
    private function resolveMode(?string $value): string
    {
        $mode = is_string($value) ? strtoupper(trim($value)) : '';

        if (! in_array($mode, self::MODES, true)) {
            throw ScanRejectionException::reject(
                outcome: self::OUTCOME_MODE_INVALID,
                field: 'mode',
                message: 'Mode scan tidak valid. Pilih MASUK atau PULANG.',
                // Mode yang ditolak TIDAK dikembalikan sebagai nilai: cukup
                // panjangnya, agar payload klien tidak ikut tersimpan.
                scanContext: ['mode_received_length' => mb_strlen($mode)],
            );
        }

        return $mode;
    }

    private function resolveBarcode(?string $value): string
    {
        $barcode = is_string($value) ? trim($value) : '';

        if ($barcode === '') {
            throw ScanRejectionException::reject(
                outcome: self::OUTCOME_BARCODE_EMPTY,
                field: 'barcode',
                message: 'Barcode wajib diisi.',
            );
        }

        if (mb_strlen($barcode) > self::MAX_BARCODE_LENGTH) {
            throw ScanRejectionException::reject(
                outcome: self::OUTCOME_BARCODE_TOO_LONG,
                field: 'barcode',
                message: 'Barcode tidak valid.',
                // Panjang saja, bukan isinya.
                scanContext: ['barcode_length' => mb_strlen($barcode)],
            );
        }

        return $barcode;
    }

    /**
     * Siswa harus ADA pada master data (pencocokan PERSIS
     * `students.barcode_code`) dan berstatus AKTIF. Fail closed.
     */
    private function resolveStudent(string $barcode): Student
    {
        $student = Student::query()
            ->where('barcode_code', $barcode)
            ->first();

        if ($student === null) {
            // Barcode TIDAK disimpan di audit: yang bocor di sini justru bisa
            // menjadi nomor kartu siswa orang lain (PRD 04 §9.2).
            throw ScanRejectionException::reject(
                outcome: self::OUTCOME_BARCODE_NOT_FOUND,
                field: 'barcode',
                message: 'Barcode tidak ditemukan pada data siswa.',
            );
        }

        if (! $this->isStudentActive($student)) {
            throw ScanRejectionException::reject(
                outcome: self::OUTCOME_STUDENT_NOT_ACTIVE,
                field: 'barcode',
                message: 'Siswa ' . $student->full_name
                    . ' tidak berstatus aktif (status: ' . ($student->status ?: '-') . '). Scan ditolak.',
                scanContext: [
                    'student_id' => (int) $student->getKey(),
                    'nis' => $student->nis,
                    'status_siswa' => (string) ($student->status ?: '-'),
                ],
            );
        }

        return $student;
    }

    /** Hanya AKTIF yang boleh di-scan (PRD 02 §10.6: AKTIF/LULUS/MUTASI/KELUAR). */
    private function isStudentActive(Student $student): bool
    {
        return strtoupper(trim((string) $student->status)) === 'AKTIF';
    }

    /**
     * Duplikat yang LOLOS application guard lalu ditahan UNIQUE constraint
     * (interleaving sungguhan) -> pesan aplikasi yang aman, BUKAN HTTP 500.
     *
     * Catatan API (bug nyata yang pernah ditemukan di jalur jadwal piket):
     * `ValidationException` TIDAK memiliki method `previous()`, jadi cause
     * dicatat ke log aplikasi (PRD 04 §10 / OWASP A09) tanpa nilai sensitif.
     */
    private function raceDuplicateException(Student $student, string $dateString, Throwable $e): ScanRejectionException
    {
        Log::warning('School attendance duplicate caught by database constraint', [
            'reason' => 'unique_constraint_raced_past_guard',
            'student_id' => (int) $student->getKey(),
            'attendance_date' => $dateString,
            'cause' => $e::class,
        ]);

        return ScanRejectionException::reject(
            outcome: self::OUTCOME_DUPLICATE_RACE,
            field: 'check_in_time',
            message: 'ABSENSI SUDAH TERCATAT. Perubahan tidak disimpan.',
            scanContext: ['student_id' => (int) $student->getKey()],
        );
    }

    // -----------------------------------------------------------------
    // Audit (PRD 02 §4b butir 8, PRD 04 §9.1, §9.2)
    // -----------------------------------------------------------------

    /**
     * Aksi `SCHOOL_ATT_SCAN` — scan masuk/pulang siswa oleh Guru Piket.
     *
     * PRD 04 §9.1 mendefinisikan event-nya sebagai TINDAKAN
     * "Guru Piket melakukan scan barcode siswa", BUKAN sebagai hasil mutasi.
     * Karena itu setiap scan dari guru yang sah secara otorisasi menghasilkan
     * SATU baris SCHOOL_ATT_SCAN - termasuk yang ditolak - dan kolom `outcome`
     * membedakan hasilnya (pola yang sama dipakai AUTH_LOGIN_SUCCESS /
     * AUTH_LOGIN_FAILED pada tabel PRD yang sama).
     *
     * `attendance_changed` dibuat eksplisit agar tidak ada kemungkinan salah
     * baca: outcome gagal selalu false, jadi audit gagal tidak pernah dapat
     * disalahartikan sebagai absensi sukses (PRD 04 §9.2).
     *
     * Payload dibatasi data non-sensitif: TANPA barcode mentah, token, cookie,
     * kata sandi, atau nilai konfigurasi.
     */
    private function auditScan(
        User $actor,
        SchoolAttendance $attendance,
        string $mode,
        Teacher $teacher,
        Student $student,
        Request $request,
    ): void {
        $time = $mode === self::MODE_MASUK ? $attendance->check_in_time : $attendance->check_out_time;

        AuditService::logDataChange(
            actor: $actor,
            action: AuditLog::ACTION_SCHOOL_ATT_SCAN,
            target: $attendance,
            oldValues: null,
            newValues: [
                'outcome' => self::OUTCOME_SCAN_SUCCESS,
                'attendance_changed' => true,
                'attendance_id' => (int) $attendance->getKey(),
                'student_id' => (int) $student->getKey(),
                'student_nis' => $student->nis,
                'student_nama' => $student->full_name,
                'attendance_date' => $attendance->attendance_date instanceof CarbonInterface
                    ? $attendance->attendance_date->toDateString()
                    : (string) $attendance->attendance_date,
                'mode' => $mode,
                'waktu_scan' => $time?->format('Y-m-d H:i:s'),
                'operator_teacher_id' => (int) $teacher->getKey(),
                'operator_nama' => $teacher->full_name,
            ],
            request: $request,
        );
    }

    /**
     * Audit atas scan yang DITOLAK server (PRD 04 §9.1, outcome eksplisit).
     *
     * Dipanggil scan() SETELAH DB::transaction() selesai di-rollback, sehingga:
     *  - baris audit tetap ada (append-only) walau mutasi absensi dibatalkan;
     *  - tidak ada satupun kolom attendance yang tersimpan dari scan gagal;
     *  - respons HTTP tetap penolakan validasi biasa (bukan 500).
     *
     * `entity` Sengaja tidak menunjuk baris absensi bila barisnya tidak jadi
     * dibuat/diubah (auditable_id = null); penelusuran cukup lewat actor +
     * waktu + student_id/NIS pada payload bila siswa terselesaikan.
     */
    private function auditRejectedScan(
        User $actor,
        Teacher $teacher,
        Request $request,
        ScanRejectionException $rejection,
    ): void {
        $this->writeRejectedAudit(
            $actor,
            $teacher,
            $request,
            $rejection->scanOutcome,
            $rejection->scanContext,
        );
    }

    /**
     * Audit percobaan scan yang bentuk inputnya ditolak validasi FORM
     * (mode invalid / barcode kosong / barcode terlalu panjang).
     *
     * Kenapa perlu jalur sendiri? FormRequest dieksekusi SEBELUM service, jadi
     * penolakan bentuk input tidak pernah menyentuh scan(). Peristiwa scan-nya
     * tetap nyata: guru yang sudah sah sebagai Guru Piket menekan MASUK/PULANG
     * lalu mengirim kartu (PRD 04 §9.1 mencatat AKSI scan-nya).
     *
     * Gerbang otorisasi tetap dijaga: status piket dibaca ULANG dari database,
     * dan bila guru ternyata tidak bertugas maka TIDAK ada audit scan apa pun —
     * persis seperti penolakan 403 di middleware (permintaan ditolak sebelum
     * event scan Guru Piket terjadi).
     */
    public function auditRejectedAttempt(Request $request, ?User $user, string $outcome, array $context = []): void
    {
        if ($user === null) {
            return;
        }

        $teacher = $this->findOnDutyTeacher($request, $user);

        if ($teacher === null) {
            return;
        }

        $this->writeRejectedAudit($user, $teacher, $request, $outcome, $context);
    }

    /**
     * Satu tempat penulisan audit gagal: barcode/value klien apa pun dibuang
     * sebelum ditulis (PRD 04 §9.2), dan attendance_changed selalu false.
     *
     * @param  array<string, mixed>  $context
     */
    private function writeRejectedAudit(
        User $actor,
        Teacher $teacher,
        Request $request,
        string $outcome,
        array $context,
    ): void {
        // Barcode tidak pernah masuk payload, apa pun isinya (lapisan kedua
        // setelah resolveStudent()/resolveBarcode() memang tidak mengirimnya).
        unset($context['barcode'], $context['barcode_code'], $context['mode_received']);

        AuditService::logDataChange(
            actor: $actor,
            action: AuditLog::ACTION_SCHOOL_ATT_SCAN,
            target: SchoolAttendance::class,
            oldValues: null,
            newValues: [
                'outcome' => $outcome,
                'attendance_changed' => false,
                'attendance_date' => $this->duty->currentDutyDate(),
                'operator_teacher_id' => (int) $teacher->getKey(),
                'operator_nama' => $teacher->full_name,
            ] + $context,
            request: $request,
        );
    }
}
