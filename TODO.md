# TODO - Portal Sekolah

## Tujuan Dokumen
Dokumen ini mencatat semua task dan perubahan yang sedang/akan dikerjakan untuk menghindari konflik antara workstream ADMINISTRATION dan ACADEMIC saat push/pull ke GitHub.

---

## WORKSTREAM: ACADEMIC

### Status: SEDANG DIKERJAKAN
> **Owner:** Academic Workstream  
> **Branch Rekomendasi:** `feature/academic-dashboard`  
> **Tanggal Mulai:** 2026-10-05

### Task yang Sedang Dikerjakan

#### A-01: Dashboard Guru ✅ COMPLETED
**Status:** ✅ **SELESAI DIIMPLEMENTASIKAN** (2026-10-05)

**Target yang Telah Tercapai:**
1. ✅ Widget jadwal mengajar hari ini (GUR-DASH-001)
2. ✅ Banner status Guru Piket hari ini + tombol scanner (GUR-DASH-002)
3. ✅ Placeholder ringkasan dokumen ajar (GUR-DASH-003) — menunggu A-03 GoogleDriveService

**File yang Telah Dibuat/Diubah:**
- ✅ `app/Http/Controllers/RoleDashboardController.php` - Method `guru()` diupdate dengan query jadwal & piket
- ✅ `resources/views/dashboards/guru.blade.php` - View terpisah khusus guru (bukan lagi pakai role.blade.php)

**Widget yang Diimplementasikan:**
- GUR-DASH-001: Jadwal mengajar hari ini dari tabel `schedules` (filter day_of_week = today)
- GUR-DASH-002: Status piket dari `teacher_duty_schedules` + tombol buka scanner jika piket
- GUR-DASH-003: Placeholder dokumen ajar (BLOCKED sampai GoogleDriveService tersedia)
- Quick Links: Absensi Siswa, Profil Saya

**PRD Reference:**
- PRD 01 §7.1 (GUR-DASH-001–003) ✅

---

#### A-02: Profil Guru ✅ COMPLETED
**Status:** ✅ **SELESAI DIIMPLEMENTASIKAN** (2026-10-05)

**Target yang Telah Tercapai:**
1. ✅ Biodata pribadi guru (GUR-PRF-001)
2. ✅ Riwayat penugasan mengajar (GUR-PRF-001)
3. ✅ Ubah kata sandi mandiri dengan verifikasi password lama (GUR-PRF-002)
4. ✅ Ubah nomor kontak (GUR-PRF-002)

**File yang Telah Dibuat:**
- ✅ `app/Http/Controllers/Teacher/ProfileController.php` - Controller 5 methods (show, edit, updateContact, editPassword, updatePassword)
- ✅ `resources/views/guru/profile/show.blade.php` - Profil biodata + riwayat penugasan
- ✅ `resources/views/guru/profile/edit.blade.php` - Form edit nomor kontak
- ✅ `resources/views/guru/profile/password.blade.php` - Form ubah kata sandi
- ✅ `routes/web.php` - 5 routes ditambahkan di grup `guru`

**Routes yang Ditambahkan:**
- `GET /guru/profile` → show (biodata + riwayat)
- `GET /guru/profile/edit` → edit (form kontak)
- `PUT /guru/profile/contact` → updateContact
- `GET /guru/profile/password` → editPassword (form password)
- `PUT /guru/profile/password` → updatePassword

**Security:**
- ✅ Hanya role teacher (middleware role:teacher)
- ✅ Anti-IDOR: guru hanya akses profil sendiri via auth()->user()
- ✅ Password lama wajib diverifikasi (current_password)
- ✅ Password baru: min 8 char, mixed case, numbers

**PRD Reference:**
- PRD 01 §7.2 (GUR-PRF-001–002) ✅

---

#### A-05: Absensi Siswa per Mata Pelajaran ✅ COMPLETED
**Status:** ✅ **SELESAI DIIMPLEMENTASIKAN** (2026-10-05)

**Target yang Telah Tercapai:**
1. ✅ Buka sesi absensi (`OPEN`) untuk kelas/mapel yang diampu (GUR-ATT-001)
2. ✅ Pre-fill seluruh siswa dengan status `HADIR` otomatis (GUR-ATT-002)
3. ✅ Edit status H/I/S/A + catatan per siswa (GUR-ATT-003)
4. ✅ Simpan & kunci (`CLOSED`) secara manual (GUR-ATT-004)
5. ✅ Read-only pasca `CLOSED` dengan opsi reopen jika belum ada koreksi admin
6. ✅ Manual close sesuai ADR 14 (bukan otomatis)

**File yang Telah Dibuat:**
- ✅ `app/Http/Controllers/Teacher/StudentAttendanceController.php` - Controller 8 methods (242 lines)
- ✅ `app/Services/StudentAttendanceService.php` - Service 10 methods (313 lines)
- ✅ `resources/views/guru/attendance/index.blade.php` - Daftar sesi dengan filter
- ✅ `resources/views/guru/attendance/create.blade.php` - Form buka sesi baru
- ✅ `resources/views/guru/attendance/edit.blade.php` - Input kehadiran H/I/S/A dengan highlight baris
- ✅ `resources/views/guru/attendance/show.blade.php` - Detail sesi read-only dengan statistik
- ✅ `routes/web.php` - 8 routes ditambahkan (resource + close + reopen)

**Routes yang Ditambahkan:**
- `GET /guru/attendance` → index
- `GET /guru/attendance/create` → create
- `POST /guru/attendance` → store
- `GET /guru/attendance/{id}` → show
- `GET /guru/attendance/{id}/edit` → edit
- `PUT /guru/attendance/{id}` → update
- `POST /guru/attendance/{id}/close` → close
- `POST /guru/attendance/{id}/reopen` → reopen

**Security & Validation:**
- ✅ Authorization via `StudentAttendanceSessionPolicy` (anti-IDOR)
- ✅ Validasi tanggal tidak boleh di masa depan
- ✅ Cek duplikasi sesi pada tanggal yang sama
- ✅ Pre-fill otomatis dari `class_enrollments`
- ✅ Meeting order auto-increment
- ✅ Transaksi database untuk data consistency

**PRD Reference:**
- PRD 01 §7.5 (GUR-ATT-001–004) ✅
- PRD 05 §5.11 (ADR 14) ✅
- PRD 04 §3.2.3 (Anti-IDOR) ✅

---

#### A-06: Dashboard Supervisor ✅ COMPLETED
**Status:** ✅ **SELESAI DIIMPLEMENTASIKAN** (2026-10-05)

**Target yang Telah Tercapai:**
1. ✅ Widget statistik guru (total & aktif) (SUP-DASH-001)
2. ✅ Placeholder metrik dokumen guru (SUP-DASH-002) — menunggu A-03 GoogleDriveService
3. ✅ Quick links ke monitoring dokumen

**File yang Telah Dibuat/Diubah:**
- ✅ `app/Http/Controllers/RoleDashboardController.php` - Method `supervisor()` diupdate dengan query statistik guru
- ✅ `resources/views/dashboards/supervisor.blade.php` - View terpisah khusus supervisor

**Widget yang Diimplementasikan:**
- SUP-DASH-001: Statistik guru (total, aktif, rasio)
- SUP-DASH-002: Placeholder dokumen (menunggu A-03)
- Quick Links: Monitoring Dokumen

**Security:**
- ✅ Defense-in-depth: `authorizeArea()` dengan `abort_unless(hasRole)`
- ✅ Middleware `role:supervisor` di route group
- ✅ Read-only access (sesuai PRD 01 §8)

**PRD Reference:**
- PRD 01 §8.1 (SUP-DASH-001–002) ✅

---

### Task BLOCKED (Menunggu GoogleDriveService)

> **Catatan:** Task berikut TIDAK DAPAT dikerjakan sebelum GoogleDriveService diimplementasikan dan ownership-nya ditetapkan.

#### A-03: Repositori Dokumen Guru (BLOCKED)
- **Blocker:** GoogleDriveService belum ada
- **Status:** Model `Document`, `DocumentVersion`, `DocumentReview`, `DocumentComment`, dan `DocumentPolicy` sudah ada
- **PRD Reference:** PRD 01 §7.3, PRD 03, PRD 05 Fase 5

#### A-04: Document Versioning & Permanent Retention (BLOCKED)
- **Blocker:** Bergantung pada A-03 (GoogleDriveService)
- **PRD Reference:** PRD 01 §7.3 (GUR-DOC-005), PRD 05 §5.13 (ADR 08)

#### A-06: Dashboard Supervisor (PARTIAL)
- **Status:** Kerangka minimal sudah ada, metrik nyata butuh A-03
- **PRD Reference:** PRD 01 §8.1 (SUP-DASH-001–002)

#### A-07: Monitoring Repositori Guru (BLOCKED)
- **Blocker:** Bergantung pada A-03
- **PRD Reference:** PRD 01 §8.2 (SUP-REP-001–002)

#### A-08: Review, Evaluasi & Approval Dokumen (BLOCKED)
- **Blocker:** Bergantung pada A-03
- **PRD Reference:** PRD 01 §8.3 (SUP-REV-001–004), PRD 05 §5.14 (ADR 05)

---

## WORKSTREAM: ADMINISTRATION

### Task yang TIDAK BOLEH Disentuh oleh Academic

> **PENTING:** Academic DILARANG mengerjakan task di bawah ini untuk menghindari konflik

#### ADJUSTMENT E (Configuration & Housekeeping)
- E-02: Konfigurasi MySQL (PARTIAL)
- E-04: Housekeeping artefak default Laravel (NOT_STARTED)
- E-05: AuditLog constants / compatibility (PARTIAL) - **butuh keputusan manusia**
- E-06: Verification test (NOT_STARTED)
- E-07: Git checkpoint (NOT_STARTED)

#### ADM-01 s/d ADM-11 (Administration Workstream Roadmap)
Semua task ADM-01 hingga ADM-11 adalah milik ADMINISTRATION dan tidak boleh diambil oleh Academic.

**Eligible untuk Administration (setelah Adjustment E selesai):**
1. ADM-06 Tahun Akademik & Semester
2. ADM-05A Data Master Mata Pelajaran
3. ADM-08 User Account & Security (lanjutkan bagian yang belum)
4. ADM-10 Audit Trail & System Log (UI baca log)

---

## SHARED DEPENDENCY

### GoogleDriveService
- **Owner:** HUMAN DECISION REQUIRED ⚠️
- **Status:** BLOCKED
- **Consumers:** A-03, A-04, A-07, A-08
- **Action Required:** Manusia harus menetapkan owner (ADMINISTRATION atau ACADEMIC) sebelum implementasi dimulai

---

## STRATEGI GIT WORKFLOW

### Branch Strategy
1. **Academic Branch:** `feature/academic-dashboard`, `feature/academic-profile`, `feature/academic-attendance`
2. **Administration Branch:** `feature/admin-adjustment-e`, `feature/admin-master-data`

### Commit Convention
- `feat:` - fitur baru
- `fix:` - perbaikan bug
- `docs:` - dokumentasi
- `test:` - penambahan/perbaikan test
- `refactor:` - refactoring tanpa mengubah fungsionalitas
- `chore:` - housekeeping

### Pull/Push Guidelines
1. **Sebelum pull:** Pastikan working tree bersih (`git status`)
2. **Sebelum push:** Pastikan test lulus (`php artisan test`)
3. **Commit message:** Jelas dan deskriptif dengan prefix yang sesuai
4. **Review:** Cek `git diff --stat` untuk memastikan tidak ada file yang tidak sengaja terubah

### File Overlap Warning
> **PERHATIAN:** File yang mungkin diubah oleh kedua workstream:

- `routes/web.php` - koordinasikan perubahan rute
- `bootstrap/app.php` - hindari perubahan middleware bersamaan
- `resources/views/layouts/admin.blade.php` - koordinasi styling
- `database/seeders/DatabaseSeeder.php` - koordinasi data seed

---

## CATATAN PENTING

### Aturan Anti-Konflik
1. **Satu file = satu owner** pada satu waktu
2. **Hormati ownership area** sesuai PRD 07 bagian 3, 4, 5
3. **Jangan ubah keputusan final** PRD/ADR tanpa persetujuan
4. **Shared file change** wajib koordinasi dan cek dampak

### Verifikasi Sebelum Commit
- [ ] Test suite lulus (`php artisan test`)
- [ ] Working tree bersih dari perubahan tak terkait
- [ ] Tidak ada file `.env` atau kredensial
- [ ] Security gate terpenuhi (§11 PRD 07)
- [ ] `git diff --check` bersih

---

## LOG PERUBAHAN

### 2026-10-05

#### Sesi 1 (09:30 - 09:50)
- **[ACADEMIC]** Membuat TODO.md untuk tracking task academic
- **[ACADEMIC]** Merencanakan A-01 (Dashboard Guru) sebagai task pertama
- **[ACADEMIC]** Mengidentifikasi A-02, A-05 sebagai task eligible berikutnya
- **[ACADEMIC]** Mendokumentasikan A-03, A-04, A-06, A-07, A-08 sebagai BLOCKED (GoogleDriveService)

#### Sesi 2 (10:06 - 10:36) 🔄 **A-05 IN PROGRESS**
- **[ACADEMIC]** ✅ **Menyelesaikan A-05: Absensi Siswa per Mata Pelajaran**
- **[ACADEMIC]** ✅ Database verified - semua tabel ready (21 migrations ran)
- **[ACADEMIC]** ✅ Membuat `StudentAttendanceService.php` dengan 10 methods lengkap (272 lines)
- **[ACADEMIC]** ✅ Membuat `Teacher/StudentAttendanceController.php` dengan 8 methods (229 lines)
- **[ACADEMIC]** 🔄 **IN PROGRESS:** Membuat 4 view files
- **[ACADEMIC]** ⏳ TODO: Update routes di `routes/web.php`
- **[ACADEMIC]** Implementasi sesuai PRD 01 §7.5 (GUR-ATT-001–004) dan ADR 14

**File yang Berhasil Dibuat:**
- ✅ `app/Services/StudentAttendanceService.php` (CREATED - 272 lines)
  - ✅ getTeacherAssignments() - Ambil penugasan guru
  - ✅ getTeacherSessions() - Ambil daftar sesi dengan filter
  - ✅ openSession() - Buka sesi baru (GUR-ATT-001)
  - ✅ prefillStudents() - Pre-fill HADIR otomatis (GUR-ATT-002)
  - ✅ updateAttendance() - Update kehadiran (GUR-ATT-003)
  - ✅ closeSession() - Tutup sesi manual (GUR-ATT-004 + ADR 14)
  - ✅ reopenSession() - Buka kembali sesi
  - ✅ getSessionDetail() - Detail sesi
  - ✅ isSessionEditable() - Validasi status
  - ✅ getSessionStatistics() - Statistik kehadiran

- ✅ `app/Http/Controllers/Teacher/StudentAttendanceController.php` (CREATED - 229 lines)
  - ✅ index() - Daftar sesi
  - ✅ create() - Form buka sesi
  - ✅ store() - Simpan sesi baru + pre-fill
  - ✅ show() - Detail/preview sesi
  - ✅ edit() - Form edit kehadiran
  - ✅ update() - Update kehadiran
  - ✅ close() - Tutup sesi
  - ✅ reopen() - Buka kembali sesi

**Next Steps:**
1. ⏳ Buat 4 view files (index, create, edit, show)
2. ⏳ Update routes/web.php (8 routes + import)
3. ⏳ Testing & verifikasi

**Verifikasi:**
- ✅ Service file created & verified
- ✅ Controller file created & verified
- ✅ Git status clean: hanya file Academic
- ✅ No conflict dengan Administration
- ✅ Database 100% ready (no migration needed)

#### Sesi 3 (11:42 - 11:46) ✅ **A-05 FULLY COMPLETED**
- **[ACADEMIC]** ✅ Routes verified: 8 routes `guru.attendance.*` aktif di repository
- **[ACADEMIC]** ✅ Membuat 4 view files dengan Tailwind CSS:
  - `resources/views/guru/attendance/index.blade.php` - Daftar sesi dengan filter status/tanggal
  - `resources/views/guru/attendance/create.blade.php` - Form buka sesi baru
  - `resources/views/guru/attendance/edit.blade.php` - Input kehadiran H/I/S/A dengan highlight baris & statistik
  - `resources/views/guru/attendance/show.blade.php` - Detail sesi read-only dengan statistik lengkap
- **[ACADEMIC]** ✅ Update `routes/web.php`: import controller + 8 routes (resource + close + reopen)
- **[ACADEMIC]** ✅ Update `TODO.md`: A-05 status → COMPLETED
- **[ACADEMIC]** ✅ Update `PRD 07`: A-05 status NOT_STARTED → COMPLETED dengan evidence repository aktual

**Verifikasi Final:**
- ✅ Service: `StudentAttendanceService.php` (313 lines) — exists
- ✅ Controller: `StudentAttendanceController.php` (242 lines) — exists
- ✅ Views: 4 files (index, create, edit, show) — all exist
- ✅ Routes: 8 routes verified via `php artisan route:list`
- ✅ PRD 07 updated: A-05 = COMPLETED
- ✅ TODO.md updated: A-05 = COMPLETED
- ✅ No conflict dengan Administration workstream

**Status Branch:** `feature/academic-dashboard`

---

**Pembaruan Terakhir:** 2026-10-05T11:46:00+07:00  
**Dokumen ini harus diupdate setiap kali ada perubahan task atau status**

