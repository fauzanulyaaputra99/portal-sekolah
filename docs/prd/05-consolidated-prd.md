# CONSOLIDATED PRODUCT REQUIREMENTS DOCUMENT & TECHNICAL BLUEPRINT
## SISTEM PORTAL SEKOLAH

**Nomor Dokumen:** PRD-CONSOLIDATED-SEKOLAH-005  
**Nama Proyek:** Portal Sekolah (Dedicated School Management & Digital Academic Repository)  
**Versi Dokumen:** 3.1.0 (FINAL — ADDENDUM + Keputusan A1/B2/C1 disahkan)  
**Status Dokumen:** APPROVED FOR DEVELOPMENT  
**Tanggal:** 2026-09-27  
**Database Platform:** MySQL 8.0+ (InnoDB)  
**Local Development:** Laragon on Windows  
**Application:** Laravel 13 (Monolith)  
**Dokumen Sumber Terkonsolidasi:**
1. `D:\anti\portal-sekolah-prd\01-product-requirements.md` (PRD-SEKOLAH-001)
2. `D:\anti\portal-sekolah-prd\02-architecture-database.md` (ARCH-SEKOLAH-002)
3. `D:\anti\portal-sekolah-prd\03-document-google-drive.md` (ARCH-SEKOLAH-003)
4. `D:\anti\portal-sekolah-prd\04-security-operations.md` (SEC-OPS-SEKOLAH-004)
5. `D:\anti\portal-sekolah-prd\06-decision-resolution.md` (ADR-SEKOLAH-006 — Sumber Keputusan Otoritatif)

**Tujuan Dokumen:**  
Menyajikan satu kesatuan acuan spesifikasi fungsional, arsitektur sistem, skema basis data MySQL 8.0+, integrasi penyimpanan cloud, dan baseline keamanan/operasional yang konsisten, terbebas dari duplikasi, dan telah mengintegrasikan seluruh resolusi keputusan arsitektur (ADR 01 s.d. ADR 20, termasuk ADR yang menjadi `[OBSOLETE]` akibat ADDENDUM) dengan lingkungan development lokal Laragon sebagai panduan teknis mutlak sebelum tahapan pengembangan sistem Portal Sekolah dimulai.

---

## DAFTAR ISI
1. [Document Information](#1-document-information)
2. [Executive Summary](#2-executive-summary)
3. [Product Scope](#3-product-scope)
4. [User Roles & RBAC](#4-user-roles--rbac)
5. [Functional Requirements Modules](#5-functional-requirements-modules)
6. [Document Management Lifecycle & Workflow](#6-document-management-lifecycle--workflow)
7. [Attendance Architecture](#7-attendance-architecture)
8. [Dynamic Class Architecture & Progression](#8-dynamic-class-architecture--progression)
9. [Teaching Assignment & Permission Scoping](#9-teaching-assignment--permission-scoping)
10. [Technical Architecture](#10-technical-architecture)
11. [Application Architecture (Laravel 13 Monolith)](#11-application-architecture-laravel-13-monolith)
12. [Database Architecture (MySQL)](#12-database-architecture-mysql)
13. [Database Integrity & Constraints](#13-database-integrity--constraints)
14. [Google Drive Architecture (Standard Account Model)](#14-google-drive-architecture-standard-account-model)
15. [Document Storage & Consistency Flow](#15-document-storage--consistency-flow)
16. [File Security & Validation](#16-file-security--validation)
17. [File Viewing & Streaming Proxy](#17-file-viewing--streaming-proxy)
18. [Document Versioning & Permanent Retention](#18-document-versioning--permanent-retention)
19. [Authorization & Anti-IDOR Enforcement](#19-authorization--anti-idor-enforcement)
20. [Security Baseline & Defense in Depth](#20-security-baseline--defense-in-depth)
21. [Authentication Security](#21-authentication-security)
22. [Session & Web Security](#22-session--web-security)
23. [Secrets, Credentials & Configuration Management](#23-secrets-credentials--configuration-management)
24. [Audit Logging & Accountability](#24-audit-logging--accountability)
25. [Backup & Disaster Recovery Strategy](#25-backup--disaster-recovery-strategy)
26. [Dependency, Server & Platform Security](#26-dependency-server--platform-security)
27. [Application Logging & Error Masking](#27-application-logging--error-masking)
28. [Operational Monitoring & Health Checks](#28-operational-monitoring--health-checks)
29. [Security Testing Protocol](#29-security-testing-protocol)
30. [Matriks Verifikasi OWASP ASVS](#30-matriks-verifikasi-owasp-asvs)
31. [Pemetaan Ancaman OWASP Top 10](#31-pemetaan-ancaman-owasp-top-10)
32. [Non-Functional Requirements (NFR)](#32-non-functional-requirements-nfr)
33: [Scalability Strategy](#33-scalability-strategy)
34. [Reporting & Data Export](#34-reporting--data-export)
35. [Final Decision Status (Integrasi ADR 01 — ADR 20)](#35-final-decision-status-integrasi-adr-01--adr-20)
36. [Production Readiness Checklist](#36-production-readiness-checklist)
37. [Development Boundary](#37-development-boundary)
38. [Rencana Strategi Pengembangan Pasca-PRD](#38-rencana-strategi-pengembangan-pasca-prd)
39. [Final Consistency Declaration](#39-final-consistency-declaration)

---

## 1. DOCUMENT INFORMATION

* **Document ID:** PRD-CONSOLIDATED-SEKOLAH-005
* **Project Name:** Portal Sekolah
* **Version:** 3.1.0 (FINAL — ADDENDUM + Keputusan A1/B2/C1 disahkan)
* **Status:** `APPROVED FOR DEVELOPMENT`
* **Date:** 2026-09-27
* **Source Documents:** 
  * `01-product-requirements.md` (Versi 2.1.0 — FINAL)
  * `02-architecture-database.md` (Versi 2.1.0 — FINAL)
  * `03-document-google-drive.md` (Versi 1.2.0 — tidak terdampak ADDENDUM)
  * `04-security-operations.md` (Versi 2.1.0 — FINAL)
  * `06-decision-resolution.md` (Versi 2.2.0 — Seluruh Keputusan FINAL/OBSOLETE; tidak ada keputusan terbuka)
* **Purpose:** Menjadi acuan tunggal (*Single Source of Truth*) perancangan dan batasan teknis sebelum tahapan koding dimulai.

---

## 2. EXECUTIVE SUMMARY

**Portal Sekolah** adalah sistem manajemen sekolah berbasis web terpadu yang dirancang untuk mengatasi permasalahan tata kelola administrasi akademik:
1. **Pengarsipan & Supervisi Perangkat Ajar Digital:** Meniadakan pengumpulan perangkat ajar guru (RPP, ATP, PROTA, PROSEM) secara serabutan via flashdisk atau pesan instan melalui repositori cloud terstruktur, berversi *immutable* permanen, dan memiliki alur persetujuan (*review workflow*) transparan dengan hak pembatalan terkendali (*controlled revoke*).
2. **Absensi Masuk/Pulang Siswa (Barcode Scanner — ADDENDUM):** Mencatat kedatangan dan kepulangan siswa harian melalui pemindaian barcode pada kartu fisik siswa menggunakan kamera HP Guru Piket, dengan mode MASUK/PULANG yang dipilih secara eksplisit dan penentuan operator berdasarkan jadwal piket di database.
3. **Jadwal Guru Piket Berbasis Data (ADDENDUM §8–§18):** Penentuan Guru Piket bersumber dari tabel `teacher_duty_schedules` yang dikelola Admin/TU, bukan dari role baru, flag user, atau hardcoded hari.
4. **Absensi Siswa Berbasis Jam Mengajar (Subject Attendance):** Memungkinkan guru mencatat absensi siswa langsung pada setiap sesi mata pelajaran yang diampunya, dengan sesi yang dibuka dan ditutup (*closed*) secara manual oleh guru.
5. **Data Master Terintegrasi & Kelas Dinamis:** Mengelola hubungan akademik guru, siswa, rombongan belajar, dan penugasan mengajar secara fleksibel dari baseline awal (Kelas 7 / 7A / 27 siswa) hingga banyak rombel tanpa merombak struktur database.

**Filosofi & Prinsip Arsitektur:**  
Sistem dibangun di atas fondasi *"Simple enough for a school, secure enough for production"*. Sistem menggunakan arsitektur monolitik modular Laravel 13 dengan MySQL 8.0+ (InnoDB) sebagai penyimpan metadata dan Standard Google Drive Account sebagai penyimpan berkas biner privat. Menolak *over-engineering* enterprise yang membebani kapasitas staf IT sekolah. Lingkungan pengembangan lokal menggunakan Laragon pada Windows, dan lingkungan produksi dapat di-deploy pada server/hosting yang kompatibel dengan Laravel dan MySQL tanpa ketergantungan pada Laragon.

---

## 3. PRODUCT SCOPE

### 3.1 In Scope (Fitur MVP)
* **Manajemen Akun & RBAC:** Dukungan 3 role pengguna (Admin/Staff TU, Guru, Atasan/Supervisor). Untuk pejabat merangkap, diterapkan pemisahan 2 akun terpisah (ADR 04).
* **Manajemen Master Data:** Tahun ajaran, jenjang tingkat (*grade level*), rombel dinamis (*classes*), guru, siswa, dan kurikulum mata pelajaran (*subjects*).
* **Penugasan Mengajar (*Teaching Assignment*):** Pengikatan Guru, Mapel, Kelas, dan Tahun Ajaran sebagai simpul otorisasi sistem.
* **Jadwal Pelajaran Mingguan (*Schedules*):** Pemetaan jadwal rutin per penugasan mengajar.
* **Absensi Masuk/Pulang Siswa (Barcode Scanner):** Pemindaian barcode kartu siswa via kamera HP Guru Piket dengan mode MASUK/PULANG eksplisit, stempel waktu server, dan pencegahan duplikasi berlapis (ADDENDUM §1–§7).
* **Jadwal Guru Piket:** CRUD jadwal piket oleh Admin/TU berbasis `teacher_duty_schedules`, diverifikasi server-side pada setiap request scanner, seluruh perubahan diaudit (ADDENDUM §8–§18).
* **Absensi Siswa Per Sesi Mata Pelajaran:** Pembukaan sesi (OPEN), pre-fill status Hadir, pemilihan status H/I/S/A, dan penutupan manual oleh guru (Manual Close - ADR 14).
* **Koreksi Absensi Administratif:** Fitur koreksi khusus Admin TU untuk absensi siswa (masuk/pulang sekolah dan per mapel) dengan kewajiban justifikasi alasan dan pencatatan jejak audit.
* **Repositori Dokumen Guru:** Pengunggahan berkas RPP, ATP, PROTA, PROSEM dengan batas maksimal 10 MB per berkas (ADR 07) dan validasi biner mendalam (*magic bytes*).
* **Versioning Immutability & Retensi Permanen:** Penomoran berkas bertingkat ($v1, v2, v3$) tanpa saling menimpa berkas lama, disimpan permanen tanpa penghapusan otomatis (ADR 08).
* **Supervisi Akademik & Umpan Balik:** Alur pemeriksaan dokumen terbuka bagi seluruh guru di sekolah (ADR 09), dengan wewenang persetujuan (*Approve*) atau permintaan perbaikan (*Request Revision*), serta hak pembatalan persetujuan resmi (*Revoke Approval* - ADR 05).
* **Penyimpanan Cloud Privat:** Integrasi Google Drive API berbasis Standard Google Drive Account secara server-side proxy tanpa mengekspos tautan publik ke browser (ADR 06).
* **Pelaporan & Rekapitulasi:** Ekspor data tabular dalam format CSV dan Microsoft Excel (.xlsx), serta persiapan format cetak resmi PDF `[SHOULD HAVE]`.
* **Keamanan Sistem & Audit Trail:** Penegakan OWASP ASVS Level 1, proteksi IDOR server-side, rate limiting, hashing Argon2id/Bcrypt, primary key BIGINT identity (ADR 15), akses audit log khusus Admin/TU (ADR 17), dan session inactivity timeout 120 menit (ADR 11).

### 3.2 Out of Scope (Batasan Sistem / Non-Goals)
Sistem secara eksplisit **TIDAK MENGIMPLEMENTASIKAN**:
1. **Sistem Hardware RFID:** Tidak ada kartu RFID, reader fisik, endpoint pemrosesan sinyal RFID, atau integrasi mesin absensi RFID. Absensi masuk/pulang siswa menggunakan **barcode visual yang dicetak pada kartu fisik siswa**, dipindai lewat kamera browser — bukan RFID/NFC/chip.
2. **Aplikasi Mobile Native:** Tidak membangun aplikasi Android/iOS native khusus. Cukup antarmuka web responsif (scanner berjalan di browser HP).
3. **Frontend SPA Kompleks:** Tidak menggunakan React, Vue, Angular, atau Svelte. Semua antarmuka menggunakan server-rendered Laravel Blade.
4. **Editor Dokumen Kolaboratif Online:** Tidak membangun in-app document editor ala Google Docs atau Microsoft 365. Dokumen disiapkan guru secara offline dan diunggah sebagai file utuh.
5. **Arsitektur Microservices & Kubernetes:** Tidak menggunakan microservices, service mesh, orkestrator k8s, atau multi-repo.
6. **Distributed Cache / External Message Broker Berat:** Tidak menggunakan Apache Kafka, RabbitMQ, atau Redis Cluster.
7. **Modul Finansial / Pembayaran SPP:** Tidak mengelola transaksi keuangan, iuran sekolah, atau kas komite.
8. **Portal Mandiri Siswa & Orang Tua:** Pada tahap MVP, siswa dan wali murid tidak memiliki akun login ke portal.
9. **Geofencing / GPS Biometrics Presisi Tinggi:** Tidak menggunakan kalkulasi koordinat GPS radius meteran yang kompleks.
10. **Infrastruktur Antivirus Lokal Berat (ClamAV):** Tidak memasang daemon ClamAV pada server aplikasi untuk MVP (ADR 13).
11. **Layanan Pengiriman Email Transaksional (SMTP):** Tidak menyediakan self-service reset password via email pada MVP (ADR 10).
12. **~~Absensi Mandiri Guru (Teacher Self-Attendance)~~:** **DIHAPUS TOTAL** oleh ADDENDUM §20 & §26. Tidak ada tabel `teacher_attendances`, tidak ada dashboard/status presensi guru, tidak ada rekap kehadiran guru, dan tidak ada penetapan ALPA guru.
13. **Realtime Infrastructure untuk Jadwal Piket:** Tidak memerlukan WebSocket, Redis, atau Queue. Validasi jadwal cukup dilakukan server-side pada setiap request scanner (ADDENDUM §12 & §24).

---

## 4. USER ROLES & RBAC

Sistem menetapkan 3 peran formal pengguna dengan pemisahan wewenang yang tegas:

| Role | Deskripsi | Akses & Wewenang Utama | Batasan Akses |
| :--- | :--- | :--- | :--- |
| **Admin / Staff TU** | Pengelola data induk, operasional administratif, dan penugasan akademik sekolah. | * Full CRUD Data Master (Guru, Siswa, Kelas, Mapel, TA)<br>* Penjadwalan & Teaching Assignment<br>* **CRUD Jadwal Guru Piket + Audit (ADDENDUM §9, §17)**<br>* Manajemen Akun & Reset Kredensial Manual (ADR 10)<br>* **Koreksi Absensi Siswa Masuk/Pulang & Per Mapel (wajib alasan + audit)**<br>* Ekspor Laporan & Peninjauan Eksklusif Audit Log (ADR 17) | Dilarang mengubah status persetujuan dokumen atas nama Supervisor atau mengajar kelas. |
| **Guru** | Tenaga pendidik yang mengelola dokumen ajar dan kehadiran kelas. | * Manajemen Profil Pribadi<br>* **Scanner Absensi Masuk/Pulang Siswa — HANYA jika terjadwal sebagai Guru Piket pada tanggal berjalan (ADDENDUM §8, §12)**<br>* Manajemen Repositori Dokumen Pribadi (CRUD Draft, Unggah Revisi Maks 10 MB - ADR 07)<br>* Membuka & Mengunci Manual Sesi Absensi Siswa (ADR 14) | Terkunci ketat pada berkas miliknya sendiri (*Anti-IDOR*), kelas/mapel yang ditugaskan kepadanya, dan scanner yang dikunci server bila bukan Guru Piket. |
| **Atasan / Supervisor** | Pimpinan (Kepala Sekolah/Waka/Pengawas) yang memantau kualitas pengajaran. | * Monitoring Kepatuhan Pengumpulan Dokumen Seluruh Guru (ADR 09)<br>* Memeriksa Pratinjau Dokumen Guru via Stream Proxy<br>* Melakukan Persetujuan (*Approve*) atau Permintaan Perbaikan (*Request Revision*) ber-catatan wajib<br>* Melakukan Pembatalan Status Persetujuan Resmi Berita Acara (*Revoke Approval* - ADR 05)<br>* Rekapitulasi Evaluasi Akademik | Dilarang mengedit berkas fisik guru, membuka sesi absensi kelas, mengakses menu audit log umum (ADR 17), mengubah jadwal piket, atau memodifikasi data master. |

> **Kebijakan Akun Peran Ganda (Guru Merangkap Supervisor — ADR 04):**  
> Bagi guru yang memiliki jabatan struktural (contoh: Wakil Kepala Sekolah yang juga mengajar), diterapkan kebijakan **dua akun terpisah**: satu akun NIP untuk tugas pengajaran (termasuk penugasan jadwal piket), serta satu akun khusus untuk tugas supervisi pimpinan. Hal ini menjamin independensi hak akses dan mencegah terjadinya konflik kepentingan (self-approval).

---

## 5. FUNCTIONAL REQUIREMENTS MODULES

### 5.1 Authentication & Account
* **Purpose:** Mengelola identitas pengguna, login, logout, dan status keamanan akun.
* **Actors:** Seluruh Pengguna (Admin TU, Guru, Supervisor).
* **Capabilities:** Login berbasis NIP/Username dan password; sesi terenkripsi; penggantian sandi mandiri; penonaktifan akun oleh Admin.
* **Rules:** Sandi di-hash menggunakan Argon2id atau Bcrypt (cost factor $\ge 12$); pembatasan login (*rate limiting*) 5x gagal per 60 detik; regenerasi sesi saat login; invalidasi penuh saat logout; akun nonaktif (`is_active = FALSE`) langsung ditolak masuk.
* **Restrictions:** Kredensial mentah dilarang dicatat di log. **Pemulihan kata sandi dilakukan secara terpusat oleh Admin/Staff TU (ADR 10)**. Guru yang lupa sandi melapor ke TU untuk di-reset secara manual. Tidak ada layanan self-service via email pada MVP.

### 5.2 Dashboard
* **Purpose:** Menyajikan ringkasan metrik operasional dan antrean tugas sesuai peran pengguna.
* **Actors:** Admin TU, Guru, Supervisor.
* **Capabilities:** 
  * Admin: Metrik total guru/siswa/kelas, ringkasan status kelengkapan dokumen, rekap absensi masuk/pulang siswa hari ini, **Guru Piket hari ini**, 5 log audit terbaru.
  * Guru: **Kartu status Guru Piket hari ini (terkunci/terbuka + akses scanner)**, agenda jadwal mengajar hari ini (tombol input absensi mapel), status dokumen (peringatan perlu revisi).
  * Supervisor: Kartu rasio kepatuhan pengumpulan dokumen sekolah, tabel antrean dokumen masuk seluruh guru (ADR 09) yang perlu ditinjau (*Actionable Queue*).
* **Rules:** Data diagregasi secara server-side dengan query berindeks efisien.
* **Restrictions:** Tidak menampilkan data privat milik guru lain pada dashboard guru.

### 5.3 Teacher Management
* **Purpose:** Mengelola data induk tenaga pendidik dan akun login terkait.
* **Actors:** Admin / Staff TU.
* **Capabilities:** Tambah, lihat, ubah, nonaktifkan profil guru; penetapan NIP, status kepegawaian (PNS/PPPK/GTY/GTT/Honorer), dan kontak.
* **Rules:** Relasi 1-to-1 dengan tabel `users`. Username guru menggunakan NIP (atau identifikasi unik jika belum memiliki NIP).
* **Restrictions:** Data guru yang memiliki rekam jejak historis penugasan dilarang dihapus permanen (*ON DELETE RESTRICT*).

### 5.4 Student Management
* **Purpose:** Mengelola basis data induk siswa sekolah.
* **Actors:** Admin / Staff TU.
* **Capabilities:** Perekaman data siswa (NIS, NISN, Nama, Jenis Kelamin, Tanggal Lahir, Kontak Orang Tua, Status Aktif/Lulus/Mutasi).
* **Rules:** NIS dan NISN wajib unik. Siswa tidak terikat statis pada kolom kelas di tabel `students`.
* **Restrictions:** Tidak ada akun login portal mandiri untuk siswa pada fase MVP.

### 5.5 Class Management (Rombongan Belajar Dinamis)
* **Purpose:** Mengelola rombel per tahun ajaran dan penempatan siswa.
* **Actors:** Admin / Staff TU.
* **Capabilities:** Pembuatan rombel (contoh: 7A, 7B, 8A); penetapan wali kelas; penempatan siswa (*class enrollment*).
* **Rules:** Terikat pada `academic_year_id` dan `grade_level_id`. Kombinasi `(academic_year_id, name)` wajib unik. Siswa hanya boleh terdaftar di 1 rombel pada tahun ajaran yang sama.
* **Restrictions:** Tidak ada nama rombel yang di-hardcode dalam logika kode.

### 5.6 Academic Year (Tahun Ajaran)
* **Purpose:** Mengatur siklus kalender akademik sekolah.
* **Actors:** Admin / Staff TU.
* **Capabilities:** Tambah tahun ajaran (contoh: 2026/2027 Ganjil/Genap); penetapan periode tanggal; aktivasi status tahun ajaran.
* **Rules:** Tepat 1 record tahun ajaran yang berstatus `is_active = TRUE` dalam satu waktu (ditegakkan via database partial index).
* **Restrictions:** Penutupan tahun ajaran mengunci perubahan mutasi data akademik semester bersangkutan.

### 5.7 Subject Management (Mata Pelajaran)
* **Purpose:** Mengelola katalog kurikulum mata pelajaran sekolah.
* **Actors:** Admin / Staff TU.
* **Capabilities:** Perekaman kode mapel, nama mapel, dan kategori (Umum, Mulok, Peminatan).
* **Rules:** Kode mapel wajib unik (contoh: `MTK-07`).
* **Restrictions:** Mapel yang sudah memiliki riwayat penugasan mengajar tidak dapat dihapus sembarangan.

### 5.8 Teaching Assignment (Penugasan Mengajar)
* **Purpose:** Mengaitkan Guru, Mata Pelajaran, Rombel, dan Tahun Ajaran sebagai simpul batas otorisasi.
* **Actors:** Admin / Staff TU.
* **Capabilities:** Menetapkan guru pengampu suatu mapel di kelas tertentu pada periode aktif.
* **Rules:** Kombinasi `(academic_year_id, class_id, subject_id, teacher_id)` wajib unik.
* **Restrictions:** Merupakan prasyarat mutlak sebelum jadwal dibuat atau sesi absensi dibuka oleh guru.

### 5.9 Schedule (Jadwal Pelajaran)
* **Purpose:** Menyimpan slot waktu rutin pembelajaran kelas.
* **Actors:** Admin / Staff TU.
* **Capabilities:** Perekaman hari (1-7), jam mulai, dan jam selesai per penugasan mengajar.
* **Rules:** Menunjuk langsung ke `teaching_assignment_id`. Waktu `end_time > start_time`.
* **Restrictions:** Menolak tumpang tindih (*time conflict*) ruang atau guru pada jam yang sama.

### 5.10 Student School Attendance — Absensi Masuk/Pulang Sekolah (Barcode Scanner)
> *Menggantikan modul `Teacher Attendance` yang DIHAPUS TOTAL (ADDENDUM §20 & §26).*
* **Purpose:** Pencatatan kedatangan dan kepulangan siswa harian berbasis scan barcode pada kartu fisik siswa melalui kamera HP Guru Piket (Web App/Browser).
* **Actors:** Guru Piket (operator scan), Admin TU (koreksi).
* **Capabilities:** Pilih mode `[ 🟢 MASUK ]` / `[ 🔵 PULANG ]` secara eksplisit; buka kamera browser; scan barcode; sistem mencatat `check_in_time` atau `check_out_time` dengan server timestamp; rekap harian siswa masuk/belum masuk/sudah pulang.
* **Rules:**
  * **Mode Eksplisit (ADDENDUM §1):** DILARANG auto-detect `scan pertama = MASUK` / `scan kedua = PULANG`. Guru Piket wajib memilih mode sebelum scan.
  * **Server Timestamp (ADDENDUM §2):** Waktu diambil dari server, bukan perangkat HP klien.
  * **Anti-Duplikasi (ADDENDUM §3 & §5):** Kombinasi application validation + `UNIQUE(student_id, attendance_date)`. Scan ulang MASUK/PULANG ditolak tanpa membuat/menimpa record.
  * **Pulang Tanpa Record Masuk (`[FINAL — KEPUTUSAN A1]`):** Scan PULANG tanpa record MASUK pada tanggal berjalan **DITOLAK** server. Sistem tidak membuat check-in palsu / auto-create. Penyelesaian resmi: koreksi manual Admin/TU (alasan wajib + audit `SCHOOL_ATT_CORRECT`). UI pesan: "Belum ada catatan MASUK hari ini. Hubungi Admin/TU untuk koreksi."
  * **Pergantian Mode Tanpa Logout (ADDENDUM §7):** Guru Piket dapat berpindah MASUK → PULANG pada sesi yang sama; mode aktif wajib tampil jelas.
  * **Operator Tercatat:** `check_in_by_teacher_id` / `check_out_by_teacher_id` merekam Guru Piket pelaksana scan (ADDENDUM §15–§16).
* **Restrictions:** Guru Piket tidak dapat mengubah/menghapus record. Koreksi hanya oleh Admin TU dengan alasan wajib + audit log. Akses scanner tunduk pada `teacher_duty_schedules` (lihat 5.10b).

### 5.10b Teacher Duty Schedule — Jadwal Guru Piket (ADDENDUM §8–§18)
* **Purpose:** Sumber kebenaran tunggal penentuan siapa Guru Piket pada tanggal tertentu, yang menjadi prasyarat akses scanner.
* **Actors:** Admin / Staff TU (pengelola penuh).
* **Capabilities:** CRUD jadwal (Tambah, Edit, Hapus, Ganti Guru, Ganti Tanggal) berbasis data `schedule_date` + `teacher_id`.
* **Rules:**
  * **Guru Piket Bukan Role Baru (ADDENDUM §8):** `users.role` tetap `admin` / `teacher` / `supervisor`.
  * **Berbasis Data, Bukan Flag User (ADDENDUM §10):** DILARANG `users.is_piket = true`, `if user == "Fauzan"`, `if today == "Monday"`, atau jadwal hardcoded di source code.
  * **Verifikasi Server-Side per Request (ADDENDUM §12):** Setiap request scanner (buka halaman maupun submit scan) mengecek ulang jadwal di database. Frontend bukan sumber kebenaran.
  * **Akses Tidak Dipertahankan (ADDENDUM §13–§14):** Jika jadwal dicabut/diganti saat halaman scanner masih terbuka, request scan berikutnya ditolak server dengan pesan *"Jadwal piket Anda telah berubah."*
  * **Histori Immutabel (ADDENDUM §15–§16):** Perubahan jadwal tidak mengubah operator scan historis, waktu scan, maupun record absensi yang telah tersimpan.
  * **Aturan Waktu (ADDENDUM §18):** Jadwal masa depan bebas diubah; jadwal hari ini berubah dengan efek segera; jadwal masa lalu dengan record absensi wajib audit dan tidak menulis ulang histori.
  * **Audit Wajib (ADDENDUM §17):** Tambah/Edit/Ganti Guru/Ganti Tanggal/Hapus tercatat di `audit_logs` dengan minimal `actor`, `action`, tanggal jadwal, guru lama, guru baru, `timestamp`.
* **Restrictions:** Guru & Supervisor tidak memiliki hak ubah jadwal (Guru: read-only status diri sendiri; Supervisor: read-only).
* **UI Lock (ADDENDUM §19):** Guru yang bukan piket melihat panel terkunci: *"🔒 Anda hari ini bukan Guru Piket."*


### 5.11 Student Subject Attendance (Absensi Siswa Per Sesi Mapel)
* **Purpose:** Pencatatan kehadiran siswa per pertemuan mata pelajaran di kelas.
* **Actors:** Guru Pengampu (pembukaan & penutupan), Admin TU (koreksi).
* **Capabilities:** Buka sesi (`OPEN`), inisialisasi default seluruh siswa `HADIR`, pengubahan status individu (Hadir/Izin/Sakit/Alpa), simpan dan kunci sesi (`CLOSED`).
* **Rules:** 
  * Guru wajib memiliki penugasan sah untuk kelas/mapel tersebut.
  * **Manual Close Sesi (ADR 14):** Guru bertanggung jawab membuka dan menutup sesi secara manual setelah selesai mencatat kehadiran. Tidak diterapkan batas waktu penguncian otomatis (*no auto-lock scheduler*) pada MVP.
  * Setelah sesi berstatus `CLOSED`, seluruh baris kehadiran siswa terkunci permanen (*read-only*) bagi guru.
* **Restrictions:** Guru dilarang mengedit absensi pasca status `CLOSED`. Koreksi administratif dilakukan oleh Admin TU atas permohonan resmi guru dengan kewajiban mencantumkan alasan minimal 10 karakter.

### 5.12 Teacher Document Repository
* **Purpose:** Pusat pengarsipan berkas perangkat ajar digital guru (RPP, ATP, PROTA, PROSEM).
* **Actors:** Guru (pemilik), Supervisor (peninjau), Admin TU (pemantau).
* **Capabilities:** Pembuatan dokumen baru; unggah berkas fisik (PDF/Word/Excel); pemantauan status persetujuan.
* **Rules:** 
  * Berkas biner diunggah ke Standard Google Drive Account sekolah (ADR 06).
  * **Batas Maksimal Ukuran Berkas = 10 MB per berkas (ADR 07)**.
  * Validasi server-side mencakup whitelist ekstensi, MIME type, dan pemeriksaan tanda tangan biner (*Magic Bytes*).
  * **Tanpa Antivirus Lokal ClamAV (ADR 13):** MVP tidak memasang daemon ClamAV pada server guna menghemat sumber daya hardware, mengandalkan validasi biner mendalam dan isolasi penyimpanan cloud.
  * Checksum SHA-256 dihitung lokal sebelum upload dan disimpan di MySQL sebagai *Single Source of Truth* integritas.
* **Restrictions:** Guru hanya dapat mengelola dokumen yang berelasi dengan penugasannya sendiri.

### 5.13 Document Versioning & Permanent Retention
* **Purpose:** Menjaga histori perbaikan berkas secara *immutable*.
* **Actors:** Guru (pengunggah revisi), Sistem.
* **Capabilities:** Pengunggahan revisi menghasilkan versi baru ($v1 \rightarrow v2 \rightarrow v3$) dengan nomor urut bertambah otomatis; versi lama tetap utuh dan dapat ditelusuri.
* **Rules:** 
  * Berkas fisik versi lama di Google Drive tidak ditimpa (*no overwrite*). `UNIQUE(document_id, version_number)`.
  * **Retensi Permanen (ADR 08):** Seluruh versi berkas fisik ($v1, v2, v3, \dots$) disimpan permanen di Google Drive tanpa ada penghapusan otomatis (*no automatic deletion / garbage collection*), menjamin keutuhan bukti audit supervisi untuk akreditasi.
* **Restrictions:** Guru dilarang menghapus berkas versi terdahulu yang sudah diajukan ke pimpinan.

### 5.14 Supervisor Review & Revocation
* **Purpose:** Evaluasi kualitas dan kepatuhan perangkat ajar oleh pimpinan sekolah.
* **Actors:** Atasan / Supervisor.
* **Capabilities:** 
  * **Cakupan Supervisi Terbuka (ADR 09):** Supervisor dapat melihat antrean dan memeriksa dokumen seluruh guru di sekolah tanpa pembatasan rumpun mata pelajaran.
  * Melihat pratinjau dokumen via streaming proxy terproteksi.
  * Menetapkan keputusan: `SETUJUI` (status dokumen jadi `DISETUJUI`) atau `MINTA_REVISI` (status jadi `PERLU_REVISI`).
  * **Otorisasi Pembatalan Persetujuan (Revoke Approval — ADR 05):** Dokumen yang telah berstatus `DISETUJUI` dapat dibatalkan statusnya kembali menjadi `PERLU_REVISI` oleh pihak berwenang (Kepala Sekolah/Admin) jika ditemukan kekeliruan fatal. Aksi revokasi wajib menyertakan berita acara/alasan tertulis dan dicatat ketat pada audit log. Berkas fisik versi lama tidak dihapus, dan guru diizinkan mengunggah versi perbaikan baru.
* **Rules:** Aksi `MINTA_REVISI` dan pembatalan (*revoke*) wajib menyertakan catatan evaluasi minimal 10 karakter.
* **Restrictions:** Supervisor tidak dapat mengubah berkas biner guru secara langsung.

### 5.15 Reports
* **Purpose:** Rekapitulasi administratif untuk keperluan pelaporan dan akreditasi sekolah.
* **Actors:** Admin / Staff TU, Pimpinan.
* **Capabilities:** Rekap absensi masuk/pulang siswa harian & bulanan (termasuk identitas Guru Piket operator); rekap kehadiran siswa per mapel/kelas; riwayat jadwal piket guru; matriks kelengkapan dokumen ajar guru.
* **Rules:** Filter berbasis rentang tanggal, tahun ajaran, dan status. Ekspor data tabular ke CSV dan XLSX (Excel).
* **Restrictions:** Format cetak PDF resmi ber-kop surat berstatus `[SHOULD HAVE]`.

### 5.16 Audit Logs
* **Purpose:** Menyediakan akuntabilitas dan jejak forensik seluruh aktivitas krusial sistem.
* **Actors:** Sistem (pencatat otomatis), Admin / Staff TU (peninjau eksklusif).
* **Capabilities:** Pencatatan stempel waktu, user ID, role, aksi, entitas target, snapshot nilai lama/baru (`old_values`, `new_values` JSON), IP, dan user-agent.
* **Rules:** 
  * Bersifat *append-only* (tidak ada antarmuka hapus/ubah di level aplikasi).
  * **Hak Akses Eksklusif Admin TU (ADR 17):** Halaman audit log umum hanya dapat diakses oleh Admin/Staff TU. Supervisor tidak diberikan halaman audit log umum, namun tetap dapat melihat histori evaluasi dan komentar pada dokumen yang direview-nya.
* **Restrictions:** Kredensial, sandi, token otentikasi, dan isi biner berkas wajib disaring (*masked*) dari pencatatan log.

### 5.17 Settings (Pengaturan Sistem)
* **Purpose:** Konfigurasi parameter operasional portal sekolah.
* **Actors:** Admin / Staff TU.
* **Capabilities:** Pengaturan informasi profil sekolah, aktivasi semester/tahun ajaran, dan pengaturan **periode jam masuk / jam pulang sekolah** yang menjadi acuan operasional scanner absensi masuk/pulang siswa. ~~(Konfigurasi cutoff keterlambatan presensi guru — ADR 02 — DIHAPUS karena fitur presensi guru dihapus total oleh ADDENDUM §20 & §26.)~~
* **Rules:** Perubahan konfigurasi dicatat dalam tabel audit log.

---

## 6. DOCUMENT WORKFLOW

Siklus hidup dokumen perangkat ajar dikendalikan oleh *state machine* yang kaku, deterministik, dan terlindungi otorisasi server-side:

```text
       [ Guru ]
      Unggah v1
          │
          ▼
       ( DRAFT )
          │
      Guru Klik "Ajukan Review"
          │
          ▼
      ( DIKIRIM ) ◄─────────────────────────────────────────┐
          │                                                 │
   Supervisor Buka Review                                   │
          │                                                 │
          ▼                                                 │
     ( DIREVIEW )                                           │
          │                                                 │
     ┌────┴──────────────────────────┐                      │
     │                               │                      │
Supervisor Pilih               Supervisor Pilih             │
  "SETUJUI"                    "MINTA_REVISI"               │
     │                         (Wajib Catatan)              │
     ▼                               │                      │
( DISETUJUI )                        ▼                      │
     │                        ( PERLU_REVISI )              │
     │                               │                      │
     │ [Pihak Berwenang              │                      │
     │  Revoke Approval - ADR 05]    │                      │
     │ (Wajib Alasan & Audit)        │                      │
     └───────────────────────────────┤                      │
                                     │                      │
                               Guru Unggah v2               │
                               (Versi Baru)                 │
                                     │                      │
                                     ▼                      │
                                ( REVISI )                  │
                                     │                      │
                                Guru Kirim ─────────────────┘
```

### Aturan Transisi Status Dokumen:
1. **Pencegahan Penimpaan File:** Versi dokumen yang telah diajukan (`DIKIRIM`) tidak boleh ditimpa secara biner. Perbaikan wajib membentuk versi baru ($v_{n+1}$) pada tabel `document_versions`.
2. **Ketersediaan Versi Masa Lalu:** Seluruh versi lampau ($v1, v2, \dots$) tetap tersimpan secara fisik dan metadata, serta dapat ditinjau kembali sewaktu-waktu oleh Guru maupun Supervisor.
3. **Persetujuan Mengikat Versi Tertentu:** Keputusan `DISETUJUI` mengikat secara spesifik pada `current_version_id` yang disetujui.
4. **Mandatory Revision Reason:** Sistem menolak transisi ke `PERLU_REVISI` jika kolom catatan ulasan (`notes`) kosong (minimal 10 karakter).
5. **Mekanisme Revokasi Resmi (ADR 05):** Jika dokumen yang telah disetujui ditemukan memiliki kekeliruan fatal, pimpinan berwenang dapat menurunkan statusnya kembali ke `PERLU_REVISI` dengan mencantumkan alasan tertulis. Tindakan ini dicatat ke `audit_logs` (`DOC_APPROVAL_REVOKED`), berkas lama tidak dihapus, dan guru dapat mengunggah versi perbaikan baru.
6. **Histori Evaluasi:** Setiap putaran evaluasi disimpan dalam record tersendiri pada tabel `document_reviews` sehingga membentuk riwayat percakapan/evaluasi yang runut.

---

## 7. ATTENDANCE ARCHITECTURE

### 7.1 ~~Teacher Attendance (Absensi Mandiri Guru)~~ — DIHAPUS TOTAL (ADDENDUM §20 & §26)

> **ADDENDUM FINAL:** Fitur presensi mandiri harian guru telah **DIHAPUS TOTAL**. Tidak ada lagi fitur `teacher_attendances` di Portal Sekolah. Tabel `teacher_attendances` **TIDAK DIBUAT**.

### 7.1b Student School Attendance — Absensi Masuk/Pulang Sekolah (Barcode Scanner) (ADDENDUM §1–§26)

* **Mekanisme Absensi Masuk/Pulang:** Guru Piket (ditentukan oleh `teacher_duty_schedules` + tanggal berjalan) membuka scanner barcode kamera HP via Web App/Browser, memilih mode MASUK atau PULANG secara eksplisit, lalu men-scan barcode pada kartu fisik siswa.
* **Mode Eksplisit (ADDENDUM §1):** DILARANG menggunakan auto-detect (scan pertama = MASUK, scan kedua = PULANG). Guru Piket wajib memilih mode sebelum scan.
* **Server Timestamp (ADDENDUM §2):** Waktu absensi menggunakan waktu server MySQL, bukan waktu HP.
* **Duplikasi Dicegah (ADDENDUM §3, §5):** Application validation + database unique constraint `(student_id, attendance_date)`.
* **Pulang Tanpa Record Masuk (`[FINAL — KEPUTUSAN A1]`):** DITOLAK server; tanpa check-in palsu/auto-create; jalur resmi koreksi Admin/TU + audit.
* **Guru Piket Bukan Role Baru (ADDENDUM §8):** Role tetap `admin`, `teacher`, `supervisor`. Status piket = jadwal + tanggal.
* **Jadwal Piket Berbasis Data (ADDENDUM §9–§10):** Dikelola Admin/TU via tabel `teacher_duty_schedules`. DILARANG hardcoded.
* **Server Validasi per Request (ADDENDUM §12–§14):** Setiap scan, server cek ulang jadwal. Halaman scanner terbuka tidak mempertahankan akses.
* **Histori Immutable (ADDENDUM §15–§16):** Perubahan jadwal TIDAK mengubah record historis.
* **Audit Wajib (ADDENDUM §17):** Setiap CRUD jadwal piket tercatat di `audit_logs`.
* **Koreksi Administratif:** Guru Piket tidak dapat mengoreksi data sendiri. Admin TU dapat melakukan koreksi dengan kewajiban mencantumkan alasan justifikasi resmi yang tersimpan permanen di `audit_logs`.

### 7.2 Student Subject Attendance (Absensi Siswa Per Jam Pelajaran)
* **Alur Eksekusi:**
  ```text
  Guru Login 
     ↓ 
  Pilih Rombel & Mapel (Berdasarkan Teaching Assignment)
     ↓ 
  Buka Sesi Absensi (Status: OPEN)
     ↓ 
  Sistem Pre-fill Seluruh Anggota Kelas = HADIR
     ↓ 
  Guru Menyesuaikan Siswa yang IZIN / SAKIT / ALPA
     ↓ 
  Guru Menutup Sesi Secara Manual (Status: CLOSED - ADR 14)
  ```
* **Integritas Penugasan:** Guru hanya dapat membuka sesi jika memiliki record yang valid pada `teaching_assignments` untuk rombel dan mapel tersebut.
* **Penutupan Sesi Manual (ADR 14):** Guru menutup sesi absensi secara manual setelah selesai mencatat kehadiran siswa. Tidak ada batas waktu auto-lock otomatis pada MVP.
* **Immutability Setelah Tutup:** Sesi berstatus `CLOSED` langsung dikunci menjadi *read-only* bagi guru.
* **Koreksi Terkendali:** Koreksi pasca-tutup hanya dapat dilakukan oleh Admin TU atas permintaan resmi guru, dengan pencatatan audit log komprehensif.

---

## 8. DYNAMIC CLASS ARCHITECTURE & PROGRESSION

Sistem dirancang sepenuhnya dinamis tanpa ada batas rombel yang terkunci pada kode sumber:

### 8.1 Model Konseptual Tiga Lapis
1. **Master Tingkat (`grade_levels`):** Mendefinisikan tingkatan pendidikan (contoh: Tingkat 7, Tingkat 8, Tingkat 9).
2. **Rombongan Belajar (`classes`):** Mengikat nama kelas ("7A", "7B", "8C") dengan tingkat dan tahun ajaran aktif (`academic_year_id`).
3. **Daftar Anggota Rombel (`class_enrollments`):** Memetakan relasi siswa (`student_id`) ke rombel (`class_id`) pada periode kalender tertentu.

```text
Academic Year (e.g., 2026/2027)
      ↓
Class (e.g., 7A, 7B, 8A, 9C)
      ↓
Class Enrollment (Student Roster)
      ↓
Teaching Assignment (Teacher + Subject + Class)
      ↓
Schedule (Time Slot)
      ↓
Attendance Sessions & Records
```

### 8.2 Skalabilitas Tanpa Perubahan Kode
* **Baseline Awal:** Tingkat 7, Kelas 7A, 27 siswa adalah kondisi data awal (*initial data baseline*), bukan batasan kapabilitas sistem.
* **Pertumbuhan:** Penambahan rombel baru (7B, 7C, 8A, 9B) dilakukan secara mandiri oleh Admin melalui antarmuka manajemen data master tanpa perlu migrasi database tambahan.
* **Larangan Keras:** Dilarang menggunakan logika pengkondisian statis berbasis string seperti `if ($class->name == '7A')` pada seluruh lapisan aplikasi.

---

## 9. TEACHING ASSIGNMENT & PERMISSION SCOPING

Simpul otorisasi utama pembelajaran diatur oleh relasi `teaching_assignments`:

$$\text{Teaching Assignment} = (\text{Teacher} + \text{Subject} + \text{Class} + \text{Academic Year})$$

* **Multi-Assignment:** Seorang guru dapat memiliki banyak penugasan mengajar (contoh: Guru Budi mengampu Matematika di 7A dan 8A).
* **Strict Permission Scoping:**
  * Guru hanya memiliki izin membuka sesi absensi pada kombinasi kelas dan mapel yang secara eksplisit terdaftar di penugasannya.
  * Percobaan membuka sesi absensi di luar penugasan sah ditolak secara mutlak pada lapisan backend dengan respons **HTTP 403 Forbidden**.
* **Konsistensi Jadwal:** Tabel `schedules` menunjuk langsung ke `teaching_assignment_id` untuk mencegah kontradiksi penugasan pengajar.

---

## 10. TECHNICAL ARCHITECTURE

Sistem mengadopsi stack monolitik terpadu yang efisien, berkinerja tinggi, dan mudah dipelihara:

```text
Browser Klien (Guru, Admin, Supervisor)
                 │
                 │ HTTPS (TLS 1.2 / TLS 1.3)
                 │ Cookie: HttpOnly, SameSite=Lax, Secure
                 ▼
     Web Server / Reverse Proxy
                 │
                 ▼
    Laravel 13 Application (Monolith)
    ├── Authentication Guard (Argon2id/Bcrypt)
    ├── RBAC & Resource Policies (Server-Side)
    ├── Form Request Validation (Magic Bytes & Max 10MB)
    ├── Core Business Domain & Services
    ├── Server-Side Stream Reverse Proxy
    └── Audit Trail & Application Logging
         │                           │
         ▼                           ▼
  MySQL 8.0+ (InnoDB)       Standard Google Drive Account
  - BIGINT UNSIGNED PK       - Dedicated Institution Folder
  - Relational Metadata      - Zero Public Sharing
  - Foreign Key Constraints  - Permanent Binary Versions
  - Immutable Audit Logs     - Access via Service Account
         │                           │
         │ (Dev: Laragon)            │
         └─────────────┬─────────────┘
                       ▼
               Operational Layer
               ├── Automated Scheduled Backup (mysqldump, RPO 24h, RTO 8h)
               ├── Permanent Semester Backup Retention
               ├── Health Check & Disk Monitoring
               └── High-Level Incident Response
```

### Rincian Stack Teknologi:
* **Backend Framework:** Laravel 13 (PHP LTS)
* **Basis Data:** MySQL 8.0 or newer (InnoDB Engine)
* **Lingkungan Development Lokal:** Laragon on Windows (PHP, Apache/Nginx, MySQL)
* **Lingkungan Produksi:** Server / Hosting yang kompatibel dengan Laravel 13 dan MySQL 8.0+ (Platform-Agnostic, tanpa ketergantungan Laragon)
* **Penyimpanan Berkas Cloud:** Standard Google Drive Account (Integrasi via Google API Client & Service Account)
* **Frontend Rendering:** Server-Side Rendered Laravel Blade
* **Styling Antarmuka:** Tailwind CSS (Utility-First Design)
* **Interaktivitas Klien:** Vanilla JavaScript (Ringan, tanpa overhead framework SPA)
* **Build & Asset Bundler:** Vite
* **Manajemen Paket:** Composer (PHP) & npm (Frontend Assets)

---

## 11. APPLICATION ARCHITECTURE (LARAVEL 13 MONOLITH)

Aplikasi dibangun menggunakan pola arsitektur yang terstruktur dan bersih:
1. **Routing:** Dikelompokkan dengan prefix dan middleware peran yang jelas (`/admin/*`, `/guru/*`, `/supervisor/*`).
2. **Controllers:** Menangani alur request-response HTTP secara ramping (*lean controllers*). Operasi CRUD langsung didelegasikan ke Eloquent Model.
3. **Form Requests:** Mengisolasi seluruh aturan validasi input, tipe data, dan pemeriksaan ukuran berkas (maksimal 10 MB - ADR 07) sebelum masuk ke logika controller.
4. **Policies & Gates:** Memvalidasi hak kepemilikan data (*resource ownership*) pada setiap mutasi dan akses berkas untuk mengeliminasi celah IDOR.
5. **Domain Services:** Digunakan secara terukur hanya untuk proses bisnis kompleks dan integrasi eksternal:
   * `GoogleDriveService`: Abstraksi pemanggilan Google API, token JWT, upload chunked, dan retry backoff.
   * `DocumentVersionService`: Manajemen transaksi upload, kalkulasi hash SHA-256 lokal, dan pembentukan versi baru permanen.
   * `DocumentReviewService`: Transisi status evaluasi dokumen, penanganan revokasi persetujuan, dan pengiriman notifikasi internal.
   * `StudentAttendanceService`: Pembukaan sesi, pre-fill anggota kelas, dan penutupan manual sesi absensi.
   * `AttendanceCorrectionService`: Orkestrasi koreksi data kehadiran oleh Admin dengan verifikasi justifikasi.
6. **Database Transactions:** Membungkus transaksi multi-tabel (`DB::beginTransaction`) untuk menjamin konsistensi ACID.

---

## 12. DATABASE ARCHITECTURE (MYSQL)

Struktur tabel relasional minimum yang ditetapkan untuk mendukung seluruh fitur:

### 12.1 Matriks Entitas Basis Data

| Nama Tabel | Peruntukan & Deskripsi | Kunci Utama (PK - ADR 15) | Kunci Asing (FK) Kritis | Unique Constraints |
| :--- | :--- | :---: | :--- | :--- |
| `users` | Akun autentikasi pengguna | `id` (BIGINT UNSIGNED AUTO_INCREMENT) | - | `username`, `email` |
| `teachers` | Data induk tenaga pendidik | `id` (BIGINT UNSIGNED AUTO_INCREMENT) | `user_id` $\rightarrow$ `users(id)` | `user_id`, `nip` |
| `academic_years` | Periode kalender akademik | `id` (BIGINT UNSIGNED AUTO_INCREMENT) | - | `(name, semester)` |
| `grade_levels` | Master tingkatan (7, 8, 9) | `id` (BIGINT UNSIGNED AUTO_INCREMENT) | - | `name`, `level_order` |
| `classes` | Rombel per tahun ajaran | `id` (BIGINT UNSIGNED AUTO_INCREMENT) | `academic_year_id`, `grade_level_id`, `homeroom_teacher_id` | `(academic_year_id, name)` |
| `students` | Data induk siswa sekolah | `id` (BIGINT UNSIGNED AUTO_INCREMENT) | `user_id` (opsional) | `nis`, `nisn` |
| `class_enrollments`| Penempatan siswa di rombel | `id` (BIGINT UNSIGNED AUTO_INCREMENT) | `academic_year_id`, `class_id`, `student_id` | `(academic_year_id, student_id)` |
| `subjects` | Master kurikulum mata pelajaran | `id` (BIGINT UNSIGNED AUTO_INCREMENT) | - | `code` |
| `teaching_assignments`| Pivot penugasan mengajar | `id` (BIGINT UNSIGNED AUTO_INCREMENT) | `academic_year_id`, `teacher_id`, `subject_id`, `class_id` | `(academic_year_id, class_id, subject_id, teacher_id)` |
| `schedules` | Jadwal mingguan penugasan | `id` (BIGINT UNSIGNED AUTO_INCREMENT) | `teaching_assignment_id` $\rightarrow$ `teaching_assignments(id)` | - |
| `documents` | Identitas induk berkas ajar | `id` (BIGINT UNSIGNED AUTO_INCREMENT) | `teacher_id`, `academic_year_id`, `subject_id`, `class_id`, `current_version_id` | - |
| `document_versions`| Snapshot berkas fisik Drive | `id` (BIGINT UNSIGNED AUTO_INCREMENT) | `document_id`, `uploaded_by_user_id` | `(document_id, version_number)` |
| `document_reviews` | Keputusan evaluasi pimpinan | `id` (BIGINT UNSIGNED AUTO_INCREMENT) | `document_version_id`, `reviewer_user_id` | - |
| `document_comments`| Catatan diskusi review | `id` (BIGINT UNSIGNED AUTO_INCREMENT) | `document_version_id`, `user_id` | - |
| ~~`teacher_attendances`~~| ~~DIHAPUS TOTAL (ADDENDUM §20 & §26)~~ | - | - | - |
| `student_school_attendances`| Absensi masuk/pulang siswa (barcode scanner) | `id` (BIGINT UNSIGNED AUTO_INCREMENT) | `student_id`, `check_in_by_teacher_id`, `check_out_by_teacher_id`, `corrected_by_user_id` | `(student_id, attendance_date)` |
| `teacher_duty_schedules`| Jadwal piket guru (ADDENDUM) | `id` (BIGINT UNSIGNED AUTO_INCREMENT) | `teacher_id`, `created_by_user_id` | `(schedule_date, teacher_id)` |
| `student_attendance_sessions`| Sesi pertemuan kelas mapel | `id` (BIGINT UNSIGNED AUTO_INCREMENT) | `teaching_assignment_id`, `opened_by_teacher_id` | `(teaching_assignment_id, session_date, meeting_order)` |
| `student_attendance_records`| Status kehadiran siswa per sesi| `id` (BIGINT UNSIGNED AUTO_INCREMENT) | `session_id`, `student_id`, `corrected_by_user_id` | `(session_id, student_id)` |
| `settings` | Pengaturan dinamis sekolah (profil, jam operasional masuk/pulang) | `id` (BIGINT UNSIGNED AUTO_INCREMENT) | - | `key` |
| `audit_logs` | Jejak rekam audit sistem | `id` (BIGINT UNSIGNED AUTO_INCREMENT) | `user_id` | - |

---

## 13. DATABASE INTEGRITY & CONSTRAINTS

Integritas sistem ditegakkan secara ketat pada level basis data MySQL (Engine InnoDB):
1. **Primary Key Standard (ADR 15):** Menggunakan `BIGINT UNSIGNED AUTO_INCREMENT` untuk seluruh tabel guna menjamin efisiensi join relasional dan performa indexing B-Tree maksimal di MySQL. Keamanan identitas ditegakkan mutlak melalui validasi otorisasi Laravel Policy server-side, bukan bergantung pada pengacakan ID (security through obscurity).
2. **Integritas Tahun Ajaran Aktif Tunggal:**  
   Menjamin secara absolut hanya ada tepat 1 tahun ajaran yang aktif (`is_active = TRUE`) dalam sistem. Pada MySQL 8.0+, integritas ditegakkan melalui kombinasi aturan bisnis di service layer yang dibungkus transaksi database atomik (menonaktifkan record lain sebelum mengaktifkan record baru), serta dapat diperkuat dengan constraint/unique index yang kompatibel dengan MySQL.
3. **Foreign Key Integrity Rules:**
   * Seluruh relasi data induk (`teachers`, `students`, `academic_years`, `subjects`) menggunakan aturan **`ON DELETE RESTRICT`** untuk mencegah terhapusnya entitas yang memiliki data transaksi historis.
   * Entitas subordinat fisik berkas (`document_versions`, `document_reviews`, `document_comments`) menggunakan **`ON DELETE CASCADE`** terhadap record induknya.
   * Penunjukan wali kelas menggunakan **`ON DELETE SET NULL`**.
4. **Check Constraints (Didukung pada MySQL 8.0+):** Membatasi nilai domain pada kolom:
   * `users.role IN ('admin', 'teacher', 'supervisor')`
   * `teachers.gender IN ('L', 'P')`
   * `documents.category IN ('RPP', 'ATP', 'PROTA', 'PROSEM', 'LAINNYA')`
   * `documents.status IN ('DRAFT', 'DIKIRIM', 'DIREVIEW', 'PERLU_REVISI', 'DISETUJUI')`
   * ~~`teacher_attendances.status IN ('HADIR', 'TERLAMBAT', 'IZIN', 'SAKIT', 'DINAS_LUAR', 'ALPA')`~~ — DIHAPUS (ADDENDUM)
   * `student_school_attendances.scan_mode_in IN ('MASUK')` — ADDENDUM
   * `student_school_attendances.scan_mode_out IN ('PULANG')` — ADDENDUM
   * `student_attendance_sessions.status IN ('OPEN', 'CLOSED')`
   * `student_attendance_records.status IN ('HADIR', 'IZIN', 'SAKIT', 'ALPA')`
   * `schedules.day_of_week BETWEEN 1 AND 7` dan `end_time > start_time`.

---

## 14. GOOGLE DRIVE ARCHITECTURE (STANDARD ACCOUNT MODEL)

Sesuai keputusan **ADR 06**, MVP Portal Sekolah menggunakan **Standard Google Drive Account** resmi institusi sekolah:
* **MySQL = Metadata & Single Source of Truth** (relasi, otorisasi, status, hash integritas).
* **Google Drive = Binary Storage Eksternal** (penyimpan objek fisik berkas).

### Karakteristik Akses Cloud:
1. **Model Folder Sharing Standard Account:** Akun Google resmi sekolah membuat folder induk repositori di My Drive miliknya, lalu membagikan (*share*) folder tersebut dengan hak akses *Editor* kepada email Service Account aplikasi.
2. **Private Storage Penuh:** Seluruh folder dan berkas berstatus privat tanpa akses publik langsung (*zero public web access*). Opsi *"Anyone with the link can view"* dilarang keras.
3. **Server-Side Credentials:** Browser pengguna tidak pernah menerima access token, API key, atau berkas rahasia Google. Kredensial production menggunakan akun resmi sekolah (bukan akun pribadi pengembang).
4. **Hierarki Folder Deterministik:**
   ```text
   Portal_Sekolah/
   └── [Tahun_Ajaran]/
       └── [Identitas_Guru]/
           └── [Kategori_Dokumen]/
               └── [Berkas Fisik v1, v2, ...]
   ```
5. **Pola Penamaan Berkas Stored:**  
   `[Kategori]_[NamaMapel]_[Kelas]_[NIP]_v[NomorVersi]_[Timestamp].[ext]`  
   Nama asli pengunggahan guru tetap dipertahankan pada kolom `document_versions.original_filename`.

---

## 15. DOCUMENT STORAGE & CONSISTENCY FLOW

Proses pengunggahan menerapkan pola **Saga Kompensasi Sederhana** untuk menjamin konsistensi data antara cloud dan basis data:

```text
Pengguna Unggah Berkas
          ↓
Validasi Server-Side (Ekstensi, MIME, Magic Bytes, Ukuran Maks 10 MB - ADR 07)
          ↓
Simpan Sementara di Local Storage (/tmp)
          ↓
Hitung Nilai Hash SHA-256 Berkas Lokal
          ↓
Unggah Biner ke Google Drive API (Standard Account - ADR 06)
          ↓
   ┌──────┴──────────────────────────┐
(Gagal)                           (Sukses)
   │                                 │
Batalkan & Hapus /tmp         Terima gdrive_file_id
                                     │
                              Buka DB Transaction
                              - Insert documents / versions
                              - Insert audit_logs
                                     │
                              ┌──────┴───────┐
                          (Gagal)         (Sukses)
                              │              │
                         Rollback DB    Commit DB
                              │              │
                         AKSI KOMPENSASI:    Hapus file /tmp
                         Hapus file di Drive │
                         (Jika gagal ->      Selesai (HTTP 200)
                          tandai orphan)
```

### Penanganan Kegagalan & Rekonsiliasi:
* **Kompensasi Otomatis:** Jika transaksi MySQL gagal pasca-upload ke Google Drive, sistem memicu penghapusan berkas di cloud agar tidak menjadi sampah.
* **Orphan Quarantine:** Berkas di Google Drive yang tidak tercatat di database dan berusia $> 24$ jam dipindahkan ke folder `_Orphan_Quarantine/` melalui scheduled command (`php artisan drive:detect-orphans`). Sistem tidak melakukan *aggressive hard delete* seketika.
* **Missing Files Alert:** Jika berkas di Drive terhapus tidak sengaja (HTTP 404), sistem menangkap exception secara elegan, mencatat insiden ke `audit_logs`, dan menyediakan alur recovery/re-upload administratif via panel Admin.

---

## 16. FILE SECURITY & VALIDATION

Aplikasi memperlakukan seluruh berkas unggahan sebagai *Untrusted Input*:
1. **Validasi Tiga Lapis (Server-Side):**
   * Whitelist Ekstensi: `.pdf`, `.docx`, `.doc`, `.xlsx`, `.xls`.
   * Pengecekan HTTP Client MIME Type.
   * Pemeriksaan Tanda Tangan Biner (*Magic Bytes*) via ekstensi PHP `finfo` (mencegah file skrip eksekutabel disamarkan).
2. **Batasan Ukuran Berkas (ADR 07):** Maksimal **10 MB per file**. Divalidasi ketat pada Form Request server-side.
3. **Peniadaan ClamAV pada MVP (ADR 13):** Sistem tidak memasang daemon ClamAV lokal guna menjaga performa dan efisiensi memori RAM server sekolah. Keamanan berkas ditegakkan melalui validasi ekstensi/MIME/magic-bytes, penolakan eksekusi lokal, dan isolasi penyimpanan cloud.
4. **Penyimpanan di Luar Web Root:** Berkas tidak pernah disimpan di folder publik aplikasi (`public/`) sehingga terhindar dari eksekusi skrip langsung (*Webshell Execution*).
5. **Sanitasi String Nama Berkas:** Karakter berbahaya (`/`, `\`, `..`, `:`, `*`, `%`) dihapus untuk mengeliminasi serangan *Path Traversal*.
6. **SHA-256 Integrity Verification:** Nilai hash biner dihitung oleh Laravel dan disimpan pada `document_versions.file_hash_sha256` sebagai bukti orisinalitas berkas (*Single Source of Truth* integritas aplikasi).

---

## 17. FILE VIEWING & STREAMING PROXY

Pengguna mengakses dokumen melalui **Server-Side Stream Proxy Pattern**:

```text
Browser Klien ──(Request Preview / Download)──► Laravel Controller
                                                      │
                                            [Evaluasi Policy RBAC]
                                                      │ (Lolos)
                                            Google Drive API
                                            (Stream Buffer)
                                                      │
                                            Response HTTP Streamed
                                            (Content-Disposition: inline/attachment)
                                                      │
Browser Merender PDF Native ◄─────────────────────────┘
```

### Karakteristik & Efisiensi:
* **Kerahasiaan URL Cloud:** Browser klien tidak pernah mengetahui URL fisik Google Drive.
* **Anti-Bypass:** Tautan tidak dapat diakses di tab incognito atau perangkat lain tanpa session login terotorisasi.
* **Chunked Stream Output:** Laravel mengalirkan data bertahap menggunakan buffer memory kecil (8 KB - 64 KB per chunk via `StreamedResponse`), mencegah *PHP Memory Exhaustion*.
* **Dukungan HTTP Range Request:** Mendukung header `Range: bytes=...` (HTTP 206 Partial Content) untuk pemuatan cepat halaman dokumen PDF besar di peramban.

---

## 18. DOCUMENT VERSIONING & PERMANENT RETENTION

* **Prinsip Immutability:** Berkas versi terdahulu ($v1, v2$) tidak pernah ditimpa saat revisi diunggah.
* **Struktur Pohon Versi:**
  ```text
  Dokumen Induk (ID: 10, "RPP Aljabar")
    ├── Versi 1 (v1, gdrive_file_id: "0Bxx1", status: PERLU_REVISI)
    ├── Versi 2 (v2, gdrive_file_id: "0Bxx2", status: PERLU_REVISI)
    └── Versi 3 (v3, gdrive_file_id: "0Bxx3", status: DISETUJUI, Versi Aktif)
  ```
* **Audit Rekam Jejak:** Setiap versi memiliki catatan pengunggah, tanggal, dan riwayat ulasan tersendiri.
* **Retensi Permanen (ADR 08):** Seluruh versi fisik dipertahankan secara permanen di Google Drive tanpa ada proses penghapusan otomatis (*no auto-purge*), menjamin ketersediaan data historis untuk keperluan audit akreditasi sekolah.

---

## 19. AUTHORIZATION & ANTI-IDOR ENFORCEMENT

Otorisasi ditegakkan di level server melalui integrasi **Laravel Middleware** dan **Laravel Policies**:
* **Role Prefix Middleware:** Memisahkan hak akses antarmuka (`role:admin`, `role:teacher`, `role:supervisor`). Pelanggaran prefix menghasilkan **HTTP 403 Forbidden**.
* **Resource Ownership Policy:**
  * Guru hanya berhak melihat, mengunggah, atau mengunduh dokumen miliknya sendiri (`document.teacher_id === auth.teacher.id`).
  * Guru hanya berhak membuka sesi absensi untuk kelas/mapel yang ada di `teaching_assignments` aktif miliknya.
  * Supervisor berhak memeriksa dokumen seluruh guru di sekolah yang diajukan ke tahap review (ADR 09).
  * Admin berhak mengakses data administratif sesuai wewenangnya.
* **IDOR Protection:** Manipulasi ID numerik pada parameter URL rute (misal: `/documents/versions/999/download`) dideteksi oleh Policy dan langsung diblokir seketika tanpa melakukan pemanggilan ke basis data hilir atau Google Drive API.

---

## 20. SECURITY BASELINE & DEFENSE IN DEPTH

Sistem mengadopsi standar verifikasi **OWASP ASVS v4.0** dan awareness terhadap **OWASP Top 10 (2021)**:
1. **Prinsip Least Privilege:** Hak akses pengguna, database, dan kredensial cloud dibatasi seminimal mungkin.
2. **Pertahanan Berlapis (*Defense in Depth*):** Validasi di form request, pengecekan policy di controller, constraint di basis data MySQL, dan isolasi file di cloud storage.
3. **Pencegahan Injeksi SQL:** 100% interaksi database menggunakan Eloquent ORM dan PDO Prepared Statements dengan parameter binding.
4. **Pencegahan Cross-Site Scripting (XSS):** Seluruh output antarmuka menggunakan auto-escaping Blade `{{ $variable }}`. Larangan keras penggunaan tag raw `{!! !!}` untuk input pengguna.
5. **Pencegahan Cross-Site Request Forgery (CSRF):** Seluruh formulir mutasi (POST/PUT/DELETE) wajib memuat token `@csrf`.

---

## 21. AUTHENTICATION SECURITY

* **Algoritma Hashing:** Menggunakan **Argon2id** (atau Bcrypt dengan minimum cost factor 12). Kata sandi mentah dilarang disimpan atau dicatat di log.
* **Brute-Force Rate Limiting:** Maksimal **5 percobaan gagal per 60 detik** per username/IP. Jika terlampaui, request diblokir dengan respons HTTP `429 Too Many Requests`.
* **Siklus Hidup Sesi:**
  * Login sukses: Menjalankan `$request->session()->regenerate()` (mitigasi *Session Fixation*).
  * Logout: Menjalankan `$request->session()->invalidate()` dan regenerasi token CSRF.
* **Verifikasi Status Akun:** Global middleware `EnsureUserIsActive` memeriksa status `users.is_active`. Akun nonaktif langsung diputus sesinya.
* **Pemulihan Kata Sandi Terpusat (ADR 10):** Guru yang lupa kata sandi melapor ke Staf Tata Usaha. Admin/TU melakukan reset manual melalui panel Admin yang menghasilkan kata sandi sementara baru sesuai baseline keamanan. Seluruh proses reset tercatat di audit log (`USER_PASSWORD_RESET`). Tidak memerlukan mail server SMTP pada MVP.

---

## 22. SESSION & WEB SECURITY

* **Spesifikasi Cookie Sesi:**
  * Nama cookie dikustomisasi (misal: `portal_sekolah_session`).
  * `HttpOnly = TRUE`: Mencegah akses cookie oleh skrip klien/XSS.
  * `SameSite = Lax`: Mencegah pengiriman cookie lintas situs pada mutasi CSRF.
  * `Secure = TRUE`: Wajib di lingkungan produksi ber-HTTPS.
* **Enkripsi Transit Mutlak:** Wajib menggunakan HTTPS (TLS 1.2 / TLS 1.3) pada lingkungan produksi. Pengalihan otomatis HTTP port 80 ke HTTPS port 443.
* **Web Security Headers:**
  * `X-Content-Type-Options: nosniff`
  * `X-Frame-Options: SAMEORIGIN`
  * `Referrer-Policy: strict-origin-when-cross-origin`
  * `Permissions-Policy: camera=(self), microphone=(), geolocation=()` — *ADR 20 `[FINAL — Keputusan B2]`: camera=(self) disahkan agar scanner barcode (kamera HP) berfungsi; tanpa whitelist device; camera=() ditolak. Akses kamera tetap dikontrol permission browser + otorisasi server-side Guru Piket.*
  * `Strict-Transport-Security: max-age=31536000; includeSubDomains` (diaktifkan setelah HTTPS produksi stabil).
* **Inactivity Timeout (ADR 11):** Sesi login otomatis berakhir setelah **120 menit tanpa aktivitas**. Pengguna wajib login ulang setelah sesi expired.

---

## 23. SECRETS, CREDENTIALS & CONFIGURATION MANAGEMENT

1. **Git Cleanliness:** File `.env`, berkas kredensial autentikasi Google, sertifikat TLS, dan berkas kunci privat dilarang keras masuk ke version control Git (wajib terdaftar di `.gitignore`).
2. **Kunci Enkripsi Aplikasi:** `APP_KEY` digenerate secara kriptografis (32 karakter acak) dan wajib unik per lingkungan server.
3. **Penyimpanan Kredensial:** Kredensial autentikasi Google disimpan di luar direktori web root dan dilindungi hak akses sistem operasi yang hanya mengizinkan akun service aplikasi membacanya.
4. **Pemisahan Lingkungan:** Kredensial sandbox/lokal untuk pengembangan dipisahkan secara mutlak dari kredensial resmi institusi sekolah pada produksi. Penggantian kredensial produksi dilakukan saat aplikasi rampung dan siap dideploy.

---

## 24. AUDIT LOGGING & ACCOUNTABILITY

Sistem menyediakan jejak rekam audit komprehensif melalui tabel `audit_logs`:
* **Hak Akses Eksklusif Admin TU (ADR 17):** Halaman audit log umum hanya dapat diakses oleh Admin/Staff TU. Supervisor tidak diberikan halaman audit log umum, namun tetap dapat melihat histori evaluasi dan komentar pada dokumen yang direview-nya.
* **Peristiwa Wajib Audit:**
  * Autentikasi: `AUTH_LOGIN_SUCCESS`, `AUTH_LOGIN_FAILED`, `AUTH_LOGOUT`.
  * Manajemen Akun: `USER_CREATED`, `USER_STATUS_TOGGLE`, `USER_PASSWORD_RESET`.
  * Repositori Dokumen: `DOC_UPLOAD`, `DOC_SUBMIT`, `DOC_NEW_VERSION`, `DOC_REVIEW_APPROVED`, `DOC_REVIEW_REVISED`, `DOC_APPROVAL_REVOKED` (ADR 05).
  * ~~Presensi Guru: `ATT_TEACHER_SELF`, `ATT_TEACHER_ALPA_MANUAL` (ADR 01), `ATT_TEACHER_CORRECT`~~ — **DIHAPUS TOTAL (ADDENDUM §20 & §26)**.
  * Absensi Masuk/Pulang Siswa: `SCHOOL_ATT_SCAN` (scan masuk/pulang oleh Guru Piket), `SCHOOL_ATT_CORRECT` (koreksi Admin/TU — wajib alasan).
  * Jadwal Piket Guru: `DUTY_SCHEDULE_CREATE`, `DUTY_SCHEDULE_UPDATE` (guru lama → guru baru / ganti tanggal), `DUTY_SCHEDULE_DELETE` — seluruhnya wajib mencatat `actor`, tanggal jadwal, guru lama, guru baru, `timestamp` (ADDENDUM §17).
  * Absensi Siswa Mapel: `ATT_SESSION_OPEN`, `ATT_SESSION_CLOSED`, `ATT_STUDENT_CORRECT` (wajib alasan).
  * Pengaturan Sistem: `ACADEMIC_YEAR_CHANGE`, `TEACHING_ASSIGN_EDIT`.
* **Prinsip Immutability & Sanitasi:** Tabel audit log bersifat *append-only*. Kolom snapshot JSON (`old_values`, `new_values`) wajib melewati mekanisme filter pembersih untuk menyaring kata sandi, token, dan rahasia sistem.

---

## 25. BACKUP & DISASTER RECOVERY STRATEGY

### 25.1 Target Pemulihan Bencana (RPO & RTO — ADR 12)
Sistem menetapkan target kelangsungan operasional (*Business Continuity*):
* **RPO (Recovery Point Objective) = 24 Jam:** Kehilangan data maksimum yang ditoleransi adalah data transaksi sejak jadwal pencadangan harian terakhir.
* **RTO (Recovery Time Objective) = 8 Jam:** Target waktu pemulihan sistem hingga dapat beroperasi normal kembali setelah insiden adalah maksimal 8 jam kerja staf IT.

### 25.2 Pencadangan MySQL & Retensi Semester (ADR 16)
* **Otomasi Terjadwal:** Pencadangan dump basis data (`mysqldump` / MySQL logical backup) dijalankan rutin setiap malam di luar jam sekolah via otomasi scheduler lingkungan produksi.
* **Kebijakan Retensi:** Cadangan harian disimpan 7 hari; cadangan mingguan disimpan 4 minggu.
* **Retensi Permanen Cadangan Akhir Semester (ADR 16):** Snapshot cadangan basis data pada akhir semester kalender akademik disimpan secara permanen (*permanent offline retention*) untuk kebutuhan arsip historis akreditasi sekolah.
* **Isolasi Berkas Backup:** Disimpan di direktori terisolasi dengan hak akses terbatas dan disalin ke media penyimpanan terpisah (*offsite/secondary backup*).

### 25.3 Cadangan & Pemulihan Google Drive
* Berkas biner di Google Drive terlindungi oleh redundansi storage Google Cloud.
* Fitur Trash Google Drive dapat dimanfaatkan untuk pemulihan darurat jangka pendek, namun bukan pengganti backup resmi.
* Perintah rekonsiliasi `php artisan drive:reconcile` dijalankan pasca pemulihan snapshot database untuk mencocokkan referensi ID berkas dengan objek fisik di cloud.

---

## 26. DEPENDENCY, SERVER & PLATFORM SECURITY

1. **Komit Lockfiles:** Berkas `composer.lock` dan `package-lock.json` wajib di-commit ke repositori untuk menjamin konsistensi versi pustaka pihak ketiga.
2. **Audit Dependensi Berkala:** Rutin mengeksekusi `composer audit` dan `npm audit` untuk mendeteksi kerentanan pustaka.
3. **Pengerasan Server (Platform-Agnostic):**
   * Pembaruan keamanan (*security patches*) berkala pada OS (Linux/Windows Server), runtime PHP, engine MySQL 8.0+, dan web server.
   * Port MySQL (`3306`) tertutup rapat dari akses internet publik.
   * Akun database aplikasi beroperasi dengan hak akses minimum (*non-root*).
   * Direktori source code aplikasi berstatus *read-only* bagi service web server; direktori kerja sementara dilarang memiliki izin eksekusi skrip (*noexec* jika didukung OS).

---

## 27. APPLICATION LOGGING & ERROR MASKING

1. **Konfigurasi Produksi Anti-Bocor:**  
   Pada lingkungan produksi, konfigurasi wajib menyetel:  
   `APP_DEBUG=false`
2. **Error Masking Ramah Pengguna:** Jika terjadi galat internal sistem (HTTP 500), pengguna disajikan tampilan halaman galat generik yang bersih. Sistem dilarang keras membocorkan *stack trace*, query SQL mentah, path folder server, atau variabel lingkungan.
3. **Logging Internal Terstruktur:** Rincian teknis kegagalan dicatat secara internal ke berkas log harian dengan kebijakan rotasi harian (*daily log channel*) berretensi 14 hingga 30 hari.

---

## 28. OPERATIONAL MONITORING & HEALTH CHECKS

Pemantauan operasional dirancang esensial, handal, dan tanpa *over-engineering*:
1. **Endpoint Health Check Internal:** Endpoint tertutup/ber-token (`/healthz` atau sejenisnya) untuk memeriksa:
   * Keterjangkauan koneksi basis data MySQL (`SELECT 1`).
   * Ketersediaan kapasitas ruang penyimpanan disk server lokal.
   * Keterjangkauan konektivitas integrasi Google Drive API.
   * Mengembalikan respons JSON ringkas (`{"status": "ok", "db": "healthy", "storage": "healthy"}`).
2. **Pemantauan Kapasitas Disk:** Peringatan otomatis dicatat jika kapasitas penyimpanan server melebihi 85% untuk mencegah kegagalan proses baca/tulis aplikasi.

---

## 29. SECURITY TESTING PROTOCOL

Rangkaian pengujian keamanan yang wajib dieksekusi sebelum rilis produksi:
1. **Uji Penetrasi Autentikasi:** Verifikasi pemblokiran rate limit login (HTTP 429 setelah 5 kali gagal) dan regenerasi sesi.
2. **Uji Otorisasi & Anti-IDOR:**
   * Guru A mencoba mengunduh dokumen milik Guru B $\rightarrow$ Wajib HTTP 403 Forbidden.
   * Guru membuka sesi absensi di luar penugasan mengajar $\rightarrow$ Wajib HTTP 403 Forbidden.
   * Manipulasi status dokumen di luar wewenang role $\rightarrow$ Wajib diblokir Policy.
3. **Uji Keamanan Scanner Barcode & Jadwal Piket (WAJIB — ADDENDUM §23):**
   * Guru **bukan** piket mengakses URL scanner secara langsung $\rightarrow$ **403 / unauthorized** (penolakan server, bukan sekadar sembunyikan tombol).
   * Guru piket mengakses scanner $\rightarrow$ diizinkan.
   * Guru piket dicabut jadwalnya **saat halaman scanner masih terbuka**, lalu melakukan scan berikutnya $\rightarrow$ request **ditolak** server.
   * Guru mencoba **memalsukan tanggal** pada request $\rightarrow$ server tetap memakai tanggal server.
   * Guru mencoba **memalsukan user/operator** pada payload request $\rightarrow$ ditolak; operator diambil dari sesi login terautentikasi.
   * Guru mencoba **mengubah mode MASUK↔PULANG** di luar mekanisme server yang valid $\rightarrow$ ditolak.
   * **Duplikat MASUK** $\rightarrow$ tidak membuat record kedua.
   * **Duplikat PULANG** $\rightarrow$ tidak membuat/menimpa record kedua.
   * **PULANG tanpa catatan MASUK** (`[FINAL — KEPUTUSAN A1]`) $\rightarrow$ **DITOLAK** server; tidak ada check-in palsu/auto-create; respons mengarahkan ke koreksi Admin/TU.
   * **Header `Permissions-Policy: camera=(self)`** (ADR 20 — Keputusan B2) $\rightarrow$ scanner kamera HP dapat meminta izin browser; `camera=()` tidak digunakan; whitelist device tidak diterapkan.
   * **Perubahan jadwal piket** $\rightarrow$ audit log tercatat.
   * **Perubahan jadwal piket** $\rightarrow$ histori absensi (operator, waktu, siswa) **tidak berubah**.

4. **Uji Validasi Unggahan Berkas:**
   * Pengujian unggah file skrip eksekutabel disamarkan (`shell.php.pdf`) $\rightarrow$ Wajib ditolak validasi Magic Bytes (HTTP 422).
   * Pengujian unggahan melebihi batas ukuran 10 MB $\rightarrow$ Wajib ditolak dengan pesan kesalahan bersih.
5. **Uji Ketahanan Terhadap Injeksi & CSRF:**
   * Pengujian form submission tanpa token CSRF $\rightarrow$ Wajib HTTP 419 Page Expired.
   * Uji injeksi karakter script XSS pada formulir teks $\rightarrow$ Wajib ter-escape sempurna pada view Blade.
   * Uji parameter filter terhadap SQL Injection.

---

## 30. MATRIKS VERIFIKASI OWASP ASVS

Pemetaan standar verifikasi **OWASP ASVS v4.0 (Level 1 Baseline)** untuk aplikasi Portal Sekolah:

| Kode ASVS | Area Verifikasi | Kebutuhan Keamanan Spesifik | Metode Verifikasi | Status Perancangan |
| :--- | :--- | :--- | :--- | :---: |
| **V1** | **Architecture** | Pemisahan role tegas, otorisasi server-side terpusat | Review Blueprint Arsitektur (01-05) | **REQUIRED** |
| **V2** | **Authentication** | Hashing Argon2id/Bcrypt, brute-force rate limiting 5x/60s | Automated Feature Testing & Auth Suite | **REQUIRED / TO BE VERIFIED** |
| **V3** | **Session Mgmt** | Cookie HttpOnly, SameSite=Lax, Secure HTTPS, Session Regeneration, Timeout 120m (ADR 11) | Inspeksi Response Header HTTP | **REQUIRED / TO BE VERIFIED** |
| **V4** | **Access Control** | Penegakan Laravel Policy pada setiap mutasi data, proteksi IDOR, PK BIGINT UNSIGNED (ADR 15) | Test Suite Otorisasi Multi-Role | **REQUIRED / TO BE VERIFIED** |
| **V5** | **Input Validation**| Validasi Form Request ketat, sanitasi nama berkas, magic bytes check | Unit & Integration Testing | **REQUIRED / TO BE VERIFIED** |
| **V6** | **Cryptography** | Enkripsi transit HTTPS TLS 1.2+, hashing SHA-256 internal aplikasi | Audit Konfigurasi Web Server & Hash Test | **REQUIRED / TO BE VERIFIED** |
| **V7** | **Error Handling** | Error masking UI (`APP_DEBUG=false`), log teknis tersimpan internal | Pengujian Skenario Exception | **REQUIRED / TO BE VERIFIED** |
| **V8** | **Data Protection**| Private Google Drive (ADR 06), tidak ada file biner atau secret di tabel DB | Audit Skema MySQL & Drive API | **REQUIRED / TO BE VERIFIED** |
| **V10** | **File Upload** | Whitelist format dokumen, larangan eksekusi direktori upload, batas 10 MB (ADR 07) | Penetrasi Unggah File Berbahaya | **REQUIRED / TO BE VERIFIED** |
| **V11** | **Logging & Audit**| Pencatatan aksi login, mutasi dokumen, koreksi kehadiran di `audit_logs` (ADR 17) | Inspeksi Isi Tabel `audit_logs` | **REQUIRED / TO BE VERIFIED** |
| **V13** | **API & Integrasi**| Streaming proxy tertutup ke Google API, isolasi kredensial cloud | Validasi Pola Proxy Tanpa URL Publik | **REQUIRED / TO BE VERIFIED** |
| **V14** | **Config & Server** | Port database privat, firewall aktif, hak akses izin berkas OS terisolasi | Audit Konfigurasi Host & Environment | **REQUIRED / TO BE VERIFIED** |

---

## 31. PEMETAAN ANCAMAN OWASP TOP 10

| Kategori OWASP (2021) | Potensi Risiko pada Portal Sekolah | Kontrol Pertahanan yang Diterapkan |
| :--- | :--- | :--- |
| **A01: Broken Access Control** | Manipulasi parameter URL untuk mengintip dokumen guru lain atau mengedit absensi kelas lain. | Penegakan Laravel Policy pada setiap Controller; verifikasi kepemilikan dan relasi *teaching assignment* di level server; IDOR dicegah via policy checks. |
| **A02: Cryptographic Failures** | Penyadapan lalu lintas jaringan sekolah atau kebocoran kata sandi guru. | Enkripsi HTTPS mutlak; Hashing Argon2id/Bcrypt; tidak menyimpan data mentah rahasia di database. |
| **A03: Injection** | Serangan SQL Injection melalui input formulir pencarian atau filter laporan. | 100% menggunakan Eloquent ORM / Query Builder dengan parameter binding pada seluruh query database. |
| **A04: Insecure Design** | Alur status dokumen dimanipulasi sehingga guru meng-approve berkasnya sendiri. | *State machine* kaku dan deterministik; aturan transisi status dikunci berdasarkan role yang sah; pemisahan akun ganda (ADR 04). |
| **A05: Security Misconfiguration** | Mode `APP_DEBUG=true` tertinggal di server produksi sehingga mengekspos variabel rahasia. | Konfigurasi produksi wajib menyetel `APP_DEBUG=false`; pengerasan konfigurasi web server dan environment. |
| **A06: Vulnerable & Outdated Components**| Celah keamanan pada pustaka pihak ketiga Composer atau npm. | Pemeriksaan berkala via `composer audit` dan `npm audit`; penguncian versi pada berkas lockfile. |
| **A07: Identification & Auth Failures** | Serangan tebak kata sandi massal (brute force) terhadap akun pengguna. | Rate limiting login (5x per 60 detik); regenerasi sesi login; invalidasi total saat logout; session timeout 120m (ADR 11). |
| **A08: Software & Data Integrity Failures**| Berkas dokumen di cloud storage termodifikasi atau rusak tanpa terdeteksi. | Perhitungan checksum SHA-256 internal oleh aplikasi yang disimpan di MySQL sebagai *Single Source of Truth*. |
| **A09: Security Logging & Monitoring Failures**| Modifikasi data akademik penting tidak terdeteksi oleh pimpinan sekolah. | Pencatatan otomatis ke tabel `audit_logs` yang bersifat append-only; hak akses baca eksklusif Admin/TU (ADR 17). |
| **A10: Server-Side Request Forgery (SSRF)** | Eksploitasi panggilan jaringan server ke endpoint eksternal berbahaya. | Aplikasi tidak menerima input URL tujuan dari pengguna; panggilan API murni dibatasi ke endpoint resmi Google Drive API. |

---

## 32. NON-FUNCTIONAL REQUIREMENTS (NFR)

1. **Security:** Mengikuti baseline OWASP ASVS v4.0 L1, otorisasi server-side mutlak, proteksi IDOR, session timeout 120 menit (ADR 11), dan sanitasi berkas multi-lapis.
2. **Simplicity:** Antarmuka Blade responsif, mudah dipahami tenaga pendidik dengan berbagai tingkat literasi digital, bebas dari kerumitan arsitektur terdistribusi.
3. **Maintainability:** Standar penulisan kode terstruktur Laravel, pemisahan modul yang jelas, dan kepatuhan PSR-12.
4. **Reliability & Data Integrity:** Menjamin tidak ada kehilangan data transaksi (*zero data loss*), konsistensi transaksional ACID, retensi dokumen permanen (ADR 08), serta mekanisme kompensasi kegagalan upload.
5. **Realistic Scalability:** Mampu berkembang dinamis dari skala 1 rombel baseline hingga puluhan rombel per angkatan melalui efisiensi model data relasional.
6. **Auditability:** Seluruh mutasi dokumen (termasuk revokasi persetujuan - ADR 05), scan & koreksi absensi siswa masuk/pulang, perubahan jadwal guru piket, dan data master terekam permanen pada tabel `audit_logs`.
7. **Performance:** Memanfaatkan indeks database MySQL (InnoDB), primary key BIGINT UNSIGNED AUTO_INCREMENT (ADR 15), eager loading Eloquent, dan streaming chunked buffer untuk pengunduhan berkas. Validasi performa aktual Google Drive ditentukan melalui pengujian beban nyata pada infrastruktur sekolah `[TO BE VERIFIED]`.

---

## 33. SCALABILITY STRATEGY

Sistem mendukung pertumbuhan skala institusi sekolah dari:
$$\text{Baseline: Tingkat 7, Kelas 7A, 27 Siswa} \longrightarrow \text{Target: Puluhan Rombel (7A-7C, 8A-8C, 9A-9C)}$$

Pilar Skalabilitas Sederhana:
1. **Normalisasi Data Dinamis:** Pemisahan tabel `grade_levels`, `classes`, dan `class_enrollments` memungkinkan penambahan ratusan rombel dan ribuan siswa baru tanpa modifikasi skema tabel atau kode sumber.
2. **Optimasi Database:** Seluruh foreign key dan kolom pencarian rutin (`academic_year_id`, `teacher_id`, `attendance_date`, `status`) dilengkapi indeks performa tinggi.
3. **Pagination Bawaan:** Seluruh antarmuka daftar data mengimplementasikan paginasi server-side Laravel (`paginate(25)`), mencegah lonjakan konsumsi memori saat jumlah data bertambah besar.
4. **Penyimpanan Biner Eksternal:** Berkas dokumen fisik ditampung di Google Drive, sehingga kapasitas disk server aplikasi tetap ringan dan tidak terbebani pertumbuhan file biner.

---

## 34. REPORTING & DATA EXPORT

Sistem menyediakan fungsi rekapitulasi data administratif yang esensial:
1. **Laporan Absensi Masuk/Pulang Siswa:** Rekap harian/bulanan jumlah siswa masuk, belum masuk, dan sudah pulang, lengkap dengan identitas Guru Piket operator scan, dengan filter rentang tanggal dan kelas.
2. **Laporan Absensi Siswa:** Rekap persentase kehadiran siswa per rombel dan per mata pelajaran.
3. **Laporan Kepatuhan Dokumen:** Matriks status pemenuhan perangkat ajar seluruh guru (ADR 09) (daftar guru tuntas vs menunggak).
4. **Laporan Audit Log:** Penelusuran riwayat mutasi data dan kejadian administratif khusus oleh Admin TU (ADR 17).
5. **Format Ekspor:**
   * **MVP (MUST HAVE):** Ekspor file tabular **CSV** dan spreadsheet **Microsoft Excel (.xlsx)**.
   * **Persiapan Cetak Resmi (SHOULD HAVE):** Ekspor dokumen siap cetak berformat **PDF** lengkap dengan header kop surat dan kolom tanda tangan pimpinan `[SHOULD HAVE]`.

---

## 35. FINAL DECISION STATUS (INTEGRASI ADR 01 — ADR 20)

Seluruh 20 keputusan arsitektur dan kebijakan pada dokumen `06-decision-resolution.md` telah **disahkan oleh manusia (2026-09-27)**: ADR 01–03 `[OBSOLETE]` (ADDENDUM), ADR 04–17 `[FINAL]`, ADR 18–19 `[FINAL — ADDENDUM]`, ADR 20 `[FINAL — Keputusan B2]`, termasuk keputusan tambahan `[FINAL — KEPUTUSAN A1]`:

| Nomor ADR | Topik Keputusan | Pilihan Final Terpilih | Status Keputusan | Dokumen Rujukan |
| :--- | :--- | :--- | :---: | :--- |
| **ADR 01** | Mekanisme Penandaan Status ALPA Guru | **`[OBSOLETE]`** — Fitur presensi mandiri guru dihapus total (ADDENDUM §20 & §26). Tidak ada lagi penetapan ALPA guru. | **OBSOLETE** | `06-decision-resolution.md` — ADR 01 |
| **ADR 02** | Batas Toleransi Jam Masuk Guru | **`[OBSOLETE]`** — Tidak relevan; cutoff keterlambatan presensi guru dihapus bersama fiturnya (ADDENDUM §20 & §26). | **OBSOLETE** | `06-decision-resolution.md` — ADR 02 |
| **ADR 03** | Batasan Jaringan IP Presensi Guru | **`[OBSOLETE]`** — Tidak relevan; akses scanner Guru Piket dikontrol oleh `teacher_duty_schedules`, bukan whitelist IP (ADDENDUM §8, §12). | **OBSOLETE** | `06-decision-resolution.md` — ADR 03 |
| **ADR 04** | Akun Peran Ganda (Guru & Supervisor) | **B — Dua Akun Terpisah** (1 akun NIP untuk mengajar, 1 akun khusus untuk supervisi pimpinan) | **FINAL** | `06-decision-resolution.md` — ADR 04 |
| **ADR 05** | Otorisasi Pembatalan Dokumen DISETUJUI | **B — Dapat Direvoke oleh Pihak Berwenang** (Wajib berita acara/alasan, tercatat di audit log, versi lama aman) | **FINAL** | `06-decision-resolution.md` — ADR 05 |
| **ADR 06** | Model Akun Google Drive Sekolah | **B — Standard Google Drive Account** (Folder sharing privat via Service Account, tanpa Workspace Shared Drive) | **FINAL** | `06-decision-resolution.md` — ADR 06 |
| **ADR 07** | Batas Maksimal Ukuran Unggah Berkas | **A — 10 MB per berkas** (Validasi multi-lapis server-side, efisien untuk bandwidth sekolah) | **FINAL** | `06-decision-resolution.md` — ADR 07 |
| **ADR 08** | Kebijakan Retensi Berkas Versi Lama | **A — Semua Versi Disimpan Permanen** (Tidak ada auto-delete, histori fisik utuh untuk akreditasi) | **FINAL** | `06-decision-resolution.md` — ADR 08 |
| **ADR 09** | Ruang Lingkup Supervisi Dokumen | **A — Seluruh Guru dalam Satu Sekolah** (Supervisor memeriksa seluruh guru tanpa sekat rumpun) | **FINAL** | `06-decision-resolution.md` — ADR 09 |
| **ADR 10** | Mekanisme Reset Password Pengguna | **A — Reset Terpusat oleh Admin/TU** (Manual oleh TU ber-audit log, tanpa mail server SMTP untuk MVP) | **FINAL** | `06-decision-resolution.md` — ADR 10 |
| **ADR 11** | Batas Inactivity Timeout Sesi | **B — 120 Menit (2 Jam)** (Sesi expired setelah 120 menit tanpa aktivitas, ramah workstation bersama) | **FINAL** | `06-decision-resolution.md` — ADR 11 |
| **ADR 12** | Target Pemulihan Bencana (RPO & RTO) | **A — RPO 24 Jam & RTO 8 Jam** (Backup harian terjadwal, pemulihan maksimal 8 jam kerja) | **FINAL** | `06-decision-resolution.md` — ADR 12 |
| **ADR 13** | Integrasi Antivirus Engine (ClamAV) | **B — Tidak Menggunakan ClamAV untuk MVP** (Validasi 3 lapis server-side, hemat RAM server) | **FINAL** | `06-decision-resolution.md` — ADR 13 |
| **ADR 14** | Batas Waktu Auto-Lock Sesi Absensi Siswa| **B — Manual Close oleh Guru** (Guru menutup sesi manual, tanpa auto-lock scheduler pada MVP) | **FINAL** | `06-decision-resolution.md` — ADR 14 |
| **ADR 15** | Format Identifier Kunci Primer (PK) | **A — BIGINT / Identity** (Diterapkan sebagai `BIGINT UNSIGNED AUTO_INCREMENT` pada MySQL, proteksi IDOR via Policy) | **FINAL** | `06-decision-resolution.md` — ADR 15 |
| **ADR 16** | Kebijakan Cadangan Akhir Semester | **A — Permanent Retention** (Snapshot akhir semester disimpan permanen untuk arsip akreditasi) | **FINAL** | `06-decision-resolution.md` — ADR 16 |
| **ADR 17** | Hak Akses Audit Log Sistem | **B — Hanya Admin/Staff TU** (Supervisor tidak memiliki menu audit umum, menjaga fokus wewenang) | **FINAL** | `06-decision-resolution.md` — ADR 17 |
| **ADR 18** | Mode Scanner Absensi Masuk/Pulang | **Mode eksplisit `[MASUK]`/`[PULANG]` dipilih Guru Piket** — dilarang auto-detect dari urutan scan; **"Pulang tanpa Masuk" DITOLAK (`[FINAL — KEPUTUSAN A1]`, disahkan 2026-09-27)** — tanpa check-in palsu/auto-create; jalur resmi koreksi Admin/TU (ADDENDUM §1, §2, §5, §6) | **FINAL — ADDENDUM + A1** | `06-decision-resolution.md` — ADR 18 |
| **ADR 19** | Sumber Jadwal & Validasi Guru Piket | **`teacher_duty_schedules` di MySQL, divalidasi server-side pada setiap request**; perubahan jadwal langsung berlaku tanpa perubahan kode; data historis tidak berubah; seluruh mutasi terjadwal diaudit; tanpa WebSocket/Redis/Queue (ADDENDUM §9–§14) | **RESOLVED — ADDENDUM** | `06-decision-resolution.md` — ADR 19 |
| **ADR 20** | Kebijakan `Permissions-Policy` camera (Scanner Barcode) | **Keputusan B2: `camera=(self)`** disahkan (2026-09-27) — scanner kamera HP wajib berjalan; **tanpa whitelist device kompleks**; `camera=()` ditolak; akses kamera tetap dikontrol permission browser + otorisasi server-side Guru Piket | **FINAL — B2** | `06-decision-resolution.md` — ADR 20; `04-security-operations.md` Bab 5.3 & 21 |

---

## 36. PRODUCTION READINESS CHECKLIST

Daftar periksa verifikasi sebelum sistem diizinkan meluncur ke lingkungan produksi:

### A. Lapisan Aplikasi (Application Layer)
* [ ] Konfigurasi `APP_ENV=production` terpasang pada environment file.
* [ ] Konfigurasi `APP_DEBUG=false` terpasang pada environment file.
* [ ] Kunci enkripsi unik telah digenerate (`APP_KEY` valid dan unik).
* [ ] Seluruh password akun bawaan awal (*default seeder*) telah diganti dengan kata sandi baru yang kuat.
* [ ] Cache konfigurasi dan rute telah dioptimasi (`php artisan config:cache`, `php artisan route:cache`).
* [ ] Seluruh pengujian otorisasi Role & Policy telah berstatus lulus (*pass*).
* [ ] Audit dependensi (`composer audit` & `npm audit`) bersih dari celah keamanan berstatus critical.

### B. Lapisan Infrastruktur & Basis Data (Infrastructure Layer)
* [ ] Layanan HTTPS aktif dengan sertifikat SSL/TLS valid serta pengalihan otomatis HTTP ke HTTPS.
* [ ] Port basis data MySQL (`3306`) tertutup rapat dari internet publik.
* [ ] Akun koneksi database aplikasi menggunakan user berhak akses minimum (*non-root*).
* [ ] Skrip pencadangan database terjadwal aktif pada scheduler environment produksi (RPO 24h, RTO 8h - ADR 12).
* [ ] Uji coba pemulihan data (*restore testing*) dari berkas cadangan telah diverifikasi berhasil.
* [ ] Izin akses berkas pada direktori server telah disetel secara ketat sesuai permission sistem operasi.

### C. Lapisan Cloud Storage (Google Drive Layer)
* [ ] Google production credential menggunakan account/credential resmi sekolah berbasis Standard Google Drive Account (ADR 06).
* [ ] Credential production disimpan di luar web root dan dilindungi dengan permission filesystem/environment yang sesuai.
* [ ] Hak akses folder penyimpanan Google Drive dipastikan berstatus **Private (Zero Public Sharing)**.
* [ ] Alur unggah berkas (maksimal 10 MB - ADR 07), pratinjau streaming proxy, dan pengunduhan dokumen telah teruji dengan hak akses yang benar.

### D. Lapisan Operasional & Pemantauan (Operations Layer)
* [ ] Mode rotasi log aplikasi aktif dan direktori log memiliki izin tulis bagi service aplikasi.
* [ ] Endpoint pemeriksaan kesehatan sistem (*Health check*) berfungsi normal dan dapat diakses pihak berwenang.
* [ ] Prosedur tanggap darurat (*Incident Response Plan*) telah dipahami oleh tim teknis sekolah.

---

## 37. DEVELOPMENT BOUNDARY

> **BATASAN TAHAPAN (PHASE BOUNDARY):**  
> Dokumen ini berstatus **`APPROVED FOR DEVELOPMENT (FINAL — A1/B2/C1 disahkan 2026-09-27)`**.  
> Dokumen ini merupakan blueprint spesifikasi arsitektur final sebelum pengembangan dimulai.  
> Dokumen ini **BELUM MEMASUKI**:
> * Tahap Koding / Implementasi Source Code Aplikasi
> * Tahap Eksekusi Testing / QA Aktual
> * Tahap Deployment Server
> * Tahap Rilis Produksi

---

## 38. RENCANA STRATEGI PENGEMBANGAN PASCA-PRD

Rencana kerja tingkat tinggi pasca persetujuan blueprint konsolidasi:

* **Fase 1: Project Foundation & Authentication**  
  Inisialisasi project Laravel 13, setup Tailwind & Vite, konfigurasi database MySQL 8.0+ via Laragon (`BIGINT UNSIGNED AUTO_INCREMENT` - ADR 15), migrasi tabel user, modul login/logout aman ber-rate limit, pemulihan sandi manual TU (ADR 10), dan session timeout 120m (ADR 11).
* **Fase 2: Master Data & Dynamic Class Hierarchy**  
  Implementasi modul tahun ajaran, tingkat pendidikan, rombel dinamis, data induk guru, dan data induk siswa beserta enrollment (termasuk atribut `barcode_code` siswa), serta pengaturan profil sekolah dan periode jam masuk/pulang.
* **Fase 3: Teaching Assignment & Scheduling**  
  Pembangunan pivot penugasan mengajar guru dan modul jadwal pelajaran mingguan sebagai simpul otorisasi kelas.
* **Fase 4: Student School Attendance & Duty Schedule (ADDENDUM)**  
  Implementasi jadwal piket guru (`teacher_duty_schedules` CRUD + audit), scanner barcode kamera HP untuk absensi masuk/pulang siswa (`student_school_attendances`), mode eksplisit MASUK/PULANG, validasi server-side per request, penolakan PULANG tanpa record MASUK (Keputusan A1 — FINAL), dan absensi siswa per mapel: penutupan manual sesi (ADR 14).
* **Fase 5: Document Repository & Google Drive Integration**  
  Implementasi repositori dokumen guru, validasi berkas multi-lapis (maks 10 MB - ADR 07, tanpa ClamAV - ADR 13), kalkulasi SHA-256 lokal, dan integrasi streaming Standard Google Drive Account (ADR 06).
* **Fase 6: Document Review & Immutable Versioning**  
  Implementasi alur evaluasi Atasan mencakup seluruh guru (ADR 09), antarmuka review terproteksi, modul penomoran versi baru ($v1 \rightarrow v2$) dengan retensi permanen (ADR 08), dan alur pembatalan resmi (*Revoke Approval* - ADR 05).
* **Fase 7: Reporting, Analytics & Audit Logging**  
  Pembangunan modul rekapitulasi data presensi dan kelengkapan dokumen, ekspor CSV/XLSX, dan panel peninjau tabel audit log khusus Admin/TU (ADR 17).
* **Fase 8: Security Verification & Integration Testing**  
  Eksekusi rangkaian pengujian keamanan (Rate limit, Anti-IDOR, Magic Bytes, CSRF, XSS) dan verifikasi fungsional terpadu.
* **Fase 9: Production Hardening & Deployment**  
  Penerapan checklist kesiapan produksi, pengerasan konfigurasi server, pemisahan kredensial produksi resmi sekolah, setup backup harian (mysqldump, RPO 24h, RTO 8h - ADR 12) dan arsip permanen semester (ADR 16), serta peluncuran sistem.

---

## 39. FINAL CONSISTENCY DECLARATION

Spesifikasi PRD Konsolidasi Final ini telah diverifikasi secara menyeluruh dan menyatakan bahwa:
1. **Bebas RFID:** 100% bebas dari segala bentuk perangkat keras atau kartu RFID. Barcode pada kartu siswa adalah barcode visual, bukan RFID/NFC.
2. **Dynamic Class:** Mendukung ekspansi kelas dinamis (7A, 7B, 8A, 9C, dst.) tanpa ada logika statis atau penguncian pada baseline kelas 7A.
3. **Role & Authorization:** Pembagian wewenang 3 peran (Admin/TU, Guru, Supervisor) dievaluasi mutlak secara server-side, dengan kebijakan 2 akun terpisah bagi guru yang merangkap jabatan (ADR 04). Guru Piket bukan role baru — status piket dari `teacher_duty_schedules` (ADDENDUM §8).
4. **Cloud Storage & Database:** Metadata di MySQL 8.0+ adalah *Single Source of Truth*; berkas fisik tersimpan privat di Standard Google Drive Account (ADR 06) via server-side stream proxy dengan retensi permanen (ADR 08).
5. **Ketertelusuran:** Seluruh transisi dokumen (termasuk revokasi persetujuan - ADR 05), mutasi data kehadiran siswa (masuk/pulang + per mapel), dan perubahan jadwal piket terekam dalam jejak audit yang *append-only* dengan akses khusus Admin/TU (ADR 17).
6. **Keputusan Final (2026-09-27):** ADR 01–03 `[OBSOLETE]` (teacher_attendances dihapus), ADR 04–20 `[FINAL]` — ADR 18 diperkuat Keputusan A1 (PULANG tanpa MASUK DITOLAK), ADR 20 diperkuat Keputusan B2 (`camera=(self)`, tanpa whitelist device). Tidak ada lagi keputusan terbuka; seluruhnya terintegrasi utuh.
7. **Platform & Lingkungan:** Menggunakan MySQL 8.0+ dengan engine InnoDB, Laragon untuk development lokal di Windows, dan platform-agnostic server/hosting untuk produksi.
8. **Teacher Attendance = HAPUS TOTAL:** Tidak ada lagi absensi mandiri guru di Portal Sekolah (ADDENDUM §20 & §26).
