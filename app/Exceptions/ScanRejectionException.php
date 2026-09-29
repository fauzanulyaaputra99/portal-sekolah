<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;

/**
 * Penolakan scan yang MASIH MERUPAKAN event "Guru Piket melakukan scan
 * barcode siswa" (PRD 04 §9.1 baris "Absensi Masuk/Pulang Siswa").
 *
 * Kenapa perlu subclass dari ValidationException?
 *  1. Perilaku HTTP harus TIDAK berubah: framework tetap menanganinya sebagai
 *     validasi gagal (422 -> redirect-back dengan pesan $errors), BUKAN HTTP 500.
 *     Handler framework memakai pengecekan `instanceof ValidationException`
 *     (vendor/.../Exceptions/Handler.php:730), jadi subclass diwarisi utuh.
 *  2. Penolakan terjadi DI DALAM DB::transaction(). Baris audit yang ditulis di
 *     dalam transaksi itu ikut ter-rollback (audit harus append-only dan harus
 *     bertahan), jadi outcome dibawa KELUAR transaksi lewat exception ini lalu
 *     diaudit oleh scan() SETELAH transaksi di-rollback.
 *
 * Sengaja TIDAK ada barcode mentah di properti ini: yang dibutuhkan auditor
 * adalah outcome + identitas siswa yang sudah terselesaikan (bila ada), bukan
 * payload klien (PRD 04 §9.2).
 */
class ScanRejectionException extends ValidationException
{
    /** Outcome audit, mis. SchoolAttendanceScanService::OUTCOME_DUPLICATE_MASUK. */
    public string $scanOutcome = '';

    /**
     * Data kontekstual non-sensitif untuk payload audit
     * (student_id, nis, mode, attendance_id, waktu yang sudah tercatat).
     *
     * @var array<string, mixed>
     */
    public array $scanContext = [];

    /**
     * Satu penolakan scan dengan outcome yang dapat diaudit.
     *
     * @param  array<string, mixed>  $scanContext
     */
    public static function reject(string $outcome, string $field, string $message, array $scanContext = []): static
    {
        /** @var static $exception */
        $exception = static::withMessages([$field => $message]);
        $exception->scanOutcome = $outcome;
        $exception->scanContext = $scanContext;

        return $exception;
    }
}
