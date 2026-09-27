<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Teacher;
use App\Models\TeacherDutySchedule;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * TeacherDutyScheduleService — CRUD Jadwal Guru Piket (ADDENDUM §8–§18).
 *
 * Acuan wajib:
 * - PRD 02 §4c `TeacherDutyScheduleService` (alur eksekusi butir 1–5).
 * - PRD 01 §6.11 ADM-PIK-001/002/004 (CRUD + minimal Tanggal/Guru + audit).
 * - PRD 04 §9.1 (matriks aksi DUTY_SCHEDULE_CREATE/UPDATE/DELETE) dan
 *   §9.2 butir 2 (payload audit wajib bebas data sensitif).
 *
 * Aturan keras PRD yang ditegakkan di sini:
 * - "Guru Piket" BUKAN role. Satu-satunya sumber kebenaran adalah baris pada
 *   tabel `teacher_duty_schedules` untuk tanggal berjalan (ADDENDUM §8).
 * - TIDAK ada `users.is_piket`, TIDAK ada nama guru hardcoded, TIDAK ada
 *   nama hari hardcoded (ADDENDUM §10 — PRD 01 §13.8).
 * - Perubahan jadwal TIDAK BOLEH menyentuh data absensi historis
 *   (PRD 02 §4c butir 5, ADDENDUM §15–§16). Karena itu service ini hanya
 *   menulis pada `teacher_duty_schedules` + `audit_logs`.
 * - Otorisasi TIDAK dievaluasi dari input klien. Hak ubah ditegakkan oleh
 *   TeacherDutySchedulePolicy (Prompt 25) di controller; actor diambil dari
 *   sesi login, bukan dari field form.
 *
 * Tanggal berjalan selalu memakai waktu SERVER/APLIKASI (config('app.timezone')
 * = Asia/Jakarta) melalui now() — tidak pernah jam browser klien.
 */
class TeacherDutyScheduleService
{
    /**
     * Batas panjang input TEKNIS untuk `notes` (kolom MySQL TEXT = 65535 byte;
     * 10.000 karakter utf8mb4 tetap di bawah kapasitasnya).
     * PRD tidak menetapkan batas niaga — angka ini murni pelindung overflow kolom.
     */
    public const MAX_NOTES_LENGTH = 10000;

    /**
     * Buat jadwal piket baru.
     *
     * @param  array{teacher_id: int|string, schedule_date: string, notes?: string|null}  $data
     *
     * @throws ValidationException bila guru tidak valid atau jadwal duplikat
     *                             (pesan manusiawi, bukan exception mentah)
     */
    public function create(array $data, User $actor, Request $request): TeacherDutySchedule
    {
        $teacher = $this->resolveTeacher($data['teacher_id'] ?? null);
        $date = $this->resolveDate($data['schedule_date'] ?? null);
        $notes = $this->resolveNotes($data['notes'] ?? null);

        // Pre-check duplikasi UNIQUE(schedule_date, teacher_id) = uq_duty_schedule_entry.
        // Pengecekan lebih dulu menghasilkan pesan yang jelas; constraint tetap
        // menjadi penjaga terakhir (race condition ditangani di bawah).
        $this->ensureNotDuplicate($date, (int) $teacher->getKey());

        try {
            return DB::transaction(function () use ($teacher, $date, $notes, $actor, $request) {
                $schedule = TeacherDutySchedule::create([
                    'schedule_date' => $date->toDateString(),
                    'teacher_id' => (int) $teacher->getKey(),
                    // Actor dari sesi login (server-side), bukan input klien.
                    'created_by_user_id' => (int) $actor->getKey(),
                    'notes' => $notes,
                ]);

                AuditService::logDataChange(
                    actor: $actor,
                    action: AuditLog::ACTION_DUTY_SCHEDULE_CREATE,
                    target: $schedule,
                    oldValues: null,
                    newValues: [
                        'schedule_date' => $date->toDateString(),
                        'guru_baru_id' => (int) $teacher->getKey(),
                        'guru_baru_nama' => $teacher->full_name,
                        'notes' => $notes,
                    ],
                    request: $request,
                );

                return $schedule;
            });
        } catch (UniqueConstraintViolationException $e) {
            throw $this->duplicateException($teacher, $date, $e);
        }
    }

    /**
     * Ubah jadwal (ganti guru dan/atau ganti tanggal) — ADM-PIK-001.
     *
     * @param  array{teacher_id: int|string, schedule_date: string, notes?: string|null}  $data
     */
    public function update(TeacherDutySchedule $schedule, array $data, User $actor, Request $request): TeacherDutySchedule
    {
        $teacher = $this->resolveTeacher($data['teacher_id'] ?? null);
        $date = $this->resolveDate($data['schedule_date'] ?? null);
        $notes = $this->resolveNotes($data['notes'] ?? null);

        $this->ensureNotDuplicate($date, (int) $teacher->getKey(), $schedule);

        // Snapshot BEFORE perubahan (kepentingan audit "guru lama").
        $previousTeacher = $schedule->teacher;
        $previousDate = Carbon::parse($schedule->schedule_date)->toDateString();
        $previousNotes = $schedule->notes;

        try {
            return DB::transaction(function () use ($schedule, $teacher, $date, $notes, $previousTeacher, $previousDate, $previousNotes, $actor, $request) {
                $schedule->update([
                    'schedule_date' => $date->toDateString(),
                    'teacher_id' => (int) $teacher->getKey(),
                    'notes' => $notes,
                    // created_by_user_id SENGAJA tidak diubah: kolom ini adalah
                    // penanda pembuat jadwal (PRD 02 §10.15b), bukan pengubah terakhir.
                ]);

                AuditService::logDataChange(
                    actor: $actor,
                    action: AuditLog::ACTION_DUTY_SCHEDULE_UPDATE,
                    target: $schedule,
                    oldValues: [
                        'schedule_date_lama' => $previousDate,
                        'guru_lama_id' => $previousTeacher ? (int) $previousTeacher->getKey() : null,
                        'guru_lama_nama' => $previousTeacher?->full_name,
                        'notes_lama' => $previousNotes,
                    ],
                    newValues: [
                        'schedule_date_baru' => $date->toDateString(),
                        'guru_baru_id' => (int) $teacher->getKey(),
                        'guru_baru_nama' => $teacher->full_name,
                        'notes_baru' => $notes,
                    ],
                    request: $request,
                );

                return $schedule;
            });
        } catch (UniqueConstraintViolationException $e) {
            throw $this->duplicateException($teacher, $date, $e);
        }
    }

    /**
     * Hapus jadwal piket.
     *
     * Hanya baris jadwal yang dihapus. Record absensi yang sudah memakai guru
     * ini sebagai operator TIDAK disentuh (PRD 02 §4c butir 5; FK
     * student_school_attendances.check_in_by_teacher_id juga ON DELETE RESTRICT
     * sehingga DB ikut melindungi histori).
     */
    public function delete(TeacherDutySchedule $schedule, User $actor, Request $request): void
    {
        DB::transaction(function () use ($schedule, $actor, $request) {
            $teacher = $schedule->teacher;

            $snapshot = [
                'schedule_date' => Carbon::parse($schedule->schedule_date)->toDateString(),
                'guru_lama_id' => (int) $schedule->teacher_id,
                'guru_lama_nama' => $teacher?->full_name,
                'notes' => $schedule->notes,
                'created_by_user_id' => (int) $schedule->created_by_user_id,
            ];

            $scheduleId = (int) $schedule->getKey();
            $schedule->delete();

            AuditService::logDataChange(
                actor: $actor,
                action: AuditLog::ACTION_DUTY_SCHEDULE_DELETE,
                // Target disebut sebagai string class + id eksplisit karena model
                // sudah terhapus (tetap merujuk baris yang sama di audit trail).
                target: TeacherDutySchedule::class,
                oldValues: $snapshot,
                newValues: null,
                request: $request,
                auditableId: $scheduleId,
            );
        });
    }

    // -----------------------------------------------------------------
    // Baca status piket (dipakai dashboard & nanti oleh scanner per request)
    // -----------------------------------------------------------------

    /**
     * Apakah guru ini terjadwal piket pada tanggal berjalan?
     *
     * PRD 04 §3.2.4 / ADDENDUM §12: status piket dinilai ULANG terhadap
     * database pada setiap request, memakai tanggal SERVER.
     *
     * @param  Teacher|int  $teacher  id atau model Guru
     * @param  DateTimeInterface|null  $date  default = tanggal server hari ini
     */
    public function isOnDuty(Teacher|int $teacher, ?DateTimeInterface $date = null): bool
    {
        $teacherId = $teacher instanceof Teacher ? (int) $teacher->getKey() : (int) $teacher;

        return $this->dutyScheduleFor($teacherId, $date) !== null;
    }

    /**
     * Baris jadwal piket milik guru pada tanggal tertentu (null bila tidak ada).
     */
    public function dutyScheduleFor(int $teacherId, ?DateTimeInterface $date = null): ?TeacherDutySchedule
    {
        if ($teacherId <= 0) {
            return null;
        }

        $dateString = $this->serverDate($date);

        return TeacherDutySchedule::query()
            ->where('teacher_id', $teacherId)
            ->whereDate('schedule_date', $dateString)
            ->first();
    }

    /**
     * Tanggal berjalan versi SERVER (Asia/Jakarta), format Y-m-d.
     * Tidak pernah membaca jam dari browser/klien (PRD 02 §10.15b).
     */
    public function currentDutyDate(): string
    {
        return $this->serverDate(null);
    }

    private function serverDate(?DateTimeInterface $date): string
    {
        return $date === null
            ? now()->toDateString()
            : Carbon::instance(Carbon::parse($date))->toDateString();
    }

    // -----------------------------------------------------------------
    // Validasi (PRD 02 §4c butir 2)
    // -----------------------------------------------------------------

    /**
     * Pastikan guru yang dipilih BENAR-BENAR memiliki profil Teacher yang sah
     * dan akunnya aktif. Fail closed terhadap id abstrak/injektif.
     */
    private function resolveTeacher(mixed $teacherId): Teacher
    {
        $id = is_numeric($teacherId) ? (int) $teacherId : 0;

        if ($id <= 0) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Guru wajib dipilih.',
            ]);
        }

        $teacher = Teacher::query()->with('user')->find($id);

        if (! $teacher) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Guru yang dipilih tidak ditemukan pada master data Guru.',
            ]);
        }

        if (! $teacher->user) {
            throw ValidationException::withMessages([
                'teacher_id' => "Profil '{$teacher->full_name}' belum terhubung ke akun pengguna, sehingga tidak dapat dijadwalkan piket.",
            ]);
        }

        // "Guru harus berstatus aktif" (PRD 02 §4c butir 2). Satu-satunya kolom
        // keaktifan yang ada di skema final adalah users.is_active (PRD 02 §10.1);
        // employment_status BUKAN penanda non-aktif (GTT/HONORER tetap guru aktif),
        // sehingga tidak difilter — PRD tidak mensyaratkannya.
        if (! $teacher->user->is_active) {
            throw ValidationException::withMessages([
                'teacher_id' => "Akun guru '{$teacher->full_name}' sedang tidak aktif.",
            ]);
        }

        return $teacher;
    }

    private function resolveDate(mixed $value): CarbonInterface
    {
        if (! is_string($value) || trim($value) === '') {
            throw ValidationException::withMessages([
                'schedule_date' => 'Tanggal piket wajib diisi.',
            ]);
        }

        $value = trim($value);

        // Format harus KANONIK Y-m-d. Carbon::createFromFormat sendirian akan
        // MENGGESER tanggal tidak nyata (2026-02-30 -> 2026-03-02), jadi
        // diformat ulang lalu dibandingkan dan divalidasi dengan checkdate().
        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value)->startOfDay();
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'schedule_date' => 'Format tanggal tidak valid. Gunakan YYYY-MM-DD.',
            ]);
        }

        if ($date->toDateString() !== $value
            || ! checkdate((int) $date->month, (int) $date->day, (int) $date->year)) {
            throw ValidationException::withMessages([
                'schedule_date' => 'Tanggal piket tidak nyata. Gunakan tanggal kalender yang valid (YYYY-MM-DD).',
            ]);
        }

        // PRD TIDAK melarang jadwal di masa lalu (PRD 02 §4c butir 5 bahkan
        // membahas perubahan jadwal masa lalu), jadi TIDAK ada aturan "min date".
        return $date;
    }

    private function resolveNotes(mixed $value): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        if (! is_string($value)) {
            throw ValidationException::withMessages([
                'notes' => 'Catatan tidak valid.',
            ]);
        }

        $notes = trim($value);

        if (mb_strlen($notes) > self::MAX_NOTES_LENGTH) {
            throw ValidationException::withMessages([
                'notes' => 'Catatan melebihi ' . self::MAX_NOTES_LENGTH . ' karakter.',
            ]);
        }

        return $notes;
    }

    /**
     * Duplikasi terhadap UNIQUE(schedule_date, teacher_id) `uq_duty_schedule_entry`.
     * Pesan dibuat eksplisit agar user tidak melihat exception mentah.
     */
    private function ensureNotDuplicate(CarbonInterface $date, int $teacherId, ?TeacherDutySchedule $ignore = null): void
    {
        $query = TeacherDutySchedule::query()
            ->whereDate('schedule_date', $date->toDateString())
            ->where('teacher_id', $teacherId);

        if ($ignore !== null) {
            $query->whereKeyNot($ignore->getKey());
        }

        if ($query->exists()) {
            $teacher = Teacher::find($teacherId);

            throw ValidationException::withMessages([
                'teacher_id' => 'Guru ' . ($teacher?->full_name ?? "#{$teacherId}")
                    . ' sudah memiliki jadwal piket pada tanggal ' . $date->toDateString()
                    . '. Pilih tanggal atau guru lain.',
            ]);
        }
    }

    private function duplicateException(Teacher $teacher, CarbonInterface $date, Throwable $e): ValidationException
    {
        return ValidationException::withMessages([
            'teacher_id' => 'Guru ' . $teacher->full_name
                . ' sudah memiliki jadwal piket pada tanggal ' . $date->toDateString()
                . '. Perubahan tidak disimpan.',
        ])->previous($e);
    }
}
