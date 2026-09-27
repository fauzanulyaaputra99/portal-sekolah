<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoleDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Portal Sekolah
|--------------------------------------------------------------------------
|
| Peta rute mengacu PRD 02 §6 (Route Tree):
|   /admin      -> middleware role:admin
|   /guru       -> middleware role:teacher
|   /supervisor -> middleware role:supervisor
|
| Keamanan (PRD 04 §3.2):
|  - Verifikasi akun aktif berjalan GLOBAL pada group web (EnsureUserIsActive).
|  - Otorisasi role ditegakkan server-side via middleware `role`.
|  - Pelanggaran prefix role => HTTP 403 (bukan redirect).
|  - Tidak ada satu pun rute yang mengandalkan penyembunyian UI sebagai
|    kontrol akses; akses URL langsung tetap terproteksi.
|
| CATATAN: rute scanner absensi masuk/pulang (/guru/school-attendance-scanner)
| BELUM dibuat pada tahap ini — dibangun pada tahap adjustment fitur
| berikutnya bersama DutySchedulePolicy (teacher_duty_schedules).
*/

// Root URL: arahkan ke dashboard jika terautentikasi, atau login jika tamu
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// ---------------------------------------------------------------------------
// Rute Tamu (Guest)
// ---------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// ---------------------------------------------------------------------------
// Rute Terproteksi (Wajib Login). EnsureUserIsActive sudah global di group web.
// ---------------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    // Dashboard foundation (Prompt 24) — tetap melayani semua role.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

// ---------------------------------------------------------------------------
// Area ADMIN / TU  (PRD 01 §6)
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [RoleDashboardController::class, 'admin'])->name('dashboard');
    });

// ---------------------------------------------------------------------------
// Area GURU  (PRD 01 §7)
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'role:teacher'])
    ->prefix('guru')
    ->name('guru.')
    ->group(function () {
        Route::get('/dashboard', [RoleDashboardController::class, 'guru'])->name('dashboard');
    });

// ---------------------------------------------------------------------------
// Area ATASAN / SUPERVISOR  (PRD 01 §8)
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'role:supervisor'])
    ->prefix('supervisor')
    ->name('supervisor.')
    ->group(function () {
        Route::get('/dashboard', [RoleDashboardController::class, 'supervisor'])->name('dashboard');
    });
