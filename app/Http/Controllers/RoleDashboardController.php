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

        return view('dashboards.role', [
            'user' => $user,
            'area' => 'Admin / Tata Usaha',
            'areaRoute' => 'admin.dashboard',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function guru(Request $request): View
    {
        $user = $this->authorizeArea($request, User::ROLE_TEACHER);

        return view('dashboards.role', [
            'user' => $user,
            'area' => 'Guru',
            'areaRoute' => 'guru.dashboard',
            'role' => User::ROLE_TEACHER,
        ]);
    }

    public function supervisor(Request $request): View
    {
        $user = $this->authorizeArea($request, User::ROLE_SUPERVISOR);

        return view('dashboards.role', [
            'user' => $user,
            'area' => 'Atasan / Supervisor',
            'areaRoute' => 'supervisor.dashboard',
            'role' => User::ROLE_SUPERVISOR,
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
