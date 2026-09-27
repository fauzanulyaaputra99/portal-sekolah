<?php

namespace App\Policies;

use App\Models\StudentAttendanceSession;
use App\Models\User;

/**
 * Otorisasi sesi absensi siswa PER MATA PELAJARAN (PRD 01 §7.5).
 *
 * Acuan wajib:
 * - PRD 02 §6 tabel ancaman: "IDOR pada Absensi Siswa Mapel" —
 *   cek silang guru sesi vs profil guru sesi login; tidak cocok -> HTTP 403.
 * - PRD 04 §3.2.3 & §16.2: Guru membuka sesi absensi di luar penugasan
 *   harus diblokir 403.
 * - PRD 01 §5.4: Guru = "Sesuai Assignment"; Admin/TU = Koreksi Manual;
 *   Supervisor = Read-only.
 *
 * Rantai kepemilikan dievaluasi server-side:
 *   session -> teaching_assignment -> teacher  VS  user -> teacher (profil)
 * Nilai dari klien (ID sesi dari URL) HANYA penunjuk resource, tidak pernah
 * menjadi dasar keputusan akses.
 */
class StudentAttendanceSessionPolicy
{
    /** Daftar/monitoring: semua role resmi, discoping di controller. */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(
            User::ROLE_ADMIN,
            User::ROLE_TEACHER,
            User::ROLE_SUPERVISOR
        );
    }

    /**
     * Baca sesi: guru pengampu pada penugasan sesi, admin (monitoring +
     * koreksi), supervisor (read-only). Guru lain -> 403 (anti-IDOR).
     */
    public function view(User $user, StudentAttendanceSession $session): bool
    {
        if ($user->isAdmin() || $user->isSupervisor()) {
            return true;
        }

        return $user->isTeacher() && $this->isAssignee($user, $session);
    }

    /**
     * Buka sesi absensi (create): hanya guru pemegang teaching assignment
     * yang sah pada sesi tersebut.
     */
    public function create(User $user): bool
    {
        return $user->isTeacher() && $user->teacherId() !== null;
    }

    /**
     * Input/kunci kehadiran (update): HANYA guru pengampu assignment sesi.
     * Setelah CLOSED, perubahan hanya lewat jalur koreksi Admin/TU
     * (ditegakkan di service pada tahap berikutnya — policy ini tetap
     * menolak guru non-pengampu pada semua kondisi).
     */
    public function update(User $user, StudentAttendanceSession $session): bool
    {
        return $user->isTeacher() && $this->isAssignee($user, $session);
    }

    /**
     * Hapus sesi: tidak untuk guru. Admin/TU adalah satu-satunya role
     * koreksi; aksi destruktif atas data absensi tidak diberikan ke
     * guru/supervisor (least privilege).
     */
    public function delete(User $user, StudentAttendanceSession $session): bool
    {
        return $user->isAdmin();
    }

    /**
     * Koreksi manual record absensi (ADM-ABS-003): khusus Admin/TU,
     * dengan kewajiban alasan (divalidasi di service, tahap berikutnya).
     */
    public function correct(User $user, StudentAttendanceSession $session): bool
    {
        return $user->isAdmin();
    }

    /**
     * Cek silang server-side: apakah guru milik akun login ini adalah guru
     * pengampu dari teaching assignment yang memiliki sesi tersebut.
     * Fail closed bila profil guru belum ada atau relasi tidak lengkap.
     */
    private function isAssignee(User $user, StudentAttendanceSession $session): bool
    {
        $teacherId = $user->teacherId();

        if ($teacherId === null) {
            return false;
        }

        $assignment = $session->teachingAssignment;

        return $assignment !== null && (int) $assignment->teacher_id === $teacherId;
    }
}
