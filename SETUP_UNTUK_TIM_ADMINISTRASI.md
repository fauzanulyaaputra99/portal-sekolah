# 📦 Setup Project untuk Tim Administrasi

## 🎯 Branch: `feature/academic-dashboard`

Dokumen ini berisi langkah-langkah setup project untuk melanjutkan development di area **Administration**.

---

## 1️⃣ Clone/Pull Repository

### Jika Belum Clone
```bash
git clone https://github.com/fauzanulyaaputra99/portal-sekolah.git
cd portal-sekolah
git checkout feature/academic-dashboard
```

### Jika Sudah Clone (Pull Update Terbaru)
```bash
cd portal-sekolah
git checkout feature/academic-dashboard
git pull origin feature/academic-dashboard
```

---

## 2️⃣ Install Dependencies

### Install PHP Dependencies
```bash
composer install
```

### Install Node Dependencies
```bash
npm install
```

---

## 3️⃣ Setup Environment

### Copy File Environment
```bash
copy .env.example .env
```

### Generate Application Key
```bash
php artisan key:generate
```

### Edit File `.env`
Sesuaikan konfigurasi database:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=portal_sekolah
DB_USERNAME=root
DB_PASSWORD=
```

---

## 4️⃣ Setup Database

### Opsi A: Menggunakan Migration (Recommended)

**Buat database kosong dulu:**
```sql
CREATE DATABASE portal_sekolah CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Jalankan migration:**
```bash
php artisan migrate
```

**Seed data dummy (opsional):**
```bash
php artisan db:seed
```

### Opsi B: Import SQL File Demo

**File SQL tersedia di:** `database/demo/portal_sekolah_demo.sql`

**Cara import:**
```bash
mysql -u root -p portal_sekolah < database/demo/portal_sekolah_demo.sql
```

Atau via phpMyAdmin:
1. Buka phpMyAdmin
2. Pilih database `portal_sekolah`
3. Tab "Import"
4. Pilih file `portal_sekolah_demo.sql`
5. Klik "Go"

---

## 5️⃣ Compile Assets

### Development Mode (dengan hot reload)
```bash
npm run dev
```
Biarkan terminal ini tetap running.

### Production Build
```bash
npm run build
```

---

## 6️⃣ Jalankan Server

**Buka terminal baru, jalankan:**
```bash
php artisan serve
```

**Akses aplikasi di:** http://localhost:8000

---

## 7️⃣ Login Test

### User Admin
- **Username:** `admin_tu`
- **Password:** `password123`
- **Role:** Admin/TU

### User Guru (untuk test)
- **Username:** `198501012010011001`
- **Password:** `password123`
- **Role:** Teacher

### User Supervisor (untuk test)
- **Username:** `supervisor_kepsek`
- **Password:** `password123`
- **Role:** Supervisor

---

## 8️⃣ Verifikasi Migrasi

Cek status migrasi:
```bash
php artisan migrate:status
```

Semua harus status **"Ran"**.

---

## 9️⃣ Struktur Area Admin yang Sudah Ada

### ✅ Yang Sudah Selesai:
- **Layout Admin** (`layouts/admin.blade.php`)
  - Sidebar fixed dengan menu
  - Dark mode toggle
  - Turbo Drive support
  
- **Dashboard Admin** (`dashboards/admin.blade.php`)
  - Quick access cards
  - Modern UI

- **Form Styling** (Global CSS)
  - Semua input sudah diseragamkan
  - Min-height: 42px
  - Dark mode support

### 🚧 Yang Perlu Dikerjakan (Tim Administrasi):

Urutan prioritas sesuai PRD 07:

1. **ADM-06** - Tahun Akademik & Semester
2. **ADM-05A** - Data Master Mata Pelajaran
3. **ADM-08** - User Account & Security (lanjutkan bagian CRUD)
4. **ADM-02** - Data Master Guru
5. **ADM-04** - Kelas & Tingkat
6. **ADM-03** - Data Master Siswa
7. **ADM-07** - Teaching Assignment
8. **ADM-05B** - Jadwal Pelajaran
9. **ADM-10** - Audit Trail UI (baca log)
10. **ADM-01** - Dashboard metrics lengkap
11. **ADM-11** - Laporan & Ekspor

---

## 🔟 Cara Membuat CRUD Baru

### Template Structure:
```
app/Http/Controllers/Admin/
  └── NamaController.php

resources/views/admin/nama/
  ├── index.blade.php    (list/table)
  ├── create.blade.php   (form tambah)
  ├── edit.blade.php     (form edit)
  └── show.blade.php     (detail, opsional)
```

### Extend Layout Admin:
```blade
@extends('layouts.admin')

@section('title', 'Judul Halaman')
@section('subtitle', 'Deskripsi')

@section('content')
    <!-- Konten halaman -->
@endsection
```

### Form Input Sudah Auto-Styled:
Semua input type berikut sudah auto-styled (42px height, dark mode):
- `<input type="text">`
- `<input type="email">`
- `<input type="password">`
- `<input type="number">`
- `<input type="date">`
- `<select>`
- `<textarea>`

Tidak perlu tambah class khusus, cukup tulis HTML standar!

---

## 1️⃣1️⃣ Routes Admin

**Tambahkan di** `routes/web.php` **dalam grup admin:**

```php
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Route admin Anda di sini
        Route::resource('subjects', SubjectController::class);
    });
```

---

## 1️⃣2️⃣ Dokumentasi PRD

**Folder PRD:** `../portal-sekolah-prd/`

File yang perlu dibaca:
- `01-product-requirements.md` - Requirements utama
- `02-architecture-database.md` - Database schema
- `04-security-operations.md` - Security guidelines
- `05-consolidated-prd.md` - PRD final
- `06-decision-resolution.md` - ADR (keputusan)
- `07-agent-collaboration-workflow.md` - Workflow & roadmap

---

## 1️⃣3️⃣ Testing

Jalankan test sebelum commit:
```bash
php artisan test
```

Semua test harus **passing (hijau)**.

---

## 1️⃣4️⃣ Git Workflow

### Sebelum Mulai Kerja:
```bash
git pull origin feature/academic-dashboard
```

### Setelah Selesai Coding:
```bash
git add .
git status
php artisan test
git commit -m "feat(admin): Deskripsi perubahan"
git push origin feature/academic-dashboard
```

---

## 1️⃣5️⃣ Kontak & Koordinasi

- **Academic Workstream:** @LintangAzani
- **Administration Workstream:** @fauzanulyaaputra99

**Koordinasi jika:**
- Perlu ubah shared files (`routes/web.php`, `RoleDashboardController.php`)
- Perlu tambah migration baru
- Perlu ubah model/policy yang dipakai bersama

---

## ❓ Troubleshooting

### Error: "Please provide a valid cache path"
```bash
mkdir storage\framework\cache\data
mkdir storage\framework\sessions
mkdir storage\framework\views
```

### Error: Migration gagal
```bash
php artisan migrate:fresh --seed
```

### Error: CSS/JS tidak load
```bash
npm run build
php artisan optimize:clear
```

### Error: Permission denied
```bash
# Windows (run as Administrator)
icacls storage /grant Users:F /t
icacls bootstrap\cache /grant Users:F /t
```

---

## 📞 Butuh Bantuan?

1. Cek `ACADEMIC_CHANGES.md` - Dokumentasi perubahan Academic
2. Cek `TODO.md` - Task list
3. Baca PRD 07 - Workflow & roadmap lengkap

**Happy Coding! 🚀**
