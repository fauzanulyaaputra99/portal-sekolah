<?php

namespace App\Policies;

use App\Models\SchoolAttendance;
use App\Models\User;

/**
 * Otorisasi Absensi Masuk/Pulang SEKOLAH (PRD 01 §5.4 baris "Koreksi Absensi
 * Siswa": Admin = Penuh (wajib catatan), Guru = Tidak Ada, Supervisor =
 * Tidak Ada; PRD 01 §13.7, PRD 04 §3.2.3 anti-IDOR).
 *
 * Lapisan KEDUA setelah middleware `role` pada rute. Sengaja tidak percaya:
 * - role/identitas dari klien (dibaca dari kolom users.role pada sesi server),
 * - kepemilikan objek (record absensi tidak "dimiliki" user login — akses
 *   dinilai dari role, bukan dari kecocokan id klien dengan data).
 *
 * Catatan pemisahan hak (PRD 01 §5.4):
 * - BACA  : Admin/TU (monitoring ADM-ABS-001) + Supervisor (read-only).
 * - KOREKSI: HANYA Admin/TU. Supervisor eksplisit ditolak meski hanya ingin
 *   mengubah satu nilai, karena matriks PRD menyebut "Tidak Ada" untuk
 *   koreksi. Guru pun ditolak total (§13.7 "Guru Piket tidak dapat mengoreksi").
 * - HAPUS : tidak ada jalur hapus pada modul ini (append-only + ON DELETE
 *   RESTRICT); penolakan di sini menjaga agar tidak pernah ada yang
 *   "diam-diam" menambahkannya.
 */
class SchoolAttendancePolicy
{
    /** Daftar/monitoring rekap: Admin/TU + Supervisor read-only. */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_SUPERVISOR);
    }

    /** Baca satu record: Admin/TU + Supervisor (read-only). */
    public function view(User $user, SchoolAttendance $attendance): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_SUPERVISOR);
    }

    /**
     * Koreksi/manual entry = hak penuh Admin/TU saja (ADM-ABS-003).
     * Guru & Supervisor => ditolak.
     */
    public function correct(User $user, SchoolAttendance $attendance): bool
    {
        return $user->isAdmin();
    }

    /** Tidak ada aksi hapus record absensi pada modul ini (PRD 04 §9.2). */
    public function delete(User $user, SchoolAttendance $attendance): bool
    {
        return false;
    }
}
