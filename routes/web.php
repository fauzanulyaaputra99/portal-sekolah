<?php

use App\Http\Controllers\Admin\SchoolAttendanceController;
use App\Http\Controllers\Admin\TeacherDutyScheduleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoleDashboardController;
use App\Http\Controllers\Teacher\SchoolAttendanceScanController;
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
| CATATAN (ADDENDUM): scanner absensi masuk/pulang siswa sudah aktif di
| /guru/school-attendance-scanner dan dikunci oleh rantai `auth` ->
| `role:teacher` -> `on.duty`. Status "Guru Piket" dinilai ulang terhadap
| teacher_duty_schedules pada SETIAP request memakai tanggal server, sehingga
| akses scanner tidak pernah bergantung pada UI, role khusus, maupun flag user.
| Pengelolaan jadwalnya tetap /admin/duty-schedules (TeacherDutySchedulePolicy).
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

        // -----------------------------------------------------------------
        // Scanner Absensi Masuk/Pulang Siswa (ADDENDUM §1–§21, PRD 01 §7.4).
        //
        // Rangkaian: `auth` -> `role:teacher` -> `on.duty` (EnsureTeacherOnDuty).
        // - role:teacher menolak admin & supervisor dengan HTTP 403 (scanner
        //   bukan kewenangan area mereka — PRD 01 §5.4).
        // - `on.duty` menilai ULANG teacher_duty_schedules terhadap TANGGAL
        //   SERVER pada SETIAP request: GET (buka halaman) dan POST (submit
        //   scan). Tanpa cache, tanpa flag, tanpa role "piket"
        //   (ADDENDUM §12–§14; PRD 04 §3.2.4 & §16.5).
        // - POST berada dalam grup `web` => middleware CSRF (PreventRequestForgery)
        //   aktif; form menyematkan @csrf (PRD 04 §5.1).
        // - Klien hanya mengirim `barcode` + `mode`. Operator, tanggal, waktu,
        //   dan status piket SELALU dari server (PRD 04 §3.2.4).
        // - Tidak ada aksi edit/hapus di sini: Guru Piket tidak punya hak
        //   koreksi (PRD 01 §13.7).
        // -----------------------------------------------------------------
        Route::middleware('on.duty')
            ->name('school-attendance-scanner.')
            ->group(function () {
                Route::get('/school-attendance-scanner', [SchoolAttendanceScanController::class, 'index'])
                    ->name('index');

                Route::post('/school-attendance-scanner', [SchoolAttendanceScanController::class, 'store'])
                    ->name('store');
            });
    });

// ---------------------------------------------------------------------------
// MONITORING & KOREKSI Absensi Masuk/Pulang Siswa (PRD 02 §6
// /admin/school-attendances; PRD 01 §6.9 ADM-ABS-001 & ADM-ABS-003, §13.7).
//
// Pola pemisahan hak sama dengan jadwal piket: URL tetap di bawah prefix
// /admin, yang dilonggarkan HANYA hak MEMBACA (supervisor = read-only,
// PRD 01 §5.4). Aksi koreksi tetap role:admin dan dijaga ulang oleh
// SchoolAttendancePolicy::correct() (defense in depth). Guru tetap 403.
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'role:admin,supervisor'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/school-attendances', [SchoolAttendanceController::class, 'index'])
            ->name('school-attendances.index');

        Route::get('/school-attendances/{schoolAttendance}/correct', [SchoolAttendanceController::class, 'edit'])
            ->name('school-attendances.correct');
    });

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::patch('/school-attendances/{schoolAttendance}', [SchoolAttendanceController::class, 'update'])
            ->name('school-attendances.update');
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
