<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard FOUNDATION per area role (Prompt 25).
 *
 * Sengaja MINIMAL: hanya membuktikan pemisahan area + otorisasi role.
 * Metrik operasional lengkap (ADM-DASH-001, GUR-DASH, SUP-DASH, indikator
 * absensi masuk/pulang, guru piket hari ini, dsb.) dibangun pada tahap
 * berikutnya — BUKAN di sini.
 *
 * Keamanan (PRD 04 §3.2): middleware `role` pada rute sudah menolak role
 * yang salah dengan 403 SEBELUM kode ini dijalankan. Pemeriksaan role di
 * bawah hanyalah penjaga ganda (defense in depth) — bukan satu-satunya.
 */
class RoleDashboardController extends Controller
{
    public function admin(Request $request): View
    {
        $user = $this->authorizeArea($request, User::ROLE_ADMIN);

        return view('dashboards.admin', [
            'user' => $user,
            'areaRoute' => 'admin.dashboard',
        ]);
    }

    /**
     * Dashboard Guru (PRD 01 §7.1 GUR-DASH-001–003).
     *
     * Widget:
     * - GUR-DASH-001: Jadwal mengajar hari ini
     * - GUR-DASH-002: Status Guru Piket hari ini + tombol scanner
     * - GUR-DASH-003: Ringkasan status berkas ajar (placeholder — butuh A-03)
     */
    public function guru(Request $request): View
    {
        $user = $this->authorizeArea($request, User::ROLE_TEACHER);
        $teacher = $user->teacher;

        // GUR-DASH-001: Jadwal mengajar hari ini
        $todayDayOfWeek = now()->dayOfWeekIso;
        $todaySchedules = collect();

        if ($teacher) {
            $todaySchedules = \App\Models\Schedule::query()
                ->whereHas('teachingAssignment', fn ($q) => $q->where('teacher_id', $teacher->id))
                ->where('day_of_week', $todayDayOfWeek)
                ->with(['teachingAssignment.subject', 'teachingAssignment.classRoom.gradeLevel'])
                ->orderBy('start_time')
                ->get();
        }

        // GUR-DASH-002: Status Guru Piket hari ini
        $isOnDutyToday = false;
        if ($teacher) {
            $isOnDutyToday = \App\Models\TeacherDutySchedule::query()
                ->where('teacher_id', $teacher->id)
                ->where('schedule_date', now()->toDateString())
                ->exists();
        }

        // GUR-DASH-003: Ringkasan dokumen (placeholder — A-03 BLOCKED GoogleDriveService)
        $documentSummary = null;

        return view('dashboards.guru', [
            'user' => $user,
            'teacher' => $teacher,
            'todaySchedules' => $todaySchedules,
            'isOnDutyToday' => $isOnDutyToday,
            'documentSummary' => $documentSummary,
        ]);
    }

    /**
     * Dashboard Supervisor (PRD 01 §8.1 SUP-DASH-001–002).
     *
     * Widget:
     * - SUP-DASH-001: Ringkasan progres pengumpulan dokumen seluruh guru
     * - SUP-DASH-002: Antrean DIKIRIM/REVISI + distribusi status dokumen
     *
     * Catatan: Metrik nyata dokumen membutuhkan A-03 (GoogleDriveService).
     * Saat ini menampilkan statistik guru dan placeholder dokumen.
     */
    public function supervisor(Request $request): View
    {
        $user = $this->authorizeArea($request, User::ROLE_SUPERVISOR);

        // Statistik guru (untuk monitoring kepatuhan)
        $totalTeachers = \App\Models\Teacher::count();
        $activeTeachers = \App\Models\Teacher::whereHas('user', fn ($q) => $q->where('is_active', true))->count();

        // Placeholder metrik dokumen (A-03 BLOCKED GoogleDriveService)
        // Nanti akan berisi: total dokumen per status, antrean review, distribusi kategori
        $documentStats = [
            'total' => 0,
            'draft' => 0,
            'submitted' => 0,
            'approved' => 0,
            'revision_requested' => 0,
        ];

        return view('dashboards.supervisor', [
            'user' => $user,
            'totalTeachers' => $totalTeachers,
            'activeTeachers' => $activeTeachers,
            'documentStats' => $documentStats,
        ]);
    }

    /**
     * Defense in depth: pastikan role sesi cocok dengan area.
     * Melempar 403 (AccessDeniedHttpException via abort) bila tidak.
     */
    private function authorizeArea(Request $request, string $requiredRole): User
    {
        $user = $request->user();

        abort_unless($user->hasRole($requiredRole), 403, 'Akses Ditolak: area ini bukan kewenangan role Anda.');

        if ($user->isTeacher()) {
            $user->load('teacher');
        }

        return $user;
    }
}
