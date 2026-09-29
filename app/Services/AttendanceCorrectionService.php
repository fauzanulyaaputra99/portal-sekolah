<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\SchoolAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * AttendanceCorrectionService — koreksi manual absensi masuk/pulang SEKOLAH
 * oleh Admin/TU (PRD 02 §5 alur butir 1–4, PRD 01 §6.9 ADM-ABS-003 & §13.7,
 * PRD 04 §9.1 aksi `SCHOOL_ATT_CORRECT`, PRD 05 §5.10).
 *
 * Kenapa jalur ini ada: scan PULANG tanpa catatan MASUK DITOLAK server
 * `[FINAL — KEPUTUSAN A1]` dan sistem dilarang membuat check_in palsu
 * (PRD 02 §4b butir 6). Satu-satunya penyelesaian resmi kasus tersebut adalah
 * koreksi Admin/TU di sini — dengan alasan wajib (min. 10 karakter) + audit.
 *
 * Aturan yang ditegakkan:
 * - Actor dari sesi login (`corrected_by_user_id`), BUKAN dari payload klien.
 * - Alasan minimal 10 karakter (PRD 01 §13.7 / PRD 02 §5 butir 2).
 * - Waktu koreksi memakai clock SERVER (Asia/Jakarta); nilai jam yang
 *   diketik manual diparsing pada timezone aplikasi, bukan timezone klien.
 * - `attendance_date` TIDAK boleh diubah: satu baris = satu siswa satu hari
 *   (`uq_student_school_att_entry`), dan memindah tanggal = membuat record
 *   lain + merusak telusur histori.
 * - Integritas baris: PULANG tidak boleh ada tanpa MASUK pada baris yang sama
 *   (mirror aturan scanner), dan mengosongkan MASUK saat PULANG terisi ditolak.
 * - Kolom operator (`check_in_by_teacher_id`/`check_out_by_teacher_id`) &
 *   `scan_mode_*` ikut diselaraskan saat nilai jam diisi/dikosongkan, supaya
 *   jejak "siapa mencatat" tidak pernah tertinggal menunjuk operator lama.
 * - `is_corrected` / `corrected_by_user_id` / `corrected_at` = penanda
 *   administratif; nilai LAMA disimpan utuh di `audit_logs.old_values` sehingga
 *   hasil scan asli tetap dapat ditelusuri (PRD 01 §13.7 "histori koreksi
 *   tercatat lengkap").
 * - Mutasi + audit dalam SATU transaksi: kegagalan tulis audit membatalkan
 *   perubahan (PRD 04 §9.2 append-only).
 */
class AttendanceCorrectionService
{
    /** Alasan koreksi minimal 10 karakter (PRD 01 §6.9 ADM-ABS-003). */
    public const MIN_REASON_LENGTH = 10;

    /** Batas teknis panjang alasan (kolom MySQL TEXT). */
    public const MAX_REASON_LENGTH = 5000;

    /** Nilai khusus UI: "isi dengan waktu server sekarang". */
    public const TIME_NOW = 'NOW';

    /**
     * Koreksi satu record absensi masuk/pulang sekolah.
     *
     * @param  array<string, mixed>  $data  keys: check_in_time, check_out_time, reason
     *
     * @throws ValidationException  pesan bisnis yang manusiawi (HTTP 422 / redirect-back)
     */
    public function correct(SchoolAttendance $attendance, array $data, User $actor, Request $request): SchoolAttendance
    {
        $reason = $this->resolveReason($data['reason'] ?? null);

        $old = [
            'check_in_time' => $attendance->check_in_time,
            'check_out_time' => $attendance->check_out_time,
            'check_in_by_teacher_id' => $attendance->check_in_by_teacher_id,
            'check_out_by_teacher_id' => $attendance->check_out_by_teacher_id,
            'scan_mode_in' => $attendance->scan_mode_in,
            'scan_mode_out' => $attendance->scan_mode_out,
            'is_corrected' => $attendance->is_corrected,
        ];

        $newIn = $this->resolveTime($data['check_in_time'] ?? null, 'check_in_time');
        $newOut = $this->resolveTime($data['check_out_time'] ?? null, 'check_out_time');

        // Field yang tidak dikirim = tidak diubah; nilai kosong = dikosongkan.
        $changesIn = array_key_exists('check_in_time', $data);
        $changesOut = array_key_exists('check_out_time', $data);

        $finalIn = $changesIn ? $newIn : $attendance->check_in_time;
        $finalOut = $changesOut ? $newOut : $attendance->check_out_time;

        $this->assertCoherent($attendance, $finalIn, $finalOut, $changesIn, $changesOut);

        if (! $this->isDifferent($attendance, $finalIn, $finalOut)) {
            throw ValidationException::withMessages([
                'check_in_time' => 'Tidak ada perubahan yang tersimpan. Nilai jam masih sama.',
            ]);
        }

        $moment = now();

        return DB::transaction(function () use ($attendance, $actor, $request, $reason, $old, $finalIn, $finalOut, $changesIn, $changesOut, $moment): SchoolAttendance {
            $attributes = [
                'is_corrected' => true,
                'corrected_by_user_id' => (int) $actor->getKey(),
                'corrected_at' => $moment,
                // Alasan ikut tersimpan pada record (bukan hanya di audit) agar
                // Admin/TU yang membaca ulang barisnya tahu kenapa diubah.
                'correction_reason' => $reason,
            ];

            if ($changesIn) {
                $attributes['check_in_time'] = $finalIn;
                // Operator scan historis TIDAK diubah: actor koreksi adalah akun
                // Admin/TU (bukan guru), dan check_in_by_teacher_id adalah FK ke
                // `teachers`. Jejak "siapa yang memindai" dibiarkan utuh sesuai
                // PRD 01 §13.7 (histori terkunci) + ADDENDUM §15-§16; status
                // terkoreksi diketahui dari is_corrected + audit SCHOOL_ATT_CORRECT.
                // FK hanya dibersihkan bila nilai jam sengaja dikosongkan.
                if ($finalIn === null && $attendance->check_in_time !== null) {
                    $attributes['check_in_by_teacher_id'] = null;
                    $attributes['scan_mode_in'] = null;
                }
            }

            if ($changesOut) {
                $attributes['check_out_time'] = $finalOut;
                if ($finalOut === null && $attendance->check_out_time !== null) {
                    $attributes['check_out_by_teacher_id'] = null;
                    $attributes['scan_mode_out'] = null;
                }
            }

            $attendance->update($attributes);

            AuditService::logDataChange(
                actor: $actor,
                action: AuditLog::ACTION_SCHOOL_ATT_CORRECT,
                target: $attendance,
                oldValues: $this->presentable($old),
                newValues: $this->presentable([
                    'attendance_id' => (int) $attendance->getKey(),
                    'student_id' => (int) $attendance->student_id,
                    'attendance_date' => $attendance->attendance_date?->toDateString(),
                    'check_in_time' => $finalIn,
                    'check_out_time' => $finalOut,
                    'is_corrected' => true,
                    'corrected_by_user_id' => (int) $actor->getKey(),
                    'corrected_at' => $moment,
                    'alasan_koreksi' => $reason,
                ]),
                request: $request,
            );

            return $attendance->refresh();
        });
    }

    // -----------------------------------------------------------------
    // Validasi
    // -----------------------------------------------------------------

    private function resolveReason(mixed $value): string
    {
        $reason = is_string($value) ? trim($value) : '';

        if (mb_strlen($reason) < self::MIN_REASON_LENGTH) {
            throw ValidationException::withMessages([
                'reason' => 'Alasan koreksi wajib diisi minimal ' . self::MIN_REASON_LENGTH . ' karakter.',
            ]);
        }

        if (mb_strlen($reason) > self::MAX_REASON_LENGTH) {
            throw ValidationException::withMessages([
                'reason' => 'Alasan koreksi melebihi ' . self::MAX_REASON_LENGTH . ' karakter.',
            ]);
        }

        return $reason;
    }

    /**
     * @return Carbon|null  null berarti nilai dikosongkan
     *
     * @throws Throwable  saat format/nilai tanggal tidak valid
     */
    private function resolveTime(mixed $value, string $field): ?Carbon
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        if (is_string($value) && strtoupper(trim($value)) === self::TIME_NOW) {
            return now();
        }

        if (! is_string($value)) {
            throw ValidationException::withMessages([
                $field => 'Format waktu tidak valid.',
            ]);
        }

        $raw = trim($value);

        // Terima format input HTML (datetime-local) dan format db; interpreted
        // pada timezone APLIKASI (Asia/Jakarta), bukan timezone klien/browser.
        foreach (['Y-m-d\TH:i', 'Y-m-d H:i', 'Y-m-d\TH:i:s'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $raw, config('app.timezone'));
            } catch (Throwable) {
                continue;
            }

            // Tolak tanggal tak nyata (Carbon menggeser, bukan menolak).
            $canonical = $parsed->format(str_contains($format, ':s') ? 'Y-m-d H:i:s' : (str_contains($format, 'T') ? 'Y-m-d\TH:i' : 'Y-m-d H:i'));

            if ($canonical !== $raw) {
                throw ValidationException::withMessages([
                    $field => 'Waktu tidak valid. Gunakan format YYYY-MM-DD HH:MM.',
                ]);
            }

            return $parsed;
        }

        throw ValidationException::withMessages([
            $field => 'Format waktu tidak valid. Gunakan YYYY-MM-DD HH:MM (contoh: ' . now()->format('Y-m-d') . ' 07:05).',
        ]);
    }

    /**
     * Integritas baris pasca-koreksi (mirror aturan scanner, PRD 05 §5.10
     * Keputusan A1): PULANG tidak boleh berdiri tanpa MASUK.
     *
     * @param  Carbon|null  $finalIn
     * @param  Carbon|null  $finalOut
     */
    private function assertCoherent(
        SchoolAttendance $attendance,
        ?Carbon $finalIn,
        ?Carbon $finalOut,
        bool $changesIn,
        bool $changesOut,
    ): void {
        if ($finalOut !== null && $finalIn === null) {
            throw ValidationException::withMessages([
                'check_in_time' => 'Catatan PULANG tidak boleh ada tanpa catatan MASUK pada hari yang sama. Isi jam MASUK lebih dulu.',
            ]);
        }

        // Urutan waktu masuk <= pulang (PRD 02 §10.15).
        if ($finalIn !== null && $finalOut !== null && $finalOut->lt($finalIn)) {
            throw ValidationException::withMessages([
                'check_out_time' => 'Waktu pulang tidak boleh lebih awal daripada waktu masuk.',
            ]);
        }

        // Mengosongkan MASUK padahal PULANG adalah hasil SCAN asli: nilai scan
        // tidak boleh hilang diam-diam (PRD 01 §13.7, ADDENDUM §15-§16).
        if ($changesIn && $finalIn === null && $attendance->check_out_time !== null) {
            throw ValidationException::withMessages([
                'check_in_time' => 'Kosongkan catatan PULANG lebih dulu sebelum mengosongkan catatan MASUK.',
            ]);
        }
    }

    /**
     * @param  Carbon|null  $finalIn
     * @param  Carbon|null  $finalOut
     */
    private function isDifferent(SchoolAttendance $attendance, ?Carbon $finalIn, ?Carbon $finalOut): bool
    {
        $oldIn = $attendance->check_in_time;
        $oldOut = $attendance->check_out_time;

        $inChanged = ! $this->sameInstant($oldIn, $finalIn);
        $outChanged = ! $this->sameInstant($oldOut, $finalOut);

        // Koreksi juga dianggap perubahan bila record belum pernah dikoreksi
        // (penanda administratif perlu tetap tercatat).
        return $inChanged || $outChanged || ! $attendance->is_corrected;
    }

    private function sameInstant(mixed $a, mixed $b): bool
    {
        if ($a === null && $b === null) {
            return true;
        }

        if ($a === null || $b === null) {
            return false;
        }

        return Carbon::parse($a)->eq(Carbon::parse($b));
    }

    /**
     * Bentuk payload audit: waktu diformat string, boolean dinormalisasi,
     * TANPA barcode/nilai sensitif lain (PRD 04 §9.2).
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function presentable(array $values): array
    {
        return array_map(
            fn ($value) => match (true) {
                $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i:s'),
                is_bool($value) => $value ? '1' : '0',
                default => $value,
            },
            $values
        );
    }
}
