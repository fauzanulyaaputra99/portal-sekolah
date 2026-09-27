<?php

namespace App\Policies;

use App\Models\TeachingAssignment;
use App\Models\User;

/**
 * Otorisasi Penugasan Mengajar (teaching assignments).
 *
 * Acuan PRD 01 §5.4 & §6.7, PRD 04 §3.2.3 (mutasi penugasan wajib Policy).
 *
 * Matriks:
 * - Admin/TU  : CRUD penuh (ADM-TAS-001/002)
 * - Guru      : read-only milik sendiri
 * - Supervisor: read-only
 *
 * Catatan kewenangan (PRD 04 §3.2.4 / ADDENDUM §11–§13): akses scanner TIDAK
 * dievaluasi di sini. Scanner bergantung pada teacher_duty_schedules per
 * tanggal server dan baru dibangun pada tahap adjustment fitur berikutnya
 * (DutySchedulePolicy) — bukan bagian Prompt 25.
 */
class TeachingAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(
            User::ROLE_ADMIN,
            User::ROLE_TEACHER,
            User::ROLE_SUPERVISOR
        );
    }

    /**
     * Baca satu penugasan: admin & supervisor boleh (monitoring);
     * guru hanya penugasan yang memang miliknya (anti-IDOR).
     */
    public function view(User $user, TeachingAssignment $assignment): bool
    {
        if ($user->isAdmin() || $user->isSupervisor()) {
            return true;
        }

        $teacherId = $user->teacherId();

        return $user->isTeacher()
            && $teacherId !== null
            && (int) $assignment->teacher_id === $teacherId;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, TeachingAssignment $assignment): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, TeachingAssignment $assignment): bool
    {
        return $user->isAdmin();
    }
}
