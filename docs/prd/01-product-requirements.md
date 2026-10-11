# PRODUCT REQUIREMENTS DOCUMENT (PRD)
## SISTEM PORTAL SEKOLAH

**Nomor Dokumen:** PRD-SEKOLAH-001  
**Versi Dokumen:** 2.1.0 (FINAL — ADDENDUM + Keputusan A1/B2/C1 disahkan)  
**Status:** APPROVED FOR DEVELOPMENT  
**Role Penyusun:** Senior Product Manager & Senior System Analyst  
**Target Rilis:** MVP (Minimum Viable Product)  
**Tanggal Revisi ADDENDUM:** 2026-09-27 · **Finalisasi Keputusan:** 2026-09-27  

---

## DAFTAR ISI
1. [Ringkasan Eksekutif & Konteks Proyek](#1-ringkasan-eksekutif--konteks-proyek)
2. [Tujuan Sistem & Value Proposition](#2-tujuan-sistem--value-proposition)
3. [Tech Stack Constraints & Arsitektur Tingkat Tinggi](#3-tech-stack-constraints--arsitektur-tingkat-tinggi)
4. [Prinsip Produk & Pedoman Desain](#4-prinsip-produk--pedoman-desain)
5. [User Personas & Role-Based Access Control (RBAC)](#5-user-personas--role-based-access-control-rbac)
6. [Kebutuhan Fungsional: Admin & Staff Tata Usaha (TU)](#6-kebutuhan-fungsional-admin--staff-tata-usaha-tu)
7. [Kebutuhan Fungsional: Guru](#7-kebutuhan-fungsional-guru)
8. [Kebutuhan Fungsional: Atasan / Supervisor](#8-kebutuhan-fungsional-atasan--supervisor)
9. [Document Management Lifecycle & Workflow](#9-document-management-lifecycle--workflow)
10. [Document Versioning & Storage Strategy](#10-document-versioning--storage-strategy)
11. [Document Review & Feedback System](#11-document-review--feedback-system)
12. [Student Subject Attendance (Absensi Siswa Per Mapel)](#12-student-subject-attendance-absensi-siswa-per-mapel)
13. [Student School Attendance — Absensi Masuk/Pulang Sekolah (Barcode Scanner)](#13-student-school-attendance--absensi-masukpulang-sekolah-barcode-scanner)
14. [Dynamic Class Architecture & Academic Progression](#14-dynamic-class-architecture--academic-progression)
15. [Teaching Assignment & Permission Scoping](#15-teaching-assignment--permission-scoping)
16. [Reporting, Analytics & Audit Logging](#16-reporting-analytics--audit-logging)
17. [Spesifikasi Dashboard per Role](#17-spesifikasi-dashboard-per-role)
18. [User Stories Berdasarkan Role](#18-user-stories-berdasarkan-role)
19. [End-to-End User Flows](#19-end-to-end-user-flows)
20. [Non-Goals & Scope Exclusions (Batasan Sistem)](#20-non-goals--scope-exclusions-batasan-sistem)
21. [MVP Scope & Feature Prioritization (MoSCoW)](#21-mvp-scope--feature-prioritization-moscow)
22. [Acceptance Criteria (Gherkin Format)](#22-acceptance-criteria-gherkin-format)
23. [Riwayat Keputusan & Open Questions (CLOSED — FINAL)](#23-riwayat-keputusan--open-questions-closed--final)

---

## 1. RINGKASAN EKSEKUTIF & KONTEKS PROYEK

### 1.1 Latar Belakang
Operasional sekolah membutuhkan integrasi pencatatan administratif yang rapi, transparan, dan dapat dipertanggungjawabkan. Saat ini, pengumpulan perangkat ajar guru (RPP, ATP, PROTA, PROSEM) kerap dilakukan melalui media pesan instan, flashdisk, atau email pribadi sehingga menyulitkan pengarsipan, riwayat revisi, dan proses supervisi akademik oleh pimpinan sekolah. Di sisi lain, pencatatan absensi guru dan absensi kehadiran siswa per jam mata pelajaran masih bergantung pada lembar kertas atau entri manual staf Tata Usaha (TU) yang rentan manipulasi, lambat direkap, dan membebani administrasi sekolah.

### 1.2 Nama Proyek
Nama Proyek Resmi: **Portal Sekolah** (Dedicated School Management & Academic Repository).

### 1.3 Penegasan Batasan Kritis (CRITICAL BOUNDARY)
> **PERINGATAN KERAS / SYSTEM BOUNDARY:**  
> Aplikasi Portal Sekolah adalah sistem mandiri baru dan **SEPENUHNYA TERPISAH dari sistem absensi berbasis RFID yang sudah ada**.  
> Sistem ini **TIDAK MENGGUNAKAN DAN TIDAK TERHUBUNG DENGAN RFID**:
> * Tidak ada kartu RFID (RFID Cards)
> * Tidak ada pembaca kartu fisik (RFID Reader)
> * Tidak ada endpoint/API pengolahan sinyal RFID
> * Tidak ada integrasi attendance mesin RFID
> * Tidak ada NFC, GPS, atau perangkat keras biometrik
>
> **Absensi Masuk/Pulang Sekolah** menggunakan **barcode pada kartu fisik siswa** yang di-scan melalui **kamera HP Guru Piket** via Web App/Browser. Barcode di sini adalah barcode visual (1D/2D) yang dicetak pada kartu, **bukan** chip RFID/NFC.
>
> **Absensi Mata Pelajaran** dilakukan secara digital murni berbasis autentikasi web session (user credentials) oleh Guru pengampu.
>
> **Absensi Mandiri Guru (Teacher Self-Attendance) = DIHAPUS TOTAL.** Tidak ada lagi fitur presensi mandiri harian guru di Portal Sekolah (ADDENDUM §20 & §26).

---

## 2. TUJUAN SISTEM & VALUE PROPOSITION

Portal Sekolah dirancang untuk mencapai 8 (delapan) pilar objektif:
1. **Pusat Pengelolaan Data Master Sekolah:** Menjadi basis data tunggal (*Single Source of Truth*) untuk data guru, siswa, kelas, mata pelajaran, tahun akademik, dan penugasan mengajar (*teaching assignments*).
2. **Digital Document Repository Terstruktur:** Menyediakan repositori digital terpusat untuk berkas perangkat ajar dan administrasi keguruan tanpa tercecer.
3. **Supervisi Akademik Transparan:** Memfasilitasi pimpinan (Kepala Sekolah/Wakil Kepala Sekolah/Pengawas) untuk memantau, memeriksa, dan mengevaluasi kelengkapan dokumen ajar guru.
4. **Audit Trail Revisi Dokumen:** Mendukung mekanisme review dua arah, umpan balik (komentar), persetujuan (*approval*), dan penyimpanan riwayat versi berkas secara *immutable* (tidak saling menimpa).
5. **Absensi Masuk/Pulang Siswa (Student School Attendance):** Mencatat kedatangan dan kepulangan siswa setiap hari melalui scanner barcode kamera HP yang dioperasikan Guru Piket, dengan mode MASUK dan PULANG yang dipilih secara eksplisit (ADDENDUM §1–§7).
6. **Absensi Siswa Berbasis Jam Pelajaran (Subject Attendance):** Memungkinkan guru mencatat absensi siswa secara langsung di setiap sesi kelas mata pelajaran yang diampunya.
7. **Struktur Kelas Dinamis (Dynamic Class Growth):** Menjamin fleksibilitas penambahan jenjang, rombongan belajar (rombel), dan siswa baru secara dinamis melalui antarmuka admin tanpa perlu modifikasi basis data atau *source code*.
8. **Keamanan & Kepatuhan Tata Kelola Berkas:** Menjaga kerahasiaan dokumen sekolah menggunakan kontrol akses ketat (*Role-Based Access Control*) dan penyimpanan cloud terenkripsi via Google Drive API.

---

## 3. TECH STACK CONSTRAINTS & ARSITEKTUR TINGKAT TINGGI

### 3.1 Constraint Teknologi Terpilih
Sistem dirancang dengan batas teknologi yang disepakati untuk menjamin efisiensi, kemudahan pemeliharaan (*maintainability*), dan kesederhanaan operasional:
* **Backend Framework:** Laravel 13 (Arsitektur Monolitik Modular)
* **Bahasa Pemrograman:** PHP (versi LTS/didukung Laravel 13)
* **Database Management System:** MySQL 8.0+ (InnoDB Engine)
* **Database Driver:** Laravel MySQL / PDO MySQL
* **Local Development Environment:** Laragon on Windows (Apache/Nginx + PHP + MySQL)
* **Production Hosting/Server:** Laravel 13 & MySQL 8.0+ compatible server/hosting (Platform-Agnostic)
* **Server-Side Templating:** Laravel Blade
* **Styling & UI Components:** Tailwind CSS (dengan utility classes yang konsisten)
* **Frontend Scripting:** Vanilla JavaScript modern (tanpa ketergantungan framework berat untuk MVP)
* **Asset Bundler:** Vite
* **Package Management:** Composer (PHP) & npm (Node.js/Tailwind)
* **Document Cloud Storage:** Google Drive API (Service Account / Standard Account Integration)

### 3.2 Implikasi Arsitektur
1. **Monolith First:** Aplikasi dikembangkan sebagai satu kesatuan (*monolith*) yang terbagi dalam modul fungsional yang bersih (Domain/Module-based folders jika diperlukan).
2. **Tanpa Frontend SPA Kompleks:** Tidak menggunakan React, Vue, atau Angular pada fase MVP untuk menghindari overhead kompilasi ganda, kompleksitas state management di client, serta latency komunikasi REST/GraphQL yang tidak esensial.
3. **Storage Abstraction:** Berkas fisik dokumen guru tidak disimpan langsung pada disk lokal server aplikasi demi mencegah overload disk dan memudahkan backup/scale. Berkas dikirim langsung atau disinkronkan ke Google Drive via Laravel Storage Disks (Flysystem Google Drive adapter), sementara metadata (ID file Google Drive, nama, MIME type, ukuran file, checksum SHA-256) disimpan dalam MySQL.
4. **Local Development Simplicity:** Pengembangan lokal menggunakan Laragon pada Windows untuk orkestrasi PHP, MySQL, dan web server, tanpa membebani developer dengan konfigurasi kompleks.

---

## 4. PRINSIP PRODUK & PEDOMAN DESAIN

Sistem dibangun dengan mengusung filosofi utama:
> *"Simple enough for a school, secure enough for production."*

### 4.1 Hierarki Prioritas Non-Fungsional
1. **Security:** Autentikasi kuat, otorisasi berbasis hak akses ketat (RBAC), sanitasi file upload, dan perlindungan CSRF/XSS/SQL Injection.
2. **Simplicity:** Antarmuka intuitif bagi tenaga pendidik dengan berbagai tingkat literasi digital. Alur kerja tidak berbelit-belit.
3. **Maintainability:** Struktur kode Laravel yang mengikuti standar PSR-12, clean architecture, dan dokumentasi modul yang jelas.
4. **Reliability:** Data absensi dan dokumen tidak boleh hilang (*zero data loss*); transisi status dokumen bersifat deterministik.
5. **Realistic Scalability:** Mampu menangani lonjakan beban saat jam masuk sekolah (absensi serentak) dan akhir semester (pengumpulan dokumen massal).
6. **Auditability:** Setiap perubahan status dokumen, pembukaan sesi absensi, dan koreksi data kehadiran oleh admin harus meninggalkan jejak audit (*audit trail*).
7. **Ease of Use:** Antarmuka responsif (dapat diakses nyaman melalui desktop laptop sekolah maupun smartphone guru).

### 4.2 Kategorisasi Fitur (Framework MoSCoW)
* **MUST HAVE:** Kebutuhan absolut yang menjadi syarat peluncuran MVP.
* **SHOULD HAVE:** Fitur bernilai tinggi yang diimplementasikan segera setelah MVP stabil.
* **NICE TO HAVE:** Fitur pelengkap kenyamanan jika alokasi waktu dan sumber daya mencukupi.
* **FUTURE:** Rencana jangka panjang yang memerlukan riset atau arsitektur tambahan.

---

## 5. TARGET USER DAN ROLE-BASED ACCESS CONTROL (RBAC)

Sistem menetapkan 3 (tiga) peran pengguna formal dengan batasan kewenangan yang tegas:

```
+-------------------------------------------------------------------------------+
|                             PORTAL SEKOLAH RBAC                               |
+-------------------------------------------------------------------------------+
| 1. ADMIN / STAFF TU    | 2. GURU                      | 3. ATASAN / SUPERVISOR |
| - Pengelolaan Master   | - Manajemen Profil Pribadi   | - Monitoring Dokumen   |
| - Manajemen Akun/User  | - Repositori Dokumen Sendiri | - Review & Verifikasi  |
| - Penugasan Mengajar   | - Absensi Kehadiran Harian   | - Approval Dokumen     |
| - Monitoring Global    | - Absensi Siswa per Sesi     | - Rekapitulasi Supervisi|
| - Koreksi & Audit Log  |   (Sesuai Assignment)        |                        |
+-------------------------------------------------------------------------------+
```

### 5.1 Role 1: Admin / Staff Tata Usaha (TU)
* **Deskripsi:** Administrator operasional sekolah yang bertanggung jawab atas pengelolaan data referensi, akun pengguna, penjadwalan penugasan, dan pemantauan kepatuhan administratif.
* **Tanggung Jawab Utama:**
  * Mengelola tahun akademik aktif dan semester.
  * Mengelola data master guru, siswa, kelas, dan mata pelajaran.
  * Menetapkan penugasan mengajar (*teaching assignments*) guru terhadap rombel dan mata pelajaran.
  * **Mengelola jadwal piket guru** (`teacher_duty_schedules`): Tambah, Edit, Hapus, Ganti Guru, Ganti Tanggal (ADDENDUM §9).
  * Membuat, mengaktifkan, menonaktifkan, atau mereset kredensial akun pengguna.
  * Memantau kelengkapan unggahan dokumen dan rekap kehadiran.
  * Melakukan koreksi absensi siswa (masuk/pulang sekolah maupun absensi mata pelajaran) dengan kewajiban mengisi alasan justifikasi koreksi.
  * Mengakses dan mengunduh laporan rekapitulasi administratif.
  * Memantau log audit sistem.

### 5.2 Role 2: Guru (Tenaga Pendidik)
* **Deskripsi:** Pengguna operasional pengajaran yang bertugas mengunggah kelengkapan ajar serta mengelola absensi kelas hariannya.
* **Tanggung Jawab Utama:**
  * Memelihara informasi profil pribadi dan kredensial akses.
  * Mengunggah, memperbarui, dan mengelola dokumen perangkat ajar (RPP, ATP, PROTA, PROSEM, modul ajar, dll.) ke dalam repositori pribadinya.
  * Mengajukan dokumen untuk ditinjau oleh Atasan.
  * Merespons catatan revisi dari Atasan dengan mengunggah versi baru.
  * **Jika bertugas sebagai Guru Piket (berdasarkan `teacher_duty_schedules` hari ini):** Membuka scanner barcode kamera HP, memilih mode MASUK atau PULANG secara eksplisit, dan men-scan barcode kartu siswa untuk mencatat absensi masuk/pulang sekolah (ADDENDUM §1–§7).
  * Membuka sesi absensi siswa, mencatat kehadiran siswa (Hadir, Izin, Sakit, Alpa) per sesi mata pelajaran yang diampunya, dan menutup sesi absensi.

### 5.3 Role 3: Atasan / Supervisor
* **Deskripsi:** Pimpinan sekolah (Kepala Sekolah, Wakil Kepala Sekolah Bidang Kurikulum, Koordinator Pengawas) yang bertugas melakukan penjaminan mutu akademik tenaga pendidik.
* **Tanggung Jawab Utama:**
  * Memantau status kepatuhan pengumpulan perangkat ajar seluruh guru.
  * Memeriksa dan mempelajari isi dokumen yang diajukan oleh guru.
  * Memberikan umpan balik kualitatif berupa catatan review atau alasan penolakan/revisi.
  * Menyetujui (*Approve*) dokumen yang telah memenuhi standar.
  * Meminta revisi (*Request Revision*) terhadap dokumen yang belum sesuai standar.
  * Mengakses riwayat versi terdahulu (*version history*) dari dokumen guru untuk melacak progres perbaikan.
  * **Batasan Kewenangan:** Atasan memiliki hak baca (*read-only*) terhadap file dokumen guru; Atasan **TIDAK DAPAT** mengedit, menghapus, atau mengganti file yang diunggah oleh guru secara sepihak.

### 5.4 Matriks Hak Akses (Permission Matrix)

| Modul / Kemampuan | Admin / TU | Guru | Atasan / Supervisor |
| :--- | :---: | :---: | :---: |
| **Kelola Master Data (Siswa, Guru, Mapel, Kelas)** | CRUD Penuh | Read-only terbatas | Read-only |
| **Kelola Tahun Ajaran & Semester** | CRUD Penuh | Tidak Ada | Read-only |
| **Kelola Teaching Assignment** | CRUD Penuh | Read-only (Milik Sendiri) | Read-only |
| **Kelola Jadwal Piket Guru** | CRUD Penuh (ADDENDUM §9) | Tidak Ada | Read-only |
| **Kelola Akun & Reset Password** | Penuh | Ganti Password Sendiri | Ganti Password Sendiri |
| **Unggah & Revisi Dokumen Ajar** | Tidak Ada (ADR 04 FINAL: dua akun terpisah) | CRUD (Milik Sendiri) | Tidak Ada |
| **Review & Beri Feedback Dokumen** | Tidak Ada | Tidak Ada | Penuh |
| **Approve Dokumen Guru** | Tidak Ada | Tidak Ada | Penuh |
| **~~Input Absensi Harian Guru~~** | ~~DIHAPUS~~ (ADDENDUM §20 & §26) | ~~DIHAPUS~~ | ~~DIHAPUS~~ |
| **Scanner Absensi Masuk/Pulang Siswa** | Koreksi Manual | Guru Piket (Berdasarkan Jadwal) | Read-only |
| **Buka & Simpan Sesi Absensi Siswa (Mapel)** | Koreksi Manual | Sesuai Assignment | Read-only |
| **Koreksi Absensi Siswa (Masuk/Pulang & Mapel)** | Penuh (Wajib Catatan) | Tidak Ada | Tidak Ada |
| **Lihat Audit Log Sistem** | Penuh | Tidak Ada | Tidak Ada (ADR 17 FINAL: khusus Admin/TU) |
| **Download Rekap Laporan Eksekutif** | Penuh | Rekap Kelas Sendiri | Penuh |

---

## 6. KEBUTUHAN FUNGSIONAL: ADMIN & STAFF TATA USAHA (TU)

### 6.1 Modul Dashboard Admin
* **ADM-DASH-001:** Menampilkan ringkasan metrik sekolah secara *real-time*:
  * Total guru aktif dan total siswa aktif.
  * Total kelas/rombel yang berjalan pada tahun ajaran aktif.
  * Total dokumen ajar yang terunggah (dikategorikan berdasarkan status: *Draft*, *Dikirim*, *Perlu Revisi*, *Disetujui*).
  * **Status absensi masuk/pulang siswa hari ini** (total siswa masuk, belum masuk, sudah pulang).
  * **Guru Piket hari ini** (berdasarkan `teacher_duty_schedules`).
  * Peringatan (*alerts*) dokumen yang menunggu review lebih dari $X$ hari atau kelas yang belum mengisi absensi hari ini.

### 6.2 Modul Data Master Guru
* **ADM-GUR-001:** Menambah, mengubah, menonaktifkan (*soft delete*), dan mencari data guru.
* **ADM-GUR-002:** Atribut data guru mencakup: NIP/NUPTK, Nama Lengkap (beserta gelar), Jenis Kelamin, Email, Nomor Telepon/WhatsApp, Alamat, Status Kepegawaian (GTT/GTY/PNS/PPPK), dan Status Akun (Aktif/Nonaktif).

### 6.3 Modul Data Master Siswa
* **ADM-SIS-001:** Menambah, mengubah, memindahkan status kelas, dan menonaktifkan siswa.
* **ADM-SIS-002:** Atribut siswa mencakup: NIS, NISN, Nama Lengkap, Jenis Kelamin, Tanggal Lahir, Nama Orang Tua/Wali, Kontak Darurat, Kelas/Rombel Saat Ini, dan Status (Aktif/Lulus/Pindah/Keluar).
* **ADM-SIS-003:** Fitur import data siswa secara massal menggunakan format spreadsheet standar (CSV/XLSX) dengan validasi data sebelum *insert*.

### 6.4 Modul Data Master Kelas & Tingkat (Dynamic Class)
* **ADM-KLS-001:** Manajemen jenjang pendidikan (*Grade Level*), misal: Tingkat 7, Tingkat 8, Tingkat 9.
* **ADM-KLS-002:** Manajemen rombongan belajar dinamis (contoh: 7A, 7B, 8A, 9C) yang terhubung ke Tahun Akademik tertentu.
* **ADM-KLS-003:** Menetapkan Wali Kelas (*Homeroom Teacher*) dari daftar guru aktif.
* **ADM-KLS-004:** Mendaftarkan siswa ke dalam kelas (*Class Enrollment*) per tahun ajaran.

### 6.5 Modul Data Master Mata Pelajaran & Jadwal
* **ADM-MPL-001:** Kelola daftar mata pelajaran (Kode Mapel, Nama Mapel, Kategori Umum/Muatan Lokal, Beban Jam/Minggu).
* **ADM-JDW-001:** Mengatur jadwal pelajaran mingguan yang memetakan: Hari, Jam Mulai, Jam Selesai, Mata Pelajaran, Kelas, dan Guru Pengampu.

### 6.6 Modul Tahun Akademik & Semester
* **ADM-TA-001:** Mengelola daftar Tahun Ajaran (contoh: `2026/2027`) dan Semester (`Ganjil`/`Genap`).
* **ADM-TA-002:** Menetapkan tepat 1 (satu) Tahun Ajaran & Semester sebagai status **AKTIF**. Semua proses absensi dan penugasan default merujuk pada periode aktif ini.
* **ADM-TA-003:** Menutup periode tahun ajaran (*Archiving/Closure*) untuk mencegah perubahan histori nilai dan absensi lampau.

### 6.7 Modul Teaching Assignment (Penugasan Mengajar)
* **ADM-TAS-001:** Antarmuka relasi banyak-ke-banyak (*many-to-many*) antara Guru, Mata Pelajaran, dan Kelas untuk Tahun Ajaran aktif.
* **ADM-TAS-002:** Validasi pencegahan duplikasi penugasan: satu rombel tidak boleh memiliki dua guru pengampu untuk mapel yang sama pada tahun ajaran yang sama kecuali ditetapkan sebagai *team teaching*.

### 6.8 Modul User Account & Keamanan
* **ADM-USR-001:** Pembuatan akun otomatis saat data Guru/Staff dibuat.
* **ADM-USR-002:** Fitur reset kata sandi (*Force Reset Password*) jika pengguna lupa password.
* **ADM-USR-003:** Penguncian akun sementara jika terdeteksi percobaan login gagal berulang kali (brute-force throttling).

### 6.9 Modul Monitoring & Koreksi Absensi
* **ADM-ABS-001:** Tampilan monitoring absensi masuk/pulang siswa harian (berdasarkan data `student_school_attendances`).
* **ADM-ABS-002:** Tampilan monitoring rekapitulasi kehadiran siswa per kelas dan per sesi mata pelajaran.
* **ADM-ABS-003 (Koreksi Absensi Siswa):** Hak khusus Admin/TU untuk mengubah status absensi siswa (masuk/pulang maupun per mata pelajaran) yang telah terkunci dengan syarat:
  * Wajib menginput **Alasan Koreksi** (minimal 10 karakter).
  * Sistem mencatat identitas Admin pelaksana koreksi dan stempel waktu.
  * Status awal tidak dihapus, melainkan ditandai sebagai `DIKOREKSI_ADMIN` dalam log audit.

### 6.10 Modul Audit Trail & System Log
* **ADM-LOG-001:** Pencatatan log otomatis untuk setiap tindakan penting: perubahan hak akses, perubahan status dokumen, penguncian absensi, koreksi data, dan **perubahan jadwal piket** (ADDENDUM §17).
* **ADM-LOG-002:** Antarmuka pencarian dan pemfilteran log berdasarkan: Tanggal, Pengguna (Aktor), Modul, dan Tipe Aksi (*Create/Update/Delete/Approve/Correct*).

### 6.11 Modul Jadwal Piket Guru (ADDENDUM §8–§18)
* **ADM-PIK-001:** Antarmuka CRUD jadwal piket guru: Tambah, Edit, Hapus, Ganti Guru, Ganti Tanggal.
* **ADM-PIK-002:** Minimal data jadwal piket: **Tanggal** + **Guru** (FK ke `teachers`).
* **ADM-PIK-003:** Perubahan jadwal piket langsung memengaruhi hak akses scanner tanpa perubahan source code.
* **ADM-PIK-004:** Setiap perubahan jadwal piket (Tambah/Edit/Hapus/Ganti Guru/Ganti Tanggal) wajib tercatat di `audit_logs` dengan minimal: actor, action, tanggal jadwal, guru lama, guru baru, timestamp (ADDENDUM §17).
* **ADM-PIK-005:** Admin dapat mengubah jadwal masa depan dengan bebas. Jadwal hari ini dapat diubah dengan efek segera. Jadwal masa lalu yang sudah memiliki record absensi wajib diaudit dan histori absensi tidak berubah (ADDENDUM §18).

---

## 7. KEBUTUHAN FUNGSIONAL: GURU

### 7.1 Modul Dashboard Guru
* **GUR-DASH-001:** Menampilkan **status Guru Piket hari ini**: Jika guru terjadwal piket, tampilkan banner `✅ Anda Guru Piket hari ini` dengan tombol akses scanner. Jika tidak terjadwal, tampilkan `🔒 Anda hari ini bukan Guru Piket` (ADDENDUM §19).
* **GUR-DASH-002:** Menampilkan widget jadwal mengajar hari ini berdasarkan hari dan jam aktif.
* **GUR-DASH-003:** Menampilkan ringkasan status berkas ajar pribadi:
  * Jumlah dokumen *Draft*.
  * Jumlah dokumen *Menunggu Review*.
  * Jumlah dokumen *Perlu Revisi* (dengan penanda visual mencolok/badge merah).
  * Jumlah dokumen *Disetujui*.

### 7.2 Modul Profil Guru
* **GUR-PRF-001:** Melihat data biodata pribadi dan riwayat penugasan mengajar.
* **GUR-PRF-002:** Mengubah kata sandi mandiri dan memperbarui nomor kontak aktif.

### 7.3 Modul Repositori Dokumen Guru
* **GUR-DOC-001:** Antarmuka repositori pribadi guru yang dikelompokkan berdasarkan Tahun Ajaran dan Kategori Dokumen:
  1. **RPP** (Rencana Pelaksanaan Pembelajaran) / Modul Ajar
  2. **ATP** (Alur Tujuan Pembelajaran) / Silabus
  3. **PROTA** (Program Tahunan)
  4. **PROSEM** (Program Semester)
  5. **Lainnya** (Bahan Ajar, Kisi-kisi Asesmen, Lembar Kerja Siswa)
* **GUR-DOC-002 (Upload Berkas Baru):**
  * Guru memilih Tahun Ajaran, Mata Pelajaran, Kelas sasaran, Kategori Dokumen, dan Judul Dokumen.
  * Unggah file dengan format yang diizinkan: `.pdf`, `.docx`, `.xlsx` (Maksimum ukuran: 10 MB per berkas).
  * Status awal setelah diunggah adalah `DRAFT`.
* **GUR-DOC-003 (Submit for Review):**
  * Guru menekan tombol **"Ajukan untuk Direview"** (*Submit*).
  * Dokumen beralih status dari `DRAFT` menjadi `DIKIRIM`.
  * Sistem mengirimkan notifikasi internal kepada Atasan/Supervisor yang bertugas.
* **GUR-DOC-004 (Melihat Feedback & Riwayat Revisi):**
  * Guru dapat membaca catatan evaluasi dari Atasan jika dokumen berstatus `PERLU REVISI`.
  * Guru dapat melihat daftar riwayat versi sebelumnya (v1, v2, v3, dst.) berserta catatan review masing-masing versi.
* **GUR-DOC-005 (Upload Revisi):**
  * Tombol unggah revisi hanya aktif jika dokumen berstatus `PERLU REVISI`.
  * Guru mengunggah file baru; sistem secara otomatis menaikkan nomor versi ($v+1$) tanpa menghapus versi terdahulu.
  * Status dokumen beralih menjadi `REVISI` atau kembali ke antrean review (`DIREVIEW`).

### 7.4 Modul Scanner Absensi Masuk/Pulang Sekolah (Guru Piket) (ADDENDUM §1–§7, §19)

> **Prasyarat Akses:** Modul scanner hanya dapat diakses oleh Guru yang terjadwal sebagai Guru Piket pada hari berjalan berdasarkan data `teacher_duty_schedules`. Status Guru Piket **bukan role baru** — role tetap `admin`, `teacher`, `supervisor` (ADDENDUM §8).

* **GUR-PIK-001 (Tampilan Bukan Guru Piket):**
  ```text
  ┌─────────────────────────────────┐
  │ ABSENSI MASUK / PULANG          │
  │                                 │
  │ 🔒 Fitur terkunci               │
  │                                 │
  │ Anda hari ini bukan Guru Piket. │
  └─────────────────────────────────┘
  ```
* **GUR-PIK-002 (Tampilan Guru Piket):**
  ```text
  ┌─────────────────────────────────┐
  │ ABSENSI MASUK / PULANG          │
  │                                 │
  │ ✅ Anda Guru Piket hari ini     │
  │                                 │
  │ Pilih Mode:                     │
  │                                 │
  │ [ 🟢 MASUK ] [ 🔵 PULANG ]      │
  │                                 │
  │ [ BUKA SCANNER ]                │
  └─────────────────────────────────┘
  ```
* **GUR-PIK-003 (Mode Eksplisit MASUK/PULANG):**
  * Guru Piket **wajib memilih mode** sebelum membuka kamera scanner.
  * **DILARANG** menggunakan logika auto-detect: `scan pertama = MASUK`, `scan kedua = PULANG` (ADDENDUM §1).
  * Mode aktif harus selalu terlihat jelas pada layar scanner (ADDENDUM §7).
  * Guru Piket dapat berpindah mode MASUK → PULANG tanpa logout (ADDENDUM §7).
* **GUR-PIK-004 (Mode MASUK):**
  * Scanner digunakan untuk mencatat kedatangan siswa (`check_in`).
  * Waktu menggunakan **server timestamp**, bukan waktu HP (ADDENDUM §2).
  * Jika siswa sudah memiliki `check_in` pada tanggal yang sama: record baru **tidak dibuat**; tampilkan pesan `⚠️ ABSENSI SUDAH TERCATAT` (ADDENDUM §3).
  * Duplikasi dicegah dengan kombinasi **application validation + database unique constraint** (ADDENDUM §3).
* **GUR-PIK-005 (Mode PULANG):**
  * Scanner digunakan untuk mencatat waktu kepulangan siswa (`check_out`).
  * Server mencari record absensi siswa hari ini dan mengisi `check_out_time`.
  * Jika `check_out` sudah tersimpan: update kedua **ditolak**; tampilkan pesan `⚠️ ABSENSI PULANG SUDAH TERCATAT` (ADDENDUM §5).
* **GUR-PIK-006 (Pulang Tanpa Record Masuk):** `[FINAL — KEPUTUSAN A1]`
  > Siswa yang **belum memiliki record MASUK** pada tanggal berjalan **TIDAK BOLEH** dicatat PULANG melalui scanner.

  * Scan PULANG tanpa record MASUK **DITOLAK** oleh server; tampilkan: `⚠️ Belum ada catatan MASUK hari ini. Hubungi Admin/TU untuk koreksi.`
  * Sistem **tidak boleh** membuat `check_in` palsu dan **tidak boleh** auto-create record MASUK (ADDENDUM §6).
  * Jalur resmi satu-satunya: **koreksi manual Admin/TU** melalui mekanisme koreksi + alasan wajib + audit log (`SCHOOL_ATT_CORRECT`).
* **GUR-PIK-007 (Validasi Server-Side per Request):**
  * Setiap request scan, server mengecek ulang jadwal piket dari database (ADDENDUM §12).
  * Jika jadwal sudah berubah/dicabut saat halaman scanner masih terbuka, **request scan berikutnya ditolak** oleh server (ADDENDUM §13).
  * Tampilkan: `🔒 Jadwal piket Anda telah berubah. Anda tidak lagi bertugas sebagai Guru Piket pada tanggal ini.`
* **GUR-PIK-008 (Integritas Historis):**
  * Perubahan jadwal piket **TIDAK BOLEH** mengubah operator scan historis, waktu scan, siswa yang sudah tercatat, atau record absensi yang sudah tersimpan (ADDENDUM §15–§16).
  * Record absensi yang sudah terjadi sebelum perubahan jadwal tetap utuh dengan operator aslinya.

### 7.5 Modul Absensi Siswa Berbasis Kelas & Mata Pelajaran
* **GUR-ATT-001:** Guru memilih kelas dan mata pelajaran yang akan diajarkan dari daftar *teaching assignment* miliknya.
* **GUR-ATT-002 (Pembukaan Sesi Absensi):**
  * Guru membuka sesi absensi untuk tanggal dan jam mengajar tertentu.
  * Sistem memuat daftar siswa aktif pada kelas tersebut.
* **GUR-ATT-003 (Pencatatan Kehadiran Siswa):**
  * Pilihan status per siswa: **HADIR (H)**, **IZIN (I)**, **SAKIT (S)**, **ALPA (A)**.
  * Nilai default: Seluruh siswa diinisialisasi sebagai **HADIR** (*pre-fill*) untuk mempercepat alur kerja, guru cukup menandai siswa yang tidak hadir.
  * Guru dapat menambahkan catatan keterangan (contoh: surat dokter untuk Sakit, dispensasi lomba untuk Izin).
* **GUR-ATT-004 (Finalisasi / Penguncian Sesi):**
  * Guru menekan tombol **"Simpan & Kunci Sesi Absensi"**.
  * Sesi beralih status dari `OPEN` menjadi `CLOSED`.
  * Setelah `CLOSED`, guru tidak dapat lagi mengubah data kehadiran; perubahan hanya dapat dilakukan melalui pengajuan koreksi ke Admin/TU.

---

## 8. KEBUTUHAN FUNGSIONAL: ATASAN / SUPERVISOR

### 8.1 Modul Dashboard Supervisor
* **SUP-DASH-001:** Ringkasan progres pengumpulan dokumen perangkat ajar seluruh guru:
  * Total guru yang sudah mengumpulkan lengkap vs belum lengkap.
  * Antrean dokumen dengan status `DIKIRIM` dan `REVISI` yang membutuhkan review segera.
  * Distribusi status dokumen secara keseluruhan (*Chart/Card*: Disetujui, Perlu Revisi, Menunggu Review).
* **SUP-DASH-002:** Filter pemantauan berdasarkan rumpun mata pelajaran atau tingkat kelas.

### 8.2 Modul Monitoring Repositori Guru
* **SUP-REP-001:** Supervisor dapat melihat daftar seluruh guru dan membuka repositori berkas ajar masing-masing guru.
* **SUP-REP-002:** Menampilkan status kepatuhan matriks dokumen (contoh: Guru A sudah mengunggah RPP dan PROTA, namun ATP dan PROSEM masih berstatus *Draft* atau *Kosong*).

### 8.3 Modul Review, Evaluasi & Approval Dokumen
* **SUP-REV-001 (Akses Berkas):** Supervisor dapat membuka, melihat (*preview* via PDF viewer browser atau Google Drive viewer terintegrasi), dan mengunduh berkas yang diunggah guru.
* **SUP-REV-002 (Integritas Berkas):** Sistem menjamin Atasan tidak dapat mengganti, menimpa, atau memodifikasi file mentah guru secara sepihak.
* **SUP-REV-003 (Keputusan Evaluasi):** Supervisor memiliki 2 (dua) aksi utama:
  1. **SETUJUI (APPROVE):**
     * Dokumen dianggap valid dan memenuhi standar mutu kurikulum.
     * Status dokumen beralih menjadi `DISETUJUI`.
     * Input komentar bersifat opsional (contoh: apresiasi atau catatan penguatan).
  2. **MINTA REVISI (REQUEST REVISION):**
     * Dokumen belum memenuhi standar dan wajib diperbaiki oleh guru.
     * Status dokumen beralih menjadi `PERLU REVISI`.
     * **Wajib Mengisi Catatan Revisi:** Supervisor wajib memasukkan instruksi perbaikan (minimal 10 karakter). Tombol kirim nonaktif jika kolom komentar kosong.
* **SUP-REV-004 (Version Tracking):** Saat meninjau dokumen revisi (misal v2 atau v3), Supervisor dapat membandingkan riwayat komentar dan membuka file versi sebelumnya untuk memastikan perbaikan telah dilakukan.

---

## 9. DOCUMENT MANAGEMENT LIFECYCLE & WORKFLOW

### 9.1 Diagram State Transition Dokumen

```text
               +-------------------+
               |       DRAFT       |<---------------------------+
               +-------------------+                            |
                         |                                      |
                   (Guru Submit)                                |
                         v                                      |
               +-------------------+                            |
               |      DIKIRIM      |                            |
               +-------------------+                            |
                         |                                      |
             (Supervisor Buka/Proses)                           |
                         v                                      |
               +-------------------+                            |
               |     DIREVIEW      |                            |
               +-------------------+                            |
                    /             \                             |
     (Supervisor Approve)    (Supervisor Minta Revisi)          |
                  /                 \                           |
                 v                   v                          |
        +---------------+   +---------------+                   |
        |   DISETUJUI   |   | PERLU REVISI  |                   |
        +---------------+   +---------------+                   |
                                     |                          |
                              (Guru Upload Rev)                 |
                                     v                          |
                            +---------------+                   |
                            |    REVISI     |-------------------+
                            +---------------+ (Menjadi Versi Baru,
                                               siap direview ulang)
```

### 9.2 Tabel Spesifikasi Transisi Status

| Status Awal | Aksi / Pemicu | Aktor | Status Akhir | Kondisi Validasi & Efek Samping |
| :--- | :--- | :--- | :--- | :--- |
| *None* | Upload File Pertama | Guru | `DRAFT` | Membuat record dokumen induk dan Versi 1 (v1). File disimpan ke Google Drive. |
| `DRAFT` | Update Berkas Draft | Guru | `DRAFT` | File v1 diperbarui (hanya boleh selama masih Draft). |
| `DRAFT` | Submit for Review | Guru | `DIKIRIM` | Validasi kelengkapan metadata. Notifikasi dibuat untuk Supervisor. |
| `DIKIRIM` | Akses Lembar Review | Supervisor | `DIREVIEW` | Menandai bahwa dokumen sedang dalam proses pemeriksaan oleh pimpinan. |
| `DIREVIEW` | Klik "Setujui" | Supervisor | `DISETUJUI` | Dokumen dikunci permanen. Guru tidak dapat lagi mengunggah revisi pada dokumen ini. |
| `DIREVIEW` | Klik "Minta Revisi" | Supervisor | `PERLU REVISI` | **Wajib mengisi alasan revisi**. Notifikasi otomatis masuk ke akun Guru. |
| `PERLU REVISI`| Upload Berkas Baru | Guru | `REVISI` / `DIKIRIM` | Sistem otomatis membuat record versi baru ($v+1$). Berkas lama tetap tersimpan. |
| `DISETUJUI` | *Any Action* | - | *Locked* | Status terminal. Pembatalan resmi (*Revoke Approval*) hanya oleh pihak berwenang dengan berita acara + audit (ADR 05 — `[FINAL]`). |

---

## 10. DOCUMENT VERSIONING & STORAGE STRATEGY

### 10.1 Prinsip Immutability Berkas
1. **Tidak Ada Penimpaan Berkas (No File Overwrite):** Berkas yang telah diunggah dan diajukan untuk review bersifat permanen dalam histori.
2. **Identitas Dokumen Induk vs Versi Berkas:**
   * **Document Master Entity:** Menyimpan metadata konseptual (Tahun Ajaran, Guru Pemilik, Kategori Dokumen, Judul Dokumen, Mata Pelajaran, Kelas Sasaran, Versi Aktif Saat Ini, dan Status Terkini).
   * **Document Version Entity:** Menyimpan histori berkas konkret (Nomor Versi: 1, 2, 3; Google Drive File ID; Google Drive Webview Link; Ukuran File; Hash SHA-256; Timestamp Upload; Catatan Guru).

### 10.2 Contoh Kasus Penomoran Versi & Penelusuran
* **Skenario:** Guru Budi mengunggah RPP Matematika Semester Ganjil.
  * **v1:** Diunggah 10 Juli 2026 -> Disubmit -> Supervisor minta revisi: *"Tambahkan indikator literasi numerasi di kegiatan inti."* -> Status: `PERLU REVISI`.
  * **v2:** Guru Budi memperbaiki berkas dan mengunggah v2 pada 12 Juli 2026 -> Status: `REVISI` -> Supervisor minta revisi: *"Lampiran asesmen formatif belum disertakan."* -> Status: `PERLU REVISI`.
  * **v3:** Guru Budi melengkapi lampiran dan mengunggah v3 pada 13 Juli 2026 -> Supervisor menyetujui -> Status: `DISETUJUI`.
* **Hasil di Sistem:** Supervisor dan Guru dapat melihat daftar riwayat versi (v1, v2, v3), dapat mengunduh berkas v1/v2 untuk keperluan perbandingan, dan melihat jejak waktu setiap interaksi.

### 10.3 Mekanisme Integrasi Google Drive
* Pengunggahan file dilakukan melalui server aplikasi (*backend proxy upload*) atau *direct-to-cloud upload* menggunakan Google Drive Service Account terisolasi.
* Struktur penyimpanan virtual folder di Google Drive distandarisasi untuk kemudahan audit:
  `PortalSekolah/{Tahun_Akademik}/{NIP_Guru}_{Nama_Guru}/{Kategori_Dokumen}/`
* Nama file di-sanitize dan distandarkan:
  `[Kategori]_[NamaMapel]_[Kelas]_[NIP]_[vX]_[Timestamp].[ext]`  
  *Contoh:* `RPP_Matematika_7A_19850101_v1_1720584000.pdf`

---

## 11. DOCUMENT REVIEW & FEEDBACK SYSTEM

### 11.1 Ruang Lingkup Fitur Review (MVP vs Future)
* **Karakteristik Review MVP (MUST HAVE):**
  * Umpan balik berbasis teks global (*General Document Comments*).
  * Formulir review formal saat pergantian status: Catatan Rekomendasi/Alasan Revisi.
  * Thread percakapan bertingkat sederhana antara Supervisor dan Guru pada halaman detail dokumen.
* **Fitur Non-MVP / Extended Scope:**
  * Komentar tertaut ke nomor halaman PDF (*Page-specific comment*) -> `[SHOULD HAVE]`.
  * Penandaan area koordinat visual/anotasi langsung di atas dokumen PDF (*In-document visual highlights*) -> `[FUTURE]`.
  * Integrasi real-time collaborative editing ala Google Docs -> **DILARANG / OUT OF SCOPE**.

### 11.2 Struktur Data Feedback & Komentar
Setiap komentar review harus mencatat:
1. `document_version_id`: Menempel spesifik pada nomor versi tertentu (misal komentar terkait v1 tidak boleh ambigu dengan v2).
2. `author_id`: Identitas pengguna peninjau (Supervisor/Atasan).
3. `comment_text`: Uraian saran atau revisi (teks tanpa batas, mendukung baris baru).
4. `review_action`: Aksi yang menyertai komentar (`APPROVE`, `REQUEST_REVISION`, atau sekadar `COMMENT_ONLY`).
5. `created_at`: Stempel waktu presisi detik.

---

## 12. STUDENT SUBJECT ATTENDANCE (ABSENSI SISWA PER MAPEL)

Sistem mengelola absensi kehadiran siswa per jam mata pelajaran (bukan sekadar absensi gerbang umum harian).

### 12.1 Siklus Hidup Sesi Absensi (*Attendance Session Lifecycle*)
Sesi absensi memiliki status biner: `OPEN` atau `CLOSED`.

```text
[Guru Masuk Kelas]
        |
        v
[Buka Sesi Absensi] --------> Status: OPEN
        |                     - Data siswa dimuat
        |                     - Guru menandai status H/I/S/A
        v
[Simpan & Kunci]   ---------> Status: CLOSED
                              - Kunci perubahan bagi Guru
                              - Tercatat waktu submit & IP
                              - Hanya Admin yang dapat mengoreksi
```

### 12.2 Rincian Aturan Sesi
1. **Otorisasi Pembukaan Sesi:** Hanya Guru yang memiliki *teaching assignment* sah untuk kelas dan mata pelajaran tersebut yang berhak membuka sesi.
2. **Kategori Status Kehadiran Siswa:**
   * **HADIR (H):** Siswa hadir secara fisik di kelas.
   * **IZIN (I):** Siswa tidak hadir dengan pemberitahuan resmi dari orang tua/sekolah.
   * **SAKIT (S):** Siswa tidak hadir karena alasan medis/surat dokter.
   * **ALPA (A):** Siswa tidak hadir tanpa keterangan yang sah.
3. **Pencegahan Sesi Ganda:** Tidak boleh ada dua sesi absensi yang aktif pada kelas, mata pelajaran, tanggal, dan rentang jam pelajaran yang identik (*unique constraint* pada database).
4. **Batas Waktu Penguncian Sesi:** `[FINAL — ADR 14]` Sesi ditutup secara **manual oleh Guru pengampu** (*Manual Close*); tidak ada *auto-lock scheduler* pada MVP (ADR 14). Sesi yang lupa ditutup tetap dapat diselesaikan melalui koreksi Admin/TU (wajib alasan + audit).
5. **Koreksi Administratif:** Guru yang melakukan kesalahan entri setelah sesi berstatus `CLOSED` wajib melapor kepada Staff TU/Admin. Admin membuka menu Koreksi Absensi Siswa, memilih siswa bersangkutan, mengubah status, dan mencantumkan alasan perubahan.

---

## 13. STUDENT SCHOOL ATTENDANCE — ABSENSI MASUK/PULANG SEKOLAH (BARCODE SCANNER)

> **ADDENDUM FINAL — ATURAN MODE MASUK/PULANG & PERUBAHAN JADWAL PIKET (§1–§26)**
>
> Bagian ini menggantikan seluruh referensi *Teacher Daily Attendance (Absensi Mandiri Guru)* yang telah **DIHAPUS TOTAL** (ADDENDUM §20 & §26). Tidak ada lagi fitur presensi mandiri harian guru di Portal Sekolah.

### 13.1 Arsitektur Dua Jenis Absensi Siswa

Sistem Portal Sekolah mengelola **dua jenis absensi siswa** yang terpisah secara arsitektur dan tujuan:

```text
ABSENSI SISWA
│
├── Absensi Masuk/Pulang Sekolah (Bab 13 ini)
│   ├── Jadwal Guru Piket (teacher_duty_schedules)
│   ├── Mode MASUK (check_in)
│   ├── Mode PULANG (check_out)
│   ├── Barcode pada Kartu Fisik Siswa
│   └── Scanner Kamera HP (Web App/Browser)
│
└── Absensi Mata Pelajaran (Bab 12)
    ├── Teaching Assignment
    ├── OPEN / CLOSED
    ├── H / I / S / A
    └── Koreksi Admin/TU + Audit
```

### 13.2 Mekanisme Absensi Masuk/Pulang Sekolah

1. **Identifikasi Siswa via Barcode:** Setiap siswa memiliki **barcode unik** yang dicetak pada kartu fisik siswa. Barcode ini adalah identifikasi visual (1D/2D), **bukan** RFID/NFC/chip.
2. **Operator:** Absensi masuk/pulang sekolah dioperasikan oleh **Guru Piket** yang ditentukan berdasarkan data jadwal piket (`teacher_duty_schedules`) pada tanggal berjalan.
3. **Scanner:** Menggunakan **kamera HP** Guru Piket melalui Web App/Browser (JavaScript `getUserMedia`). Tidak memerlukan hardware scanner khusus.
4. **Mode Eksplisit:** Guru Piket **wajib memilih mode MASUK atau PULANG** sebelum melakukan scan. Logika auto-detect (`scan pertama = MASUK`, `scan kedua = PULANG`) **DILARANG** (ADDENDUM §1).
5. **Server Timestamp:** Waktu absensi menggunakan **waktu server**, bukan waktu HP guru (ADDENDUM §2).

### 13.3 Alur Absensi Masuk/Pulang

```text
Guru Piket Login
↓
Sistem cek jadwal piket hari ini (server-side)
↓
Guru terjadwal → Tampilkan pilihan mode
↓
[ 🟢 MASUK ]    [ 🔵 PULANG ]
↓
[ BUKA SCANNER ]
↓
Kamera HP aktif
↓
Scan Barcode Siswa
↓
Server Validasi:
  - Identifikasi siswa
  - Cek jadwal piket (re-validate)
  - Cek duplikasi
  - Simpan record
↓
Tampilkan hasil
```

### 13.4 Aturan Duplikasi

* **Mode MASUK:** Jika siswa sudah memiliki `check_in` pada tanggal yang sama, record baru **tidak dibuat**. Sistem menampilkan peringatan (ADDENDUM §3).
* **Mode PULANG:** Jika `check_out` sudah tersimpan, update kedua **ditolak** (ADDENDUM §5).
* Duplikasi dicegah di **application layer** DAN **database unique constraint** pada `(student_id, attendance_date)`.

### 13.5 Pulang Tanpa Record Masuk `[FINAL — KEPUTUSAN A1: DITOLAK]`

> Keputusan manusia yang disahkan: scan **PULANG** bagi siswa yang belum memiliki record **MASUK** pada tanggal berjalan **DITOLAK**.

Sistem **tidak boleh** membuat check-in palsu maupun auto-create record MASUK (ADDENDUM §6). Siswa tetap dapat diselesaikan melalui **koreksi manual Admin/TU** (wajib alasan + audit `SCHOOL_ATT_CORRECT`).

### 13.6 Atribut Data Absensi Masuk/Pulang Siswa (`student_school_attendances`)

* `id`: BIGINT UNSIGNED AUTO_INCREMENT Primary Key.
* `student_id`: FK → `students(id)`.
* `attendance_date`: Tanggal kalender (DATE).
* `check_in_time`: Waktu masuk (TIMESTAMP, server time).
* `check_out_time`: Waktu pulang (TIMESTAMP, nullable).
* `check_in_by_teacher_id`: FK → `teachers(id)` — Guru Piket yang men-scan masuk.
* `check_out_by_teacher_id`: FK → `teachers(id)` — Guru Piket yang men-scan pulang (nullable).
* `scan_mode_in`: Mode saat scan masuk (`'MASUK'`).
* `scan_mode_out`: Mode saat scan pulang (`'PULANG'`, nullable).
* `is_corrected`: BOOLEAN — penanda koreksi Admin.
* `corrected_by_user_id`: FK → `users(id)` (nullable).
* `correction_reason`: TEXT (nullable, wajib jika `is_corrected = TRUE`).
* `corrected_at`: TIMESTAMP (nullable).
* `created_at`, `updated_at`: TIMESTAMP.
* **Constraint:** `UNIQUE (student_id, attendance_date)` — satu siswa hanya memiliki satu record per hari.

### 13.7 Kebijakan Koreksi Absensi Masuk/Pulang Siswa

* Guru Piket **tidak dapat mengoreksi** record absensi masuk/pulang yang telah tersimpan.
* Koreksi hanya dapat dilakukan oleh **Admin/TU** melalui menu koreksi khusus.
* Admin wajib mengisi **alasan koreksi** (minimal 10 karakter).
* Histori koreksi tercatat lengkap dalam tabel `audit_logs`.

### 13.8 Jadwal Guru Piket (`teacher_duty_schedules`) (ADDENDUM §8–§18)

* Guru Piket **bukan role baru**. Role tetap: `admin`, `teacher`, `supervisor`.
* Status Guru Piket berasal dari jadwal piket + tanggal berjalan.
* Jadwal piket dikelola sepenuhnya oleh Admin/TU (ADDENDUM §9).
* Minimal data: **Tanggal** + **Guru**.
* **DILARANG** hardcoded di source code: `users.is_piket = true`, `if user == "Fauzan"`, `if today == "Monday"` (ADDENDUM §10).
* Perubahan jadwal langsung memengaruhi hak akses scanner (ADDENDUM §11).
* Server mengecek jadwal pada **setiap request scan** (ADDENDUM §12).
* Halaman scanner yang sudah terbuka **tidak mempertahankan akses** jika jadwal dicabut (ADDENDUM §13–§14).
* Record historis **tidak berubah** saat jadwal berubah (ADDENDUM §15–§16).
* Perubahan jadwal wajib diaudit (ADDENDUM §17).
* Aturan perubahan berdasarkan waktu: masa depan (bebas), hari ini (efek segera, histori tetap), masa lalu (wajib audit, histori tidak berubah) (ADDENDUM §18).

---

## 14. DYNAMIC CLASS ARCHITECTURE & ACADEMIC PROGRESSION

### 14.1 Persyaratan Skalabilitas Struktur Rombongan Belajar
Sistem harus menjamin bahwa penambahan tingkat, pemecahan rombel, atau perluasan kelas di masa depan **TIDAK MEMERLUKAN PERUBAHAN SKEMA BASIS DATA ATAU MODIFIKASI KODE APLIKASI**.

* **Kondisi Saat Ini (Baseline):**
  * Jenjang: Kelas 7
  * Rombel: 7A
  * Kapasitas: 27 siswa
* **Kebutuhan Pertumbuhan Dinamis (Target):**
  * Jenjang Tingkat 7: 7A, 7B, 7C, ...
  * Jenjang Tingkat 8: 8A, 8B, ...
  * Jenjang Tingkat 9: 9A, 9B, 9C, ...
  * Kemungkinan penambahan jenjang lain (misal: Kelas Akselerasi, Program Khusus) tanpa merusak integritas sistem.

### 14.2 Model Konseptual Dinamis
* **Tingkat / Grade Level:** Disimpan sebagai entitas parametrik (Nama Tingkat: "Kelas 7", Urutan: 7).
* **Rombel / Kelas:** Entitas dinamis yang mengikat Tingkat, Nama Rombel ("7A"), Tahun Akademik ID, dan ID Wali Kelas.
* **Siswa Per Rombel:** Entitas asosiasi antara Siswa dan Rombel untuk Tahun Akademik tertentu. Ini memungkinkan seorang siswa memiliki riwayat:
  * Tahun 2025/2026: Terdaftar di Kelas 7A
  * Tahun 2026/2027: Naik ke Kelas 8B
  * Tanpa mengubah primary key siswa atau merusak histori absensi lampau.

---

## 15. TEACHING ASSIGNMENT & PERMISSION SCOPING

Sistem mengimplementasikan prinsip *Least Privilege* dalam pengelolaan kelas.

### 15.1 Aturan Pembatasan Kewenangan Mengajar
1. Seorang guru dapat mengajar beberapa kelas untuk mata pelajaran yang sama (contoh: Guru Budi mengampu Matematika di 7A, 8A, dan 9A).
2. Seorang guru dapat mengajar mata pelajaran berbeda di rombel yang berbeda.
3. **Kewenangan Absensi Siswa Bersifat Terkunci (Strictly Scoped):**
   * Guru Budi **HANYA DAPAT** melihat daftar siswa, membuka sesi absensi, dan menginput kehadiran siswa di Kelas 7A, 8A, dan 9A untuk mapel Matematika.
   * Guru Budi **DILARANG KERAS / DITOLAK AKSESNYA** jika mencoba mengakses sesi absensi Kelas 7B, atau sesi Bahasa Indonesia di 7A.
   * Sistem wajib memvalidasi otorisasi di level server/backend (menggunakan Laravel Policy / Middleware), bukan sekadar menyembunyikan tombol di antarmuka frontend.

---

## 16. REPORTING, ANALYTICS & AUDIT LOGGING

Sistem menyediakan fungsi rekapitulasi data administratif yang praktis tanpa membangun reporting engine yang rumit.

### 16.1 Laporan untuk Admin / Tata Usaha
1. **Laporan Absensi Masuk/Pulang Siswa (School Attendance):**
   * Rekapitulasi harian/bulanan: Jumlah siswa masuk, belum masuk, sudah pulang per kelas.
   * Filter: Rentang tanggal (From - To), Kelas, Nama Siswa.
   * Identitas Guru Piket (operator) tercatat per record.
2. **Laporan Absensi Siswa Per Mata Pelajaran (Subject Attendance):**
   * Rekapitulasi kehadiran siswa per kelas per mata pelajaran atau akumulasi seluruh mapel.
   * Menampilkan persentase kehadiran per siswa untuk bahan pertimbangan kelulusan/kenaikan kelas.
   * Filter: Tahun Ajaran, Semester, Tingkat, Kelas, Mata Pelajaran, Rentang Tanggal.
3. **Laporan Status Kelengkapan Dokumen Ajar:**
   * Matriks pemenuhan dokumen seluruh guru: Daftar guru yang telah menyelesaikan kewajiban upload RPP, ATP, PROTA, PROSEM dengan status `DISETUJUI`.
   * Daftar guru yang menunggak berkas atau memiliki dokumen berstatus `PERLU REVISI` yang belum diselesaikan.
4. **Laporan Jadwal Piket Guru:**
   * Riwayat jadwal piket dan perubahan, dengan audit trail.

### 16.2 Format Ekspor Data
* **Format Utama (MVP - MUST HAVE):**
  * Ekspor tabular ke file **CSV** dan format spreadsheet **Microsoft Excel (.xlsx)**.
* **Format Cetak Resmi (MVP / SHOULD HAVE):**
  * Ekspor ringkasan presensi dan kelengkapan dokumen dalam format **PDF** siap cetak lengkap dengan kop surat sekolah dan kolom tanda tangan pimpinan `[SHOULD HAVE]`.

### 16.3 Spesifikasi Audit Logging
Tabel audit log sistem merekam kejadian penting:
* `id`: Auto increment / UUID.
* `user_id`: Pengguna yang melakukan aksi.
* `role`: Peran aktif pengguna saat beraksi.
* `action`: Kode aksi (`AUTH_LOGIN`, `DOC_UPLOAD`, `DOC_SUBMIT`, `DOC_APPROVE`, `DOC_REVISION_REQUEST`, `ATT_SESSION_OPEN`, `ATT_SESSION_CLOSE`, `ATT_CORRECTION`, `SCHOOL_ATT_SCAN`, `SCHOOL_ATT_CORRECT`, `DUTY_SCHEDULE_CREATE`, `DUTY_SCHEDULE_UPDATE`, `DUTY_SCHEDULE_DELETE`).
* `entity_type`: Model yang disentuh (misal: `Document`, `AttendanceSession`, `StudentAttendance`).
* `entity_id`: ID baris yang disentuh.
* `old_values`: Nilai sebelum diubah (JSON).
* `new_values`: Nilai setelah diubah (JSON).
* `ip_address`: Alamat IP pengguna.
* `created_at`: Stempel waktu presisi.

---

## 17. SPESIFIKASI DASHBOARD PER ROLE

### 17.1 Dashboard Admin / Staff TU
* **Metrik Utama (Kartu Ringkasan):**
  * Total Guru Terdaftar (Aktif)
  * Total Siswa Terdaftar (Aktif)
  * Total Rombel Kelas Aktif
  * Status Dokumen Guru: Menunggu Review (Badge Kuning), Perlu Revisi (Badge Merah), Disetujui (Badge Hijau)
  * **Absensi Masuk/Pulang Siswa Hari Ini** (Total Masuk / Total Siswa Aktif)
  * **Guru Piket Hari Ini** (berdasarkan `teacher_duty_schedules`)
* **Tabel Pemantauan Cepat:**
  * 5 Dokumen guru terbaru yang diajukan untuk review.
  * 5 Sesi absensi kelas yang belum dikunci hari ini.
  * Log aktivitas sistem terbaru (5 aktivitas terakhir).

### 17.2 Dashboard Guru
* **Status Guru Piket Hari Ini:** Banner interaktif penanda status piket (Bukan Guru Piket -> Fitur terkunci; Guru Piket -> Pilihan Mode MASUK/PULANG + Tombol Scanner) (ADDENDUM §19).
* **Jadwal & Agenda Mengajar Hari Ini:** Daftar kartu kelas hari berjalan sesuai jadwal mingguan (Jam ke-, Kelas, Mapel, Tombol "Input Absensi Siswa").
* **Ringkasan Repositori Dokumen:**
  * Kartu Dokumen Menunggu Review.
  * Kartu Peringatan: Dokumen Perlu Revisi (menampilkan nama dokumen dan link langsung ke catatan revisi).
  * Kartu Dokumen Disetujui.

### 17.3 Dashboard Atasan / Supervisor
* **Metrik Supervisi Akademik:**
  * Dokumen Menunggu Review (Antrean Aksi Utama).
  * Dokumen Berstatus Perlu Revisi (Menunggu perbaikan guru).
  * Dokumen Tuntas / Disetujui.
  * Rasio Kepatuhan Pengumpulan Dokumen Sekolah (misal: $72\%$ guru telah tuntas).
* **Daftar Antrean Kerja (Actionable Review Queue):**
  * Tabel prioritas dokumen yang baru masuk dengan informasi: Tanggal Masuk, Nama Guru, Jenis Dokumen, Versi, Tombol "Review Sekarang".

---

## 18. USER STORIES BERDASARKAN ROLE

### 18.1 User Stories: Admin / Staff TU
* **US-ADM-01:** *Sebagai Staff TU*, saya ingin mengelola data master kelas dan mata pelajaran secara fleksibel, *sehingga* saya dapat menyesuaikan struktur sekolah setiap pergantian tahun ajaran tanpa meminta bantuan programmer.
* **US-ADM-02:** *Sebagai Staff TU*, saya ingin memetakan penugasan mengajar guru ke kelas dan mata pelajaran tertentu, *sehingga* guru hanya dapat mengakses data siswa yang relevan dengan tugas mengajarnya.
* **US-ADM-03:** *Sebagai Admin*, saya ingin melakukan koreksi status absensi siswa (masuk/pulang maupun per mapel) yang salah entri dengan mencantumkan alasan resmi, *sehingga* data kehadiran tetap akurat dan akuntabel saat diaudit.
* **US-ADM-04:** *Sebagai Admin*, saya ingin mengunduh laporan rekapitulasi kehadiran siswa bulanan ke dalam format Excel, *sehingga* saya dapat memproses laporan ke dinas pendidikan dengan cepat.
* **US-ADM-05:** *Sebagai Admin*, saya ingin mengelola jadwal piket guru (tambah, edit, hapus, ganti guru/tanggal), *sehingga* hak akses scanner selalu sesuai dengan jadwal terbaru tanpa perubahan kode (ADDENDUM §9).

### 18.2 User Stories: Guru
* **US-GUR-01:** *Sebagai Guru Piket*, saya ingin memilih mode MASUK atau PULANG dan men-scan barcode kartu siswa melalui kamera HP saya, *sehingga* absensi kedatangan/kepulangan siswa tercatat secara digital dan akurat (ADDENDUM §1–§7).
* **US-GUR-02:** *Sebagai Guru*, saya ingin mengunggah dokumen RPP, ATP, PROTA, dan PROSEM ke repositori digital pribadi saya, *sehingga* seluruh berkas ajar saya tersimpan aman dan tidak hilang.
* **US-GUR-03:** *Sebagai Guru*, saya ingin mengajukan dokumen saya untuk direview oleh Atasan dan melihat catatan feedback-nya secara transparan, *sehingga* saya tahu bagian mana yang perlu diperbaiki.
* **US-GUR-04:** *Sebagai Guru*, saya ingin mengunggah versi perbaikan dokumen tanpa menghapus berkas awal, *sehingga* riwayat progres kerja saya tetap terdokumentasi dengan baik.
* **US-GUR-05:** *Sebagai Guru*, saya ingin membuka sesi dan mengisi absensi siswa pada kelas yang saya ajar, *sehingga* catatan kehadiran siswa per pertemuan langsung tercatat di sistem sekolah.

### 18.3 User Stories: Atasan / Supervisor
* **US-SUP-01:** *Sebagai Supervisor/Kepala Sekolah*, saya ingin melihat antrean dokumen ajar yang diajukan oleh para guru, *sehingga* saya dapat melakukan supervisi akademik tepat waktu.
* **US-SUP-02:** *Sebagai Supervisor*, saya ingin membaca dokumen guru dan memberikan catatan evaluasi atau alasan revisi, *sehingga* guru mendapat arahan perbaikan yang jelas dan terarah.
* **US-SUP-03:** *Sebagai Supervisor*, saya ingin menyetujui dokumen yang telah memenuhi kualifikasi dengan satu klik, *sehingga* status kelengkapan administratif guru tersebut tervalidasi secara resmi.
* **US-SUP-04:** *Sebagai Supervisor*, saya ingin melihat riwayat versi terdahulu dari suatu dokumen berserta histori komentarnya, *sehingga* saya dapat mengevaluasi apakah guru telah sungguh-sungguh memperbaiki catatan sebelumnya.

---

## 19. END-TO-END USER FLOWS

### 19.1 Flow 1: Autentikasi Pengguna (Login & Logout)
```text
[User Akses Halaman Login]
            |
[Input Email/NIP dan Password]
            |
            v
[Validasi Kredensial & Status Akun]
      /                   \
(Gagal)                   (Berhasil)
   |                          |
[Tampilkan Error & Throttling] [Cek Role Pengguna]
                              |
       +----------------------+----------------------+
       |                      |                      |
       v                      v                      v
[Redirect Dashboard]    [Redirect Dashboard]   [Redirect Dashboard]
      ADMIN                   GURU               SUPERVISOR
```

### 19.2 Flow 2: Siklus Lengkap Dokumen (Upload, Submit, Review, Revisi, Approval)
```text
(GURU)
1. Buka Repositori Dokumen -> Klik "Tambah Dokumen".
2. Pilih Kategori (RPP/ATP/PROTA/PROSEM), Judul, Kelas, Mapel -> Pilih Berkas (PDF/DOCX).
3. Klik "Simpan Draft" -> Berkas tersimpan di Google Drive, status: DRAFT.
4. Periksa kembali -> Klik "Ajukan untuk Review" -> Status berubah menjadi DIKIRIM.

(SUPERVISOR)
5. Notifikasi antrean muncul di Dashboard -> Buka Dokumen -> Status: DIREVIEW.
6. Baca isi dokumen via viewer terintegrasi.
7. Ambil Keputusan:
   A. JIKA SESUAI:
      - Klik "Setujui Dokumen".
      - Input catatan opsional -> Status: DISETUJUI -> Selesai (Dokumen Terkunci).
   B. JIKA PERLU PERBAIKAN:
      - Klik "Minta Revisi".
      - Wajib isi alasan revisi (Contoh: "Format rubrik asesmen belum sesuai panduan kurikulum baru").
      - Klik Kirim -> Status: PERLU REVISI.

(GURU)
8. Notifikasi revisi diterima -> Buka dokumen -> Baca catatan Supervisor.
9. Perbaiki file lokal di komputer.
10. Klik "Unggah Revisi" -> Pilih file baru -> Submit.
11. Sistem mencatat versi baru (v2), file lama (v1) tetap utuh -> Status: REVISI / DIKIRIM.

(SUPERVISOR)
12. Supervisor membuka v2, membandingkan dengan catatan v1 -> Jika sudah sesuai -> Klik "Setujui" -> Status: DISETUJUI.
```

### 19.3 Flow 3: Absensi Masuk/Pulang Siswa via Scanner Barcode (ADDENDUM §1–§7)
```text
1. Guru Piket login ke Portal Sekolah pada pagi hari.
2. Sistem mengecek jadwal piket hari ini dari database.
3. Guru terjadwal → Tampil pilihan mode:
   [ 🟢 MASUK ]    [ 🔵 PULANG ]
4. Guru memilih [ MASUK ].
5. Klik "Buka Scanner" → Kamera HP aktif.
6. Scan barcode kartu siswa.
7. Server validasi:
   - Identifikasi siswa dari barcode.
   - Re-validasi jadwal piket Guru.
   - Cek duplikasi check_in hari ini.
8. Jika belum ada check_in:
   → Simpan record di student_school_attendances.
   → Tampilkan: "✅ ABSENSI MASUK BERHASIL — Fauzan, 7A, 07:05, MASUK".
9. Jika sudah ada check_in:
   → Tolak: "⚠️ Sudah tercatat masuk pukul 07:05".
10. Saat jam pulang, Guru Piket beralih mode ke [ PULANG ].
11. Scan barcode siswa → Server mengisi check_out pada record hari ini.
12. Jika belum ada check_in hari ini (KEPUTUSAN A1 — FINAL):
    → Scan PULANG DITOLAK: "⚠️ Belum ada catatan MASUK hari ini. Hubungi Admin/TU untuk koreksi."
    → Sistem TIDAK membuat check_in palsu / auto-create.
    → Penyelesaian resmi: koreksi manual Admin/TU (alasan wajib + audit).
```

### 19.4 Flow 4: Absensi Siswa Berdasarkan Mata Pelajaran (Student Attendance)
```text
1. Guru memilih menu "Absensi Siswa".
2. Sistem menampilkan daftar kelas & mapel aktif milik guru bersangkutan.
3. Guru memilih "Kelas 7A - Matematika" -> Tanggal: Hari Ini.
4. Klik "Buka Sesi Pertemuan".
5. Sistem memuat tabel daftar 27 siswa (semua default tercentang HADIR).
6. Guru melakukan pengecekan di kelas:
   - Siswa A: Ubah radio button ke SAKIT (isi catatan: "Surat terlampir di meja piket").
   - Siswa B: Ubah radio button ke ALPA.
7. Guru memeriksa ringkasan: Hadir: 25, Sakit: 1, Alpa: 1.
8. Guru menekan tombol "Simpan & Kunci Sesi".
9. Konfirmasi pop-up dialog: "Apakah Anda yakin ingin mengunci absensi? Sesi yang terkunci hanya dapat diubah oleh Admin."
10. Guru konfirmasi -> Status sesi menjadi CLOSED.
```

### 19.5 Flow 5: Koreksi Absensi oleh Admin / Staff TU
```text
1. Staff TU menerima surat pemberitahuan susulan bahwa Siswa B yang sebelumnya Alpa ternyata berstatus IZIN (dispensasi sekolah).
2. Admin login -> Buka menu "Koreksi Absensi Siswa".
3. Filter: Tanggal, Kelas 7A, Mapel Matematika.
4. Cari nama Siswa B -> Klik "Koreksi Kehadiran".
5. Ubah status dari ALPA menjadi IZIN.
6. Input Alasan Koreksi: "Dispensasi lomba cerdas cermat tingkat kota sesuai SK No. 12/DISP/2026".
7. Klik "Simpan Koreksi".
8. Sistem memperbarui status siswa, menyimpan log audit (siapa mengoreksi, kapan, status lama, status baru, alasan).
```

---

## 20. NON-GOALS & SCOPE EXCLUSIONS (BATASAN SISTEM)

Untuk memastikan rilis MVP tepat waktu, fungsional, dan stabil, hal-hal berikut **SECARA EKSPLISIT TIDAK DIMASUKKAN KE DALAM RUANG LINGKUP MVP**:

1. **Sistem RFID (RFID Cards, Reader, API, Hardware Integration):** Dilarang keras. Sistem sepenuhnya murni web-based. Barcode pada kartu siswa adalah barcode visual, bukan RFID/NFC.
2. **Frontend Single Page Application (SPA):** Tidak menggunakan React, Vue, Angular, atau Svelte untuk frontend. Semua antarmuka menggunakan server-rendered Laravel Blade.
3. **In-App Document Editor ala Google Docs / Microsoft 365:** Sistem bukan pengolah kata kolaboratif online. Dokumen disiapkan secara offline oleh guru (Word/PDF), diunggah sebagai file utuh, dan ditinjau melalui viewer.
4. **Arsitektur Microservices & Kubernetes:** Tidak menggunakan microservices, service mesh, container orchestration kompleks, atau multi-repo. Arsitektur harus berupa monolit modular sederhana di server PHP-MySQL standar.
5. **Message Broker Asynchronous Terpisah (Kafka / RabbitMQ):** Cukup menggunakan default database queue atau Redis sederhana bawaan ekosistem Laravel jika dibutuhkan background job pengunggahan ke Google Drive.
6. **Integrasi Pembayaran SPP / Finansial Sekolah:** Portal ini berfokus pada administrasi akademik dan dokumen pengajaran, tidak mengelola modul keuangan, SPP, atau kas sekolah.
7. **Portal Mandiri untuk Siswa & Orang Tua:** Pada tahap MVP, pengguna sistem hanya terdiri dari Guru, Staff TU/Admin, dan Pimpinan/Supervisor. Siswa dan wali murid tidak memiliki akun login ke dalam portal ini.
8. **Complex Geofencing / GPS Biometrics / NFC / Aplikasi Mobile Native:** Scanner menggunakan kamera HP melalui browser. Tidak memerlukan GPS, NFC, atau aplikasi mobile khusus (ADDENDUM §24).
9. **~~Absensi Mandiri Guru (Teacher Self-Attendance)~~:** DIHAPUS TOTAL (ADDENDUM §20 & §26). Tidak ada lagi fitur presensi mandiri harian guru.
10. **WebSocket / Redis / Queue / Microservice untuk Jadwal Piket:** Cukup validasi server-side per request (ADDENDUM §24).

---

## 21. MVP SCOPE & FEATURE PRIORITIZATION (MOSCOW)

| Fitur / Modul | Prioritas | Masuk MVP? | Keterangan & Catatan Teknis |
| :--- | :---: | :---: | :--- |
| **Autentikasi Session Laravel & RBAC 3 Role** | MUST HAVE | **YA** | Login aman, password hashing Argon2id/Bcrypt, proteksi brute-force. |
| **Peniadaan RFID (Pure Web Attendance)** | MUST HAVE | **YA** | Menjaga prinsip tanpa hardware RFID sama sekali. |
| **Data Master: Guru, Siswa, Mapel, TA** | MUST HAVE | **YA** | Entitas fundamental sistem berbasis MySQL. |
| **Dynamic Class & Rombel Management** | MUST HAVE | **YA** | Kelas 7A, 7B, 8A tanpa hardcoding skema database. |
| **Teaching Assignment Scoping** | MUST HAVE | **YA** | Membatasi hak absensi siswa hanya pada guru yang ditugaskan. |
| **Upload Dokumen Guru (RPP, ATP, PROTA, PROSEM)**| MUST HAVE | **YA** | Upload multi-format (PDF/DOCX), integrasi Google Drive. |
| **Document State Machine (Draft -> Approved)** | MUST HAVE | **YA** | Alur transisi status dokumen deterministik dan terotorisasi. |
| **Document Versioning (v1, v2, v3 Immutable)** | MUST HAVE | **YA** | File revisi tidak menimpa versi lama, histori tersimpan rapi. |
| **Supervisor Review & Approval dengan Alasan Revisi**| MUST HAVE | **YA** | Wajib ada feedback text box saat menolak/meminta revisi. |
| **~~Absensi Mandiri Guru (Harian)~~** | ~~DIHAPUS~~ | ~~DIHAPUS~~ | ADDENDUM §20 & §26: Fitur ini dihapus total. |
| **Absensi Masuk/Pulang Siswa (Barcode Scanner)** | MUST HAVE | **YA** | Scanner kamera HP, mode eksplisit MASUK/PULANG, jadwal piket (ADDENDUM). |
| **Jadwal Piket Guru (CRUD + Audit)** | MUST HAVE | **YA** | Admin/TU mengelola jadwal; server validasi per request (ADDENDUM §8–§18). |
| **Absensi Siswa Per Sesi Mata Pelajaran** | MUST HAVE | **YA** | Status H/I/S/A, tombol buka dan kunci sesi absensi. |
| **Koreksi Absensi oleh Admin dengan Audit Log** | MUST HAVE | **YA** | Mengatasi human error entri dengan jejak alasan yang jelas. |
| **Dashboard Metrik Sederhana per Role** | MUST HAVE | **YA** | Ringkasan kartu informasi untuk Admin, Guru, dan Supervisor. |
| **Ekspor Laporan ke Spreadsheet (CSV / Excel)** | MUST HAVE | **YA** | Rekap absensi dan pemenuhan dokumen untuk kebutuhan pelaporan. |
| **Cetak PDF Rekap Laporan Resmi** | SHOULD HAVE| **YA** | Format PDF siap cetak dengan header sekolah dan tanda tangan. |
| **Notifikasi Internal Web (In-App Badge/Alert)** | SHOULD HAVE| **YA** | Notifikasi bel saat ada dokumen baru atau permintaan revisi. |
| **Batch Import Siswa dari Excel/CSV** | SHOULD HAVE| **YA** | Mempermudah TU saat input data tahun ajaran baru. |
| **Komentar Review Tertaut Halaman Dokumen (PDF)** | SHOULD HAVE| **TIDAK** | Target pembaruan v1.1 pasca stabilisasi MVP. |
| **Notifikasi WhatsApp / Email Gateway** | NICE TO HAVE| **TIDAK** | Integrasi API eksternal (Fonnte/Mailgun) membutuhkan biaya tambahan. |
| **Anotasi Visual Koordinat di PDF** | FUTURE | **TIDAK** | Fitur lanjutan pasca implementasi PDF viewer custom. |
| **Aplikasi Mobile Khusus (Android/iOS Native)** | FUTURE | **TIDAK** | Cukup Web PWA responsif pada fase awal. |

---

## 22. ACCEPTANCE CRITERIA (GHERKIN FORMAT)

### 22.1 Autentikasi & Otorisasi
* **AC-AUTH-01: Login Berhasil Berdasarkan Role**
  ```gherkin
  Given Pengguna memiliki akun aktif dengan role "GURU"
  When Pengguna memasukkan NIP dan password yang benar pada halaman login
  Then Sistem memvalidasi kredensial dan membuat session terautentikasi
  And Sistem mengarahkan pengguna ke "/guru/dashboard"
  And Sistem menolak akses jika pengguna mencoba mengakses endpoint "/admin/*" (HTTP 403 Forbidden).
  ```

* **AC-AUTH-02: Pencegahan Brute Force**
  ```gherkin
  Given Pengguna memasukkan password yang salah sebanyak 5 kali berturut-turut
  When Pengguna mencoba melakukan submit login untuk ke-6 kalinya
  Then Sistem memblokir sementara percobaan login selama 60 detik (HTTP 429 Too Many Requests)
  And Sistem mencatat peringatan keamanan pada log audit.
  ```

### 22.2 Manajemen Repositori & Versi Dokumen
* **AC-DOC-01: Guru Mengajukan Dokumen Baru**
  ```gherkin
  Given Guru telah mengisi form dokumen dengan judul "RPP Bab 1", kategori "RPP", mapel "Matematika"
  And Guru telah memilih berkas valid "rpp_bab1.pdf" (3 MB)
  When Guru menekan tombol "Ajukan untuk Review"
  Then Berkas berhasil diunggah ke Google Drive pada folder yang sesuai
  And Sistem membuat record dokumen dengan status "DIKIRIM" dan nomor versi "v1"
  And Dokumen muncul pada antrean dashboard Atasan/Supervisor.
  ```

* **AC-DOC-02: Supervisor Meminta Revisi Dokumen**
  ```gherkin
  Given Dokumen "RPP Bab 1" milik Guru berstatus "DIKIRIM"
  When Supervisor membuka dokumen dan memilih aksi "Minta Revisi"
  And Supervisor mengosongkan kolom catatan revisi
  Then Tombol submit tetap nonaktif dan sistem menampilkan pesan "Catatan revisi wajib diisi"
  When Supervisor mengisi catatan "Tujuan pembelajaran belum memuat profil pelajar Pancasila"
  And Supervisor menekan tombol kirim
  Then Status dokumen berubah menjadi "PERLU REVISI"
  And Guru menerima penanda notifikasi revisi pada dashboard pribadinya.
  ```

* **AC-DOC-03: Versioning Immutability saat Unggah Revisi**
  ```gherkin
  Given Dokumen "RPP Bab 1" berstatus "PERLU REVISI" dengan versi saat ini "v1"
  When Guru memilih dokumen tersebut dan mengunggah berkas perbaikan "rpp_bab1_revisi.pdf"
  Then Sistem membuat record versi baru bernomor "v2"
  And File lama "v1" tetap dapat diunduh dan diakses pada riwayat versi
  And Status dokumen berubah menjadi "REVISI" (siap diperiksa kembali oleh Supervisor).
  ```

### 22.3 Absensi Siswa & Otorisasi Penugasan
* **AC-ATT-01: Otorisasi Berdasarkan Teaching Assignment**
  ```gherkin
  Given Guru Siti ditugaskan mengajar "Bahasa Indonesia" di "Kelas 7A"
  And Guru Siti TIDAK ditugaskan di "Kelas 7B"
  When Guru Siti mengakses halaman input absensi siswa untuk "Kelas 7A"
  Then Sistem menampilkan daftar siswa Kelas 7A dan formulir absensi terbuka
  When Guru Siti mencoba mengakses URL absensi untuk "Kelas 7B"
  Then Sistem memblokir akses dan mengembalikan pesan kesalahan "Akses Ditolak: Anda bukan pengajar kelas ini" (HTTP 403 Forbidden).
  ```

* **AC-ATT-02: Penguncian Sesi Absensi Siswa**
  ```gherkin
  Given Guru Budi telah mengisi absensi 27 siswa Kelas 7A (25 Hadir, 2 Sakit)
  When Guru Budi menekan tombol "Simpan & Kunci Sesi Absensi"
  Then Status sesi berubah menjadi "CLOSED"
  And Seluruh tombol radio pilihan kehadiran terkunci (read-only)
  And Guru Budi tidak dapat mengubah kembali status kehadiran siswa tersebut.
  ```

### 22.4 Koreksi Absensi oleh Admin
* **AC-ADM-01: Koreksi Kehadiran Siswa oleh Staff TU**
  ```gherkin
  Given Sesi absensi Kelas 7A tanggal 15 September telah berstatus "CLOSED"
  And Siswa "Ahmad" tercatat berstatus "ALPA"
  When Staff TU membuka menu koreksi absensi dan mengubah status Ahmad menjadi "IZIN"
  And Staff TU memasukkan alasan "Surat izin sakit susulan diserahkan orang tua"
  When Staff TU menyimpan perubahan
  Then Status kehadiran Ahmad terupdate menjadi "IZIN"
  And Sistem mencatat ke tabel audit log: [User: Staff TU, Action: ATT_CORRECTION, Target: Ahmad, Old: ALPA, New: IZIN, Reason: "Surat izin sakit susulan..."].
  ```

### 22.5 Scanner Absensi Masuk/Pulang Sekolah (ADDENDUM §21)

* **AC-SCAN-01: Mode MASUK Mencatat check_in**
  ```gherkin
  Given Guru adalah Guru Piket hari ini (berdasarkan teacher_duty_schedules)
  When Guru memilih mode MASUK dan scan barcode siswa "Fauzan"
  Then Scanner berada pada mode MASUK
  And Sistem mencatat check_in pada student_school_attendances dengan server timestamp.
  ```

* **AC-SCAN-02: Mode PULANG Mencatat check_out**
  ```gherkin
  Given Guru adalah Guru Piket hari ini
  When Guru memilih mode PULANG dan scan barcode siswa "Fauzan"
  Then Scanner berada pada mode PULANG
  And Sistem mencatat check_out pada record hari tersebut.
  ```

* **AC-SCAN-03: Duplicate MASUK Ditolak**
  ```gherkin
  Given Siswa "Fauzan" sudah memiliki check_in hari ini
  When Siswa discan lagi dalam mode MASUK
  Then Record baru tidak dibuat
  And Sistem menampilkan pesan "Sudah tercatat masuk pukul 07:05".
  ```

* **AC-SCAN-04: Duplicate PULANG Ditolak**
  ```gherkin
  Given Siswa "Fauzan" sudah memiliki check_out hari ini
  When Siswa discan lagi dalam mode PULANG
  Then check_out tidak ditimpa
  And Sistem menampilkan pesan "Sudah tercatat pulang pukul 15:12".
  ```

* **AC-SCAN-05: Guru Bukan Piket Ditolak Server**
  ```gherkin
  Given Guru "Andi" bukan Guru Piket hari ini
  When Guru "Andi" mencoba membuka scanner
  Then Server menolak akses (HTTP 403)
  And Tampilan menampilkan "Anda hari ini bukan Guru Piket".
  ```

* **AC-SCAN-06: PULANG Tanpa Record MASUK Ditolak `[FINAL — KEPUTUSAN A1]`**
  ```gherkin
  Given Siswa "Fauzan" belum memiliki check_in pada tanggal berjalan
  When Guru Piket memilih mode PULANG dan scan barcode siswa "Fauzan"
  Then Server menolak scan
  And Sistem tidak membuat check_in palsu dan tidak auto-create record MASUK
  And Tampilan menampilkan "Belum ada catatan MASUK hari ini. Hubungi Admin/TU untuk koreksi."
  And Admin/TU dapat menyelesaikan kasus melalui koreksi manual dengan alasan wajib + audit log.
  ```

### 22.6 Perubahan Jadwal Piket (ADDENDUM §22)

* **AC-DUTY-01: Perubahan Jadwal Memengaruhi Akses Scanner**
  ```gherkin
  Given Fauzan adalah Guru Piket tanggal 28-09-2026
  When Admin mengganti jadwal menjadi Budi
  Then Fauzan tidak dapat menggunakan scanner untuk tanggal tersebut
  And Budi dapat menggunakan scanner.
  ```

* **AC-DUTY-02: Histori Scan Tetap Utuh**
  ```gherkin
  Given Fauzan sudah melakukan scan sebelum perubahan jadwal
  When Admin mengganti Guru Piket
  Then Record scan lama tetap memiliki Fauzan sebagai operator.
  ```

* **AC-DUTY-03: Halaman Scanner Terbuka Tetap Ditolak**
  ```gherkin
  Given Guru sudah membuka halaman scanner
  When Admin mencabut jadwal piket Guru tersebut
  And Guru melakukan scan berikutnya
  Then Server menolak scan.
  ```

* **AC-DUTY-04: Jadwal Masa Depan Berlaku Saat Tiba**
  ```gherkin
  Given Admin mengubah jadwal masa depan
  When Tanggal tersebut tiba
  Then Akses scanner mengikuti jadwal terbaru.
  ```

---

## 23. RIWAYAT KEPUTUSAN & OPEN QUESTIONS (CLOSED — FINAL)

Seluruh pertanyaan pada daftar ini telah **disahkan oleh manusia (2026-09-27)** dan berstatus `[FINAL]`. Daftar dipertahankan sebagai *historical decision trail*; sumber otoritatif keputusan ada di `06-decision-resolution.md`:

### ~~1. Kebijakan Batas Waktu & Keterlambatan Presensi Guru~~ `[OBSOLETE — ADDENDUM §20 & §26]`
* **Status:** OBSOLETE — Fitur presensi mandiri guru dihapus total. Pertanyaan ini tidak lagi relevan.

### 2. Otorisasi Pembatalan Dokumen yang Telah Disetujui `[FINAL — ADR 05: Opsi B]`
* **Keputusan:** Dokumen `DISETUJUI` **dapat dibatalkan** (*Revoke Approval*) oleh pihak berwenang dengan berita acara/alasan wajib + audit log (dasar seluruh dokumen 01–05).

### 3. Batas Waktu Auto-Lock Sesi Absensi Siswa `[FINAL — ADR 14: Manual Close]`
* **Keputusan:** Sesi absensi mapel ditutup **manual oleh Guru**; tanpa *auto-lock scheduler* pada MVP.

### 4. Kebijakan Reset Password & Pemulihan Akun `[FINAL — ADR 10: Opsi A]`
* **Keputusan:** **Reset terpusat oleh Admin/Staff TU** melalui panel Admin (tanpa mail server/SMTP pada MVP), tercatat di audit log.

### 5. Retensi Berkas Dokumen & Manajemen Kuota Google Drive `[FINAL — ADR 08: Opsi A]`
* **Keputusan:** **Semua versi berkas disimpan permanen** (*infinite retention*); tidak ada auto-delete/hapus pasca setuju.

### 6. Ruang Lingkup Monitoring Pengawas / Supervisor `[FINAL — ADR 09: Opsi A]`
* **Keputusan:** Supervisor dapat memeriksa dokumen **seluruh guru dalam satu sekolah** tanpa batasan rumpun.

---

## 24. PENGESAHAN DOKUMEN (SIGN-OFF)

Dokumen Spesifikasi Kebutuhan Produk (*Product Requirements Document*) ini diserahkan untuk ditinjau dan disetujui bersama sebagai acuan tunggal pengerjaan arsitektur teknis dan pengembangan sistem Portal Sekolah.

| Peran Perwakilan | Nama Lengkap | Tanda Tangan / Status | Tanggal |
| :--- | :--- | :---: | :---: |
| **Lead Product Manager** | _______________________ | `DRAFT SUBMITTED` | 19 September 2026 |
| **Senior System Analyst** | _______________________ | `REVIEWED` | 19 September 2026 |
| **Kepala Sekolah / Project Sponsor** | _______________________ | `PENDING APPROVAL` | _________________ |
| **Koordinator Tata Usaha** | _______________________ | `PENDING APPROVAL` | _________________ |
