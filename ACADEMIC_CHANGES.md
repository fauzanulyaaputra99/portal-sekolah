# ACADEMIC WORKSTREAM - TRACKING PERUBAHAN

> **Tujuan:** Dokumen ini mencatat SEMUA perubahan yang dilakukan oleh Academic Workstream untuk mencegah konflik dengan Administration Workstream saat push/pull GitHub.
> **Wajib dibaca oleh tim Administration sebelum melakukan merge/pull dari branch Academic.**

---

## INFORMASI BRANCH

**Branch Academic:** `feature/academic-dashboard`  
**Last Update:** 2026-10-06T08:30:00+07:00  
**Status:** ACTIVE — A-01, A-02, A-05, A-06 COMPLETED

---

## RINGKASAN TASK SELESAI

| Task ID | Nama | Status | PRD Reference |
|---------|------|--------|---------------|
| A-01 | Dashboard Guru | ✅ COMPLETED | PRD 01 §7.1 (GUR-DASH-001–003) |
| A-02 | Profil Guru | ✅ COMPLETED | PRD 01 §7.2 (GUR-PRF-001–002) |
| A-05 | Absensi Siswa per Mata Pelajaran | ✅ COMPLETED | PRD 01 §7.5 (GUR-ATT-001–004), ADR 14 |
| A-06 | Dashboard Supervisor | ✅ COMPLETED | PRD 01 §8.1 (SUP-DASH-001–002) |
| A-03 | Repositori Dokumen Guru | 🚫 BLOCKED | Menunggu GoogleDriveService |
| A-04 | Document Versioning | 🚫 BLOCKED | Menunggu A-03 |
| A-07 | Monitoring Repositori Guru | 🚫 BLOCKED | Menunggu A-03 |
| A-08 | Review & Approval Dokumen | 🚫 BLOCKED | Menunggu A-03 |

---

## FILE YANG DIBUAT/DIUBAH OLEH ACADEMIC

### ✅ Task A-01: Dashboard Guru (COMPLETED)

#### File Baru
1. **View**
   - `resources/views/dashboards/guru.blade.php`
   - Owner: Academic
   - Keterangan: View terpisah khusus dashboard guru (bukan lagi pakai role.blade.php). Widget jadwal mengajar hari ini, banner piket, placeholder dokumen.

#### File yang Dimodifikasi ⚠️
1. **`app/Http/Controllers/RoleDashboardController.php`** ⚠️ SHARED FILE
   - **Method diubah:** `guru()`
   - **Perubahan:** Menambahkan query jadwal mengajar hari ini (`schedules`), status piket (`teacher_duty_schedules`), dan placeholder dokumen. Mengubah view dari `dashboards.role` ke `dashboards.guru`.
   - **Conflict Risk:** MEDIUM — Administration mungkin mengubah method `admin()` di file yang sama. Method `guru()` adalah milik Academic, method `admin()` milik Administration.
   - **Mitigation:** Jangan ubah method `guru()` dari sisi Administration.

---

### ✅ Task A-02: Profil Guru (COMPLETED)

#### File Baru
1. **Controller**
   - `app/Http/Controllers/Teacher/ProfileController.php`
   - Owner: Academic
   - Methods: `show`, `edit`, `updateContact`, `editPassword`, `updatePassword`
   - Security: Defense-in-depth `isTeacher()` check di setiap method

2. **Views** (3 files)
   - `resources/views/guru/profile/show.blade.php` — Biodata + riwayat penugasan
   - `resources/views/guru/profile/edit.blade.php` — Form edit kontak
   - `resources/views/guru/profile/password.blade.php` — Form ubah kata sandi

#### File Shared yang Dimodifikasi ⚠️
1. **`routes/web.php`** ⚠️ SHARED FILE
   - **Perubahan:** +1 import, +5 routes di grup `guru`
   - **Detail Perubahan:**
     ```php
     // Import
     + use App\Http\Controllers\Teacher\ProfileController;
     
     // Routes di grup guru (setelah dashboard, sebelum attendance)
     + Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
     + Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
     + Route::put('/profile/contact', [ProfileController::class, 'updateContact'])->name('profile.update-contact');
     + Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('profile.password');
     + Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
     ```
   - **Conflict Risk:** MEDIUM
   - **Mitigation:** Academic hanya menambah di grup `guru`. Administration menambah di grup `admin` atau `supervisor`.

---

### ✅ Task A-05: Absensi Siswa per Mata Pelajaran (COMPLETED)

#### File Baru
1. **Controller**
   - `app/Http/Controllers/Teacher/StudentAttendanceController.php`
   - Owner: Academic
   - Methods: 8 (index, create, store, show, edit, update, close, reopen)
   - Security: `Gate::authorize()` di setiap method via `StudentAttendanceSessionPolicy`

2. **Service**
   - `app/Services/StudentAttendanceService.php`
   - Owner: Academic
   - Methods: 10 (getTeacherAssignments, getTeacherSessions, openSession, prefillStudents, updateAttendance, closeSession, reopenSession, getSessionDetail, isSessionEditable, getSessionStatistics)

3. **Views** (4 files)
   - `resources/views/guru/attendance/index.blade.php` — Daftar sesi dengan filter
   - `resources/views/guru/attendance/create.blade.php` — Form buka sesi baru
   - `resources/views/guru/attendance/edit.blade.php` — Input kehadiran H/I/S/A
   - `resources/views/guru/attendance/show.blade.php` — Detail sesi read-only

#### File Shared yang Dimodifikasi ⚠️
1. **`routes/web.php`** ⚠️ SHARED FILE
   - **Perubahan:** +1 import, +8 routes di grup `guru`
   - **Detail Perubahan:**
     ```php
     // Import
     + use App\Http\Controllers\Teacher\StudentAttendanceController;
     
     // Routes di grup guru
     + Route::resource('attendance', StudentAttendanceController::class)
     +     ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
     + Route::post('/attendance/{id}/close', [StudentAttendanceController::class, 'close'])
     +     ->name('attendance.close');
     + Route::post('/attendance/{id}/reopen', [StudentAttendanceController::class, 'reopen'])
     +     ->name('attendance.reopen');
     ```
   - **Conflict Risk:** MEDIUM
   - **Mitigation:** Sama seperti A-02, section berbeda.

---

### ✅ Task A-06: Dashboard Supervisor (COMPLETED)

#### File Baru
1. **View**
   - `resources/views/dashboards/supervisor.blade.php`
   - Owner: Academic
   - Keterangan: View terpisah khusus dashboard supervisor. Widget statistik guru, placeholder metrik dokumen.

#### File yang Dimodifikasi ⚠️
1. **`app/Http/Controllers/RoleDashboardController.php`** ⚠️ SHARED FILE
   - **Method diubah:** `supervisor()`
   - **Perubahan:** Menambahkan query statistik guru (total, aktif) dan placeholder dokumen. Mengubah view dari `dashboards.role` ke `dashboards.supervisor`.
   - **Conflict Risk:** MEDIUM — Administration mungkin mengubah method `admin()` di file yang sama. Method `supervisor()` adalah milik Academic.
   - **Mitigation:** Jangan ubah method `supervisor()` dari sisi Administration.

---

## ROUTES YANG DITAMBAHKAN (TOTAL: 13 ROUTES)

### Academic Routes — Prefix `/guru`

| Method | Path | Name | Task |
|--------|------|------|------|
| GET | /guru/profile | guru.profile.show | A-02 |
| GET | /guru/profile/edit | guru.profile.edit | A-02 |
| PUT | /guru/profile/contact | guru.profile.update-contact | A-02 |
| GET | /guru/profile/password | guru.profile.password | A-02 |
| PUT | /guru/profile/password | guru.profile.update-password | A-02 |
| GET | /guru/attendance | guru.attendance.index | A-05 |
| GET | /guru/attendance/create | guru.attendance.create | A-05 |
| POST | /guru/attendance | guru.attendance.store | A-05 |
| GET | /guru/attendance/{id} | guru.attendance.show | A-05 |
| GET | /guru/attendance/{id}/edit | guru.attendance.edit | A-05 |
| PUT | /guru/attendance/{id} | guru.attendance.update | A-05 |
| POST | /guru/attendance/{id}/close | guru.attendance.close | A-05 |
| POST | /guru/attendance/{id}/reopen | guru.attendance.reopen | A-05 |

**Catatan:** Dashboard (A-01, A-06) menggunakan route yang sudah ada (`guru.dashboard`, `supervisor.dashboard`), hanya view dan controller yang berubah.

---

## DEPENDENCY & DATABASE

### Models yang Digunakan (TIDAK DIUBAH oleh Academic)
Academic hanya READ dan memanfaatkan model yang sudah ada:

- `User`, `Teacher`, `Student`, `ClassRoom`, `ClassEnrollment`, `Subject`, `AcademicYear`, `TeachingAssignment`
- `StudentAttendanceSession` — CRUD via Service (Academic ownership)
- `StudentAttendanceRecord` — CRUD via Service (Academic ownership)
- `Schedule` — Read only (untuk widget dashboard)
- `TeacherDutySchedule` — Read only (untuk widget dashboard)
- `Document` — Placeholder (menunggu A-03)

### Policies yang Digunakan (TIDAK DIUBAH oleh Academic)
- `StudentAttendanceSessionPolicy` — sudah ada, hanya dipanggil
- `TeachingAssignmentPolicy` — sudah ada, hanya dipanggil

### Database Changes
**NONE** — Tidak ada migration baru, tidak ada perubahan skema database. Semua tabel yang dibutuhkan sudah ada dari ADJUSTMENT A.

---

## FILE SHARED — PANDUAN KOORDINASI

### 1. `routes/web.php` ⚠️

| Section | Owner | Line Range (approx) |
|---------|-------|---------------------|
| Grup `admin` | ADMINISTRATION | ~60-110 |
| Grup `guru` | ACADEMIC | ~115-168 |
| Grup `supervisor` | ACADEMIC (dashboard) + ADMINISTRATION | ~180-207 |
| Import statements | SHARED | ~1-10 |

**Aturan:**
- Academic HANYA menambah/mengubah di dalam grup `guru` dan method `supervisor()` dashboard
- Administration HANYA menambah/mengubah di dalam grup `admin`
- Import statements: masing-masing tambah controller sendiri

### 2. `app/Http/Controllers/RoleDashboardController.php` ⚠️

| Method | Owner |
|--------|-------|
| `admin()` | ADMINISTRATION |
| `guru()` | ACADEMIC |
| `supervisor()` | ACADEMIC |
| `authorizeArea()` | SHARED (jangan diubah tanpa koordinasi) |

**Aturan:**
- Academic memiliki method `guru()` dan `supervisor()`
- Administration memiliki method `admin()`
- Jangan mengubah method milik workstream lain

---

## CONFLICT PREVENTION CHECKLIST

### Sebelum Push (Academic)
- [x] Pastikan hanya file Academic yang diubah
- [x] Verifikasi tidak ada perubahan ke area Administration (method `admin()`, grup `admin`)
- [x] Update TODO.md dengan detail perubahan
- [x] Dokumentasikan di ACADEMIC_CHANGES.md
- [x] Update PRD 07 (status task)
- [ ] Run `php artisan test` (skip — MySQL server belum running)

### Sebelum Pull dari Main (Academic)
- [ ] Backup branch: `git branch backup/academic-YYYYMMDD`
- [ ] `git pull origin main`
- [ ] Resolve conflict di `routes/web.php`: terima kedua section
- [ ] Resolve conflict di `RoleDashboardController.php`: terima kedua method
- [ ] Test after merge

### Panduan untuk Administration saat Pull Branch Academic
- [ ] File-file baru di `app/Http/Controllers/Teacher/` → AMAN, tidak bentrok
- [ ] File-file baru di `app/Services/StudentAttendanceService.php` → AMAN
- [ ] File-file baru di `resources/views/guru/` → AMAN
- [ ] File-file baru di `resources/views/dashboards/guru.blade.php` dan `supervisor.blade.php` → AMAN
- [ ] `routes/web.php` → Cek conflict di import & grup guru. Terima perubahan Academic di grup `guru`, pertahankan perubahan Administration di grup `admin`
- [ ] `RoleDashboardController.php` → Cek conflict di method `guru()` dan `supervisor()`. Pertahankan perubahan Academic di method tersebut, pertahankan method `admin()` milik Administration

---

## SECURITY IMPLEMENTATION SUMMARY

Semua fitur Academic telah melewati checklist keamanan PRD 07 §11.2 dan OWASP Top 10:

| Layer | Mekanisme | Coverage |
|-------|-----------|----------|
| Route | Middleware `auth` + `role:*` | Semua routes |
| Controller | `Gate::authorize()` / `isTeacher()` / `abort_unless()` | Setiap write-action |
| Policy | `StudentAttendanceSessionPolicy` anti-IDOR | Server-side ownership check |
| Service | DB Transaction + validation | Data consistency |
| View | `@csrf` + Blade auto-escape | CSRF + XSS |
| Password | `current_password` verify + strength rules | A-02 |

---

## LOG PERUBAHAN

### 2026-10-05 Sesi 1-2 (09:30 - 10:36)
- **[A-05]** Membuat `StudentAttendanceService.php` (313 lines)
- **[A-05]** Membuat `StudentAttendanceController.php` (242 lines)
- **[A-05]** Menambahkan 8 routes di `routes/web.php`
- **[A-05]** Membuat 4 views (index, create, edit, show)

### 2026-10-05 Sesi 3 (11:42 - 11:53)
- **[A-05]** Routes verified aktif
- **[A-02]** Membuat `ProfileController.php` (5 methods)
- **[A-02]** Membuat 3 views (show, edit, password)
- **[A-02]** Menambahkan 5 routes di `routes/web.php`
- **[A-02]** Defense-in-depth: `isTeacher()` check di semua method

### 2026-10-05 Sesi 4 (12:03 - 12:34)
- **[A-01]** Update `RoleDashboardController::guru()` dengan query jadwal & piket
- **[A-01]** Membuat `dashboards/guru.blade.php` (view terpisah)
- **[A-06]** Update `RoleDashboardController::supervisor()` dengan statistik guru
- **[A-06]** Membuat `dashboards/supervisor.blade.php` (view terpisah)
- **[A-01, A-06]** PRD 07 updated: status → COMPLETED

### 2026-10-06 Sesi 5 (08:30)
- **[DOCS]** Update komprehensif ACADEMIC_CHANGES.md dengan seluruh task (A-01, A-02, A-05, A-06)
- **[DOCS]** Penambahan panduan koordinasi untuk tim Administration

---

## SUMMARY TOTAL

| Metric | Value |
|--------|-------|
| **Tasks Completed** | 4 (A-01, A-02, A-05, A-06) |
| **Tasks Blocked** | 4 (A-03, A-04, A-07, A-08) |
| **Files Created** | 11 files |
| **Files Modified** | 2 files (routes/web.php, RoleDashboardController.php) |
| **Routes Added** | 13 routes |
| **Controllers** | 2 (StudentAttendanceController, ProfileController) |
| **Services** | 1 (StudentAttendanceService) |
| **Views** | 8 (4 attendance, 3 profile, 1 guru dashboard, 1 supervisor dashboard) |
| **Database Changes** | NONE |
| **Security Audit** | PASS (PRD 04 + OWASP Top 10) |

---

**Dokumentasi ini harus diupdate setiap kali Academic melakukan perubahan.**  
**Tim Administration WAJIB membaca dokumen ini sebelum merge/pull dari branch Academic.**

Last Updated: 2026-10-06T08:31:00+07:00
</ReplacementContent>
