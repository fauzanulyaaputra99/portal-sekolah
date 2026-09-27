<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

/**
 * Otorisasi level resource untuk Repositori Dokumen Guru.
 * Matriks acuan: PRD 01 §5.4 & §7.3/§8.3, PRD 04 §3.2.3 (anti-IDOR),
 * PRD 03 §6.3 (kepemilikan dokumen), ADR 04 (Admin TIDAK punya modul dokumen).
 *
 * Prinsip: least privilege + kepemilikan server-side.
 * Kepemilikan dihitung dari `documents.teacher_id` vs profil guru milik
 * akun yang login — TIDAK PERNAH dari parameter/hidden field klien.
 */
class DocumentPolicy
{
    /**
     * Halaman daftar: setiap role resmi boleh, TAPI cakupannya beda
     * (Guru = miliknya sendiri, Supervisor/Admin = monitoring read-only).
     * Scope per-role ditegakkan di controller/query, bukan di sini.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(
            User::ROLE_ADMIN,
            User::ROLE_TEACHER,
            User::ROLE_SUPERVISOR
        );
    }

    /**
     * Baca satu dokumen: pemilik (guru), supervisor (review read-only),
     * admin (monitoring kelengkapan). Akses oleh guru lain DITOLAK (IDOR).
     */
    public function view(User $user, Document $document): bool
    {
        if ($user->isSupervisor() || $user->isAdmin()) {
            return true;
        }

        return $user->isTeacher() && $this->owns($user, $document);
    }

    /** Hanya Guru yang dapat membuat dokumen (ADR 04: akun terpisah Admin). */
    public function create(User $user): bool
    {
        return $user->isTeacher() && $user->teacherId() !== null;
    }

    /**
     * Ubah metadata/revisi: HANYA guru pemilik.
     * Supervisor & Admin tidak dapat mengubah/menghapus file guru (§5.3, §5.4).
     */
    public function update(User $user, Document $document): bool
    {
        return $user->isTeacher() && $this->owns($user, $document);
    }

    /** Hapus: hanya guru pemilik (dan hanya sah bila belum direview — aturan itu ditegakkan di service). */
    public function delete(User $user, Document $document): bool
    {
        return $user->isTeacher() && $this->owns($user, $document);
    }

    /** Ajukan review: hanya guru pemilik. */
    public function submit(User $user, Document $document): bool
    {
        return $user->isTeacher() && $this->owns($user, $document);
    }

    /**
     * Approve / Request Revision: HANYA supervisor (§5.4 baris Review & Approve).
     * Admin dan guru (termasuk pemilik) ditolak.
     */
    public function review(User $user, Document $document): bool
    {
        return $user->isSupervisor();
    }

    /**
     * Kepemilikan murni server-side: idenfititas profil guru dari sesi login.
     * Akun guru tanpa profil (teacherId null) selalu ditolak — fail closed.
     */
    private function owns(User $user, Document $document): bool
    {
        $teacherId = $user->teacherId();

        return $teacherId !== null && (int) $document->teacher_id === $teacherId;
    }
}
