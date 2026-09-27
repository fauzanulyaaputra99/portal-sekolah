<?php

namespace App\Policies;

use App\Models\TeacherDutySchedule;
use App\Models\User;

/**
 * Otorisasi Jadwal Guru Piket.
 *
 * Acuan:
 * - PRD 01 §5.4 baris "Kelola Jadwal Piket Guru": Admin/TU = CRUD Penuh,
 *   Guru = Tidak Ada, Supervisor = Read-only.
 * - PRD 01 §6.11 ADM-PIK-001, ADDENDUM §9 (jadwal dikelola sepenuhnya Admin/TU).
 * - PRD 04 §3.2.1/§3.2.3 (otorisasi server-side + anti-IDOR), §19.A checklist.
 * - Prompt 25: memakai RBAC yang sudah ada (`role:admin` middleware + Gate),
 *   TIDAK membangun sistem otorisasi baru.
 *
 * Kepemilikan objek TIDAK berdasarkan identitas guru/client input:
 * setiap objek TeacherDutySchedule dinilai dari keberadaannya di database
 * lewat route model binding, lalu aksinya dibatasi per role.
 *
 * Catatan "Read-only Supervisor": hak lihat jadwal untuk supervisor adalah
 * hak monitoring atas resource milik area Admin; area `/supervisor/*`
 * ditegakkan oleh middleware. Policy ini hanya mengatur aksi mutasi, dan
 * `view` sengaja mengizinkan supervisor + admin (sesuai matriks PRD),
 * sementara guru HANYA boleh melihat jadwal miliknya sendiri
 * (dipakai dashboard/banner status piket — PRD 01 §7.1 GUR-DASH-001).
 */
class TeacherDutySchedulePolicy
{
    /** Daftar/monitoring jadwal: Admin & Supervisor (PRD 01 §5.4). */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_SUPERVISOR);
    }

    /**
     * Baca satu jadwal.
     * - Admin/TU: penuh (pengelola).
     * - Supervisor: read-only monitoring (PRD 01 §5.4).
     * - Guru: hanya baris jadwal miliknya sendiri (anti-IDOR + status piket dirinya).
     */
    public function view(User $user, TeacherDutySchedule $schedule): bool
    {
        if ($user->isAdmin() || $user->isSupervisor()) {
            return true;
        }

        $teacherId = $user->teacherId();

        return $user->isTeacher()
            && $teacherId !== null
            && (int) $schedule->teacher_id === $teacherId;
    }

    /** Hanya Admin/TU yang boleh membuat jadwal (ADM-PIK-001). */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Hanya Admin/TU yang boleh mengubah (ganti guru / ganti tanggal). */
    public function update(User $user, TeacherDutySchedule $schedule): bool
    {
        return $user->isAdmin();
    }

    /** Hanya Admin/TU yang boleh menghapus jadwal. */
    public function delete(User $user, TeacherDutySchedule $schedule): bool
    {
        return $user->isAdmin();
    }
}
