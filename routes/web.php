<?php

use App\Http\Controllers\Admin\TeacherDutyScheduleController;
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
| BELUM dibuat pada tahap ini. Yang sudah aktif adalah pengelolaan jadwalnya
| (/admin/duty-schedules + TeacherDutySchedulePolicy). Validasi jadwal piket
| per request untuk scanner dibangun bersama fitur scanner itu sendiri.
|
| PENGECUALIAN RUTE BACA (PRD 01 §5.4 — Admin CRUD / Guru Tidak Ada /
| Supervisor Read-only): GET /admin/duty-schedules (index) memakai
| role:admin,supervisor agar supervisor dapat MEMBACA jadwal. Seluruh aksi
| mutasi (create/store/edit/update/destroy) tetap role:admin dan guru tetap
| 403 pada semua endpoint jadwal piket.
|
| Sumber kebenaran "Guru Piket" tetap tabel teacher_duty_schedules pada tanggal
| berjalan server (Asia/Jakarta) — BUKAN role baru, BUKAN users.is_piket.
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
//
// Group ini adalah WILAYAH MUTASI: seluruh aksi tulis jadwal piket berada di
// bawah role:admin. Jangan menambah rute baca di sini jika PRD mengizinkan
// role lain membacanya (lihat group baca di bawah).
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [RoleDashboardController::class, 'admin'])->name('dashboard');

        // CRUD Jadwal Guru Piket (PRD 02 §6 /admin/duty-schedules, ADM-PIK-001).
        // Aksi mutasi: create/store/edit/update/destroy — ADMIN/TU ONLY.
        // `index` sengaja dipisah ke group baca di bawah agar supervisor dapat
        // membaca jadwal (PRD 01 §5.4 Read-only) tanpa diberi hak mutasi.
        // `show` dikecualikan karena PRD tidak mensyaratkan halaman detail jadwal.
        Route::resource('duty-schedules', TeacherDutyScheduleController::class)
            ->except(['show', 'index'])
            ->parameters(['duty-schedules' => 'dutySchedule']);
    });

// ---------------------------------------------------------------------------
// BACA Jadwal Guru Piket — Admin/TU (pengelola) + Supervisor (monitoring)
// PRD 01 §5.4: "Kelola Jadwal Piket Guru" => Admin CRUD, Guru Tidak Ada,
// Supervisor READ-ONLY. URL dan nama rute sengaja dipertahankan di bawah
// prefix /admin (admin.duty-schedules.index) karena view & redirect controller
// memakai nama rute tersebut; yang dilonggarkan HANYA hak membacanya.
//
// Guru TETAP ditolak (403) di sini — role guru tidak ada dalam daftar.
// TeacherDutySchedulePolicy::viewAny() tetap menjadi kontrol server-side
// kedua (defense in depth), sehingga meski middleware salah dikonfigurasi,
// role non-admin/supervisor tetap 403.
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'role:admin,supervisor'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/duty-schedules', [TeacherDutyScheduleController::class, 'index'])
            ->name('duty-schedules.index');
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
