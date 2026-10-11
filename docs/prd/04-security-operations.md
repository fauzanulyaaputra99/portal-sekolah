# SPESIFIKASI KEAMANAN SISTEM & OPERASIONAL (SECURITY & OPERATIONS)
## APLIKASI PORTAL SEKOLAH

**Nomor Dokumen:** SEC-OPS-SEKOLAH-004  
**Referensi Dokumen:**
* PRD-SEKOLAH-001 (`01-product-requirements.md`) — *Kebutuhan Produk & Fungsional*
* ARCH-SEKOLAH-002 (`02-architecture-database.md`) — *Arsitektur Aplikasi & Skema MySQL*
* ARCH-SEKOLAH-003 (`03-document-google-drive.md`) — *Integrasi Repositori Dokumen & Private Google Drive*

**Versi Dokumen:** 2.1.0 (FINAL — ADDENDUM + Keputusan A1/B2/C1 disahkan)  
**Status Dokumen:** APPROVED FOR DEVELOPMENT  
**Tanggal Revisi ADDENDUM:** 2026-09-27 · **Finalisasi Keputusan:** 2026-09-27  
**Role Penyusun:** Senior Software Architect, Security Architect, DevSecOps Specialist, Database & Infrastructure Specialist  

---

## DAFTAR ISI
1. [Prinsip Keamanan Sistem (Security Principles)](#1-prinsip-keamanan-sistem-security-principles)
2. [Model Ancaman Tingkat Tinggi (Threat Modeling)](#2-model-ancaman-tingkat-tinggi-threat-modeling)
3. [Keamanan Autentikasi & Otorisasi Server-Side](#3-keamanan-autentikasi--otorisasi-server-side)
4. [Pengelolaan Rahasia, Kredensial & Konfigurasi Lingkungan](#4-pengelolaan-rahasia-kredensial--konfigurasi-lingkungan)
5. [Keamanan Sesi, Cookie & Web Security Headers](#5-keamanan-sesi-cookie--web-security-headers)
6. [Keamanan Basis Data MySQL & Pengerasan Host (Platform-Agnostic)](#6-keamanan-basis-data-mysql--pengerasan-host-platform-agnostic)
7. [Postur Keamanan Integrasi Google Drive](#7-postur-keamanan-integrasi-google-drive)
8. [Proteksi Berkas & Penanganan Input Tidak Terpercaya](#8-proteksi-berkas--penanganan-input-tidak-terpercaya)
9. [Standar Audit Log & Akuntabilitas Sistem](#9-standar-audit-log--akuntabilitas-sistem)
10. [Logging Aplikasi, Penanganan Galat & Error Masking](#10-logging-aplikasi-penanganan-galat--error-masking)
11. [Strategi Cadangan (Backup) & Prosedur Pemulihan (Restore)](#11-strategi-cadangan-backup--prosedur-pemulihan-restore)
12. [Batas Waktu Pemulihan: RPO & RTO](#12-batas-waktu-pemulihan-rpo--rto)
13. [Pemantauan Operasional (Monitoring) & Health Checks](#13-pemantauan-operasional-monitoring--health-checks)
14. [Keamanan Dependensi (Composer & npm Supply Chain)](#14-keamanan-dependensi-composer--npm-supply-chain)
15. [Rencana Respons Insiden Keamanan (Incident Response Plan)](#15-rencana-respons-insiden-keamanan-incident-response-plan)
16. [Protokol Pengujian Keamanan (Security Testing Plan)](#16-protokol-pengujian-keamanan-security-testing-plan)
17. [Matriks Verifikasi OWASP ASVS (Application Security Verification Standard)](#17-matriks-verifikasi-owasp-asvs-application-security-verification-standard)
18. [Pemetaan Ancaman OWASP Top 10 (2021)](#18-pemetaan-ancaman-owasp-top-10-2021)
19. [Daftar Periksa Kesiapan Produksi (Production Readiness Checklist)](#19-daftar-periksa-kesiapan-produksi-production-readiness-checklist)
20. [Kebijakan Kredensial Produksi vs Pengembangan](#20-kebijakan-kredensial-produksi-vs-pengembangan)
21. [Riwayat Keputusan Keamanan & Operasional (FINAL)](#21-riwayat-keputusan-keamanan--operasional-final)
22. [Diagram Arsitektur Keamanan & Operasional Final](#22-diagram-arsitektur-keamanan--operasional-final)

---

## 1. PRINSIP KEAMANAN SISTEM (SECURITY PRINCIPLES)

Sistem Portal Sekolah dirancang dengan filosofi pertahanan berlapis (*Defense in Depth*) yang terukur dan realistis untuk lingkungan institusi pendidikan:
1. **OWASP ASVS (Application Security Verification Standard) v4.0 sebagai Baseline Verifikasi:** Digunakan sebagai acuan perancangan dan pengujian kontrol keamanan aplikasi web.
2. **OWASP Top 10 (2021) Awareness:** Memastikan ancaman umum seperti broken access control, injeksi, kegagalan kriptografi, dan miskonfigurasi termitigasi sejak tahap blueprint.
3. **Prinsip Hak Akses Terendah (*Least Privilege*):** Pengguna aplikasi, akun basis data MySQL, dan kredensial Google API hanya diberikan izin minimum yang diperlukan untuk menjalankan fungsinya.
4. **Otorisasi Murni Sisi Server (*Server-Side Authorization*):** Seluruh hak akses divalidasi mutlak pada backend (Laravel Middleware & Policy). Menyembunyikan elemen antarmuka di frontend bukan merupakan kontrol keamanan (*security through obscurity is prohibited*).
5. **Aman Secara Bawaan (*Secure by Default*):** Seluruh konfigurasi default disetel pada tingkat proteksi tertinggi (sesi berbatas waktu, debug nonaktif di produksi, validasi berkas ketat).
6. **Pertahanan Berlapis (*Defense in Depth*):** Keamanan tidak mengandalkan satu mekanisme saja. Validasi input, kontrol model, batasan database, dan enkripsi data saling melengkapi.
7. **Akuntabilitas & Jejak Audit (*Auditability*):** Setiap mutasi data penting (status dokumen, absensi, kredensial pengguna) wajib meninggalkan jejak audit yang permanen dan tidak dapat dihapus pengguna aplikasi.
8. **Kesederhanaan Operasional (*Pragmatic Simplicity*):** Menghindari *overengineering* alat pemantauan enterprise yang membebani kapasitas staf IT sekolah. Sistem dirancang agar dapat dioperasikan secara mandiri.

---

## 2. MODEL ANCAMAN TINGKAT TINGGI (THREAT MODELING)

### 2.1 Aset Kritis yang Dilindungi
* **Akun Pengguna:** Kredensial Guru, Admin TU, dan Supervisor/Kepala Sekolah.
* **Data Pribadi & Profil:** Data induk siswa (NISN, kontak orang tua) dan tenaga pendidik (NIP, status kepegawaian).
* **Integritas Absensi:** Catatan absensi masuk/pulang siswa (barcode), absensi siswa per mata pelajaran, dan jadwal guru piket harian.
* **Kerahasiaan Dokumen Perangkat Ajar:** Berkas RPP, ATP, PROTA, PROSEM, modul ajar, dan lembar asesmen.
* **Basis Data MySQL:** Seluruh tabel transaksi, relasi akademik, dan tabel audit log.
* **Kredensial Google API:** Kredensial autentikasi Google Drive sekolah (sesuai opsi yang dipilih pada `03-document-google-drive.md`).
* **Berkas Cadangan (Backups):** Snapshot basis data dan arsip dokumen cadangan.

### 2.2 Tabel Matriks Ancaman (Threat Matrix)

| Kategori Ancaman | Dampak Risiko | Kontrol Utama Sistem | Dokumen Rujukan |
| :--- | :--- | :--- | :---: |
| **Akses Tanpa Izin (Unauthorized Access)** | Kebocoran data dokumen atau penyalahgunaan akun | Autentikasi sesi Laravel, hashing Argon2id/Bcrypt, validasi status `is_active` | `02-architecture-database.md` |
| **Peningkatan Hak Akses (Privilege Escalation)** | Pengguna biasa mendapatkan hak akses administratif | Role divalidasi server-side via middleware & check constraint DB | `01-product-requirements.md` & `02-architecture-database.md` |
| **IDOR (Insecure Direct Object Reference)** | Pengguna mengakses/mengubah dokumen atau absensi milik pihak lain | Laravel Policy resource-level check (`user_id == owner_id` atau assignment) | `02-architecture-database.md` & `03-document-google-drive.md` |
| **Pencurian Kredensial (Credential Theft / Brute Force)** | Pengambilalihan akun pengguna | Pembatasan laju login (*Rate limiting* 5x per 60 detik) & session regeneration | Bab 3 Dokumen Ini |
| **Unggahan Berkas Berbahaya (Malicious File Upload)** | Eksekusi malware atau webshell di server | Validasi 3 lapis (Ekstensi, MIME, Magic Bytes via `finfo`), penyimpanan privat di Drive | `03-document-google-drive.md` |
| **SQL Injection (SQLi)** | Kebocoran atau manipulasi database MySQL | Penggunaan Eloquent ORM & Query Builder dengan prepared statements / parameter binding | `02-architecture-database.md` |
| **Cross-Site Scripting (XSS)** | Pembajakan sesi atau eksekusi skrip tidak sah | Blade auto-escaping `{{ $data }}`, larangan raw `{!! !!}`, sanitasi input | `02-architecture-database.md` |
| **Cross-Site Request Forgery (CSRF)** | Eksekusi aksi administratif tanpa izin pengguna sah | Token CSRF pada setiap form POST/PUT/DELETE | `02-architecture-database.md` |
| **Session Hijacking / Fixation** | Peniruan identitas pengguna yang sah | Regenerasi Session ID saat login/logout, cookie `HttpOnly`, `SameSite=Lax`, `Secure` over HTTPS | Bab 5 Dokumen Ini |
| **Kebocoran Kredensial Google** | Akses tidak sah pihak ketiga ke penyimpanan cloud | Kredensial disimpan di luar web root, permission OS ketat, dilarang masuk Git, rotasi berkala | `03-document-google-drive.md` & Bab 4 Dokumen Ini |
| **Pencurian Berkas Cadangan (Backup Theft)** | Paparan data massal sekolah | Enkripsi arsip dump backup, pembatasan akses direktori backup hanya untuk akun administrator | Bab 11 Dokumen Ini |
| **Pemalsuan Akses Scanner (ADDENDUM)** | Guru non-piket membuka scanner absensi masuk/pulang | Validasi `teacher_duty_schedules` di server **pada setiap request** (bukan hanya saat halaman dibuka); HTTP 403 + teks terkunci "Anda hari ini bukan Guru Piket." | `02-architecture-database.md` & Bab 3 Dokumen Ini |
| **Pemalsuan Payload Scan (ADDENDUM)** | Pemalsuan tanggal, operator, atau mode `MASUK`/`PULANG` | `mode` divalidasi sebagai enum server-side; operator **selalu** dari sesi login; tanggal & waktu **selalu** `CURRENT_TIMESTAMP` server; token CSRF | `02-architecture-database.md` |
| **Duplikasi Scan (ADDENDUM)** | Catatan masuk/pulang ganda yang merusak laporan | Pencegahan dua lapis: logika aplikasi + `UNIQUE(student_id, attendance_date)` pada `student_school_attendances` | `02-architecture-database.md` |

---

## 3. KEAMANAN AUTENTIKASI & OTORISASI SERVER-SIDE

> Detail role, scope, dan alur autentikasi mengacu pada `01-product-requirements.md` dan `02-architecture-database.md`.

### 3.1 Standar Keamanan Autentikasi
1. **Hashing Kata Sandi:** Menggunakan algoritma **Argon2id** (atau Bcrypt dengan minimum cost factor 12). Kata sandi mentah dilarang keras disimpan ataupun dicatat ke dalam log.
2. **Pencegahan Brute-Force (Rate Limiting):**
   * Pintu login menerapkan `RateLimiter`: Maksimal **5 percobaan gagal per 60 detik** berdasarkan kombinasi username (NIP) dan IP address.
   * Jika batas terlampaui, request diblokir dengan status HTTP `429 Too Many Requests`.
   * Percobaan login yang gagal dicatat ke `audit_logs` dengan kategori `AUTH_FAILED`.
3. **Siklus Sesi Autentikasi:**
   * **Login Berhasil:** Sesi di-regenerasi secara instan (`$request->session()->regenerate()`) untuk mencegah *Session Fixation*.
   * **Logout:** Sesi diinvalidasi sepenuhnya dari penyimpanan (`$request->session()->invalidate()`) dan CSRF token di-refresh (`$request->session()->regenerateToken()`).
4. **Verifikasi Akun Aktif:** Setiap request divalidasi oleh global middleware `EnsureUserIsActive`. Jika `users.is_active = FALSE`, sesi diterminasi seketika.

### 3.2 Penegakan Otorisasi Server-Side (Anti-Bypass)
1. **Pemisahan Kewenangan Rute:** Rute aplikasi dipisahkan berdasarkan grup otorisasi yang dilindungi middleware otentikasi dan pengecekan role secara server-side.
2. **Penolakan Akses Eksplisit:** Percobaan akses ke rute yang tidak sesuai hak akses menghasilkan respons **HTTP 403 Forbidden** (bukan redirect ambigu ke halaman lain).
3. **Pemberlakuan Laravel Policy:** Seluruh aksi mutasi terhadap dokumen, penugasan mengajar, dan sesi absensi wajib memanggil otorisasi level resource (Policy) untuk mencegah manipulasi ID parameter (*Anti-IDOR*).
4. **Kontrol Akses Scanner Absensi Masuk/Pulang (ADDENDUM §11–§13):**
   * Hak membuka scanner **tidak** ditentukan oleh role statis maupun whitelist IP, melainkan oleh keberadaan data pada `teacher_duty_schedules` untuk tanggal berjalan (server date).
   * Revalidasi jadwal dilakukan **pada setiap request scan**, sehingga halaman scanner yang masih terbuka setelah jadwal diubah **tidak** dapat lagi melakukan scan. Respons galat menampilkan pesan "Jadwal piket Anda telah berubah" dan halaman dikunci.
   * Mode absensi (`MASUK` / `PULANG`) dikirim secara eksplisit oleh Guru Piket dan divalidasi server-side. **Dilarang** men-auto-detect mode berdasarkan jumlah scan.
   * Seluruh scan memakai stempel waktu server dan ID operator dari sesi; nilai dari klien diabaikan.

---

## 4. PENGELOLAAN RAHASIA, KREDENSIAL & KONFIGURASI LINGKUNGAN

1. **Pencegahan Komit Repositori (Git Hygiene):**
   * File `.env`, berkas kredensial autentikasi Google, kunci privat SSH/TLS, dan berkas sertifikat dilarang keras masuk ke dalam version control Git.
   * File `.gitignore` wajib memuat pengecualian untuk file rahasia, file environment, credentials storage, dan direktori logs.
2. **Keunikan Kunci Aplikasi:**
   * Variabel `APP_KEY`harus digenerate menggunakan mekanisme resmi Laravel, bersifat kriptografis dan unik untuk setiap environment (Development, Staging, Production). Nilai APP_KEY tidak boleh dibagikan atau dimasukkan ke repository..
3. **Penyimpanan Kredensial Aman:**
   * Kredensial autentikasi Google yang dipilih pada `03-document-google-drive.md` harus disimpan di luar web root dan hanya dapat diakses oleh account/service yang menjalankan aplikasi sesuai permission sistem operasi.
   * Kredensial tidak boleh disimpan di direktori yang dapat diakses publik via browser.
4. **Pencegahan Kebocoran Secret:** Kredensial database, kunci Google, atau token API dilarang dirender ke dalam view/template, dilarang diteruskan ke script sisi klien (JavaScript), dan disaring (*masked/filtered*) dari payload log aplikasi dan audit log.

---

## 5. KEAMANAN SESI, COOKIE & WEB SECURITY HEADERS

### 5.1 Spesifikasi Keamanan Cookie & Sesi
* **Nama Cookie Sesi:** Menggunakan nama kustom aplikasi untuk mengaburkan fingerprint bawaan framework.
* **Flag Cookie Wajib:**
  * `HttpOnly = TRUE`: Mencegah JavaScript membaca isi cookie sesi, memitigasi pencurian cookie via XSS.
  * `SameSite = Lax`: Melindungi cookie dari pengiriman lintas situs pada permintaan mutasi, memitigasi serangan CSRF.
  * `Secure = TRUE` (Wajib di Lingkungan Produksi): Menjamin cookie hanya dikirimkan melalui saluran terenkripsi HTTPS.
* **Batas Waktu Ketidakaktifan Sesi (Session Inactivity Timeout):**  
  Sistem menggunakan inactivity timeout sesuai risiko penggunaan komputer bersama di lingkungan sekolah. Nilai final telah disahkan: **120 menit (ADR 11 — `[FINAL]`)**. Konfigurasi `session.lifetime` = 120.

### 5.2 Protokol Transport & HTTPS
* **Enkripsi Transit Mutlak:** Pada lingkungan produksi, portal wajib dijalankan di atas **HTTPS (TLS 1.2 / TLS 1.3)**. Web server / reverse proxy production menangani HTTPS/TLS sesuai arsitektur deployment yang dipilih dan mengalihkan lalu lintas HTTP port 80 ke HTTPS port 443 secara otomatis.
* **Lingkungan Lokal:** Akses HTTP biasa hanya diizinkan untuk keperluan development lokal (*localhost*).

### 5.3 Kebijakan Header Keamanan Web (Web Security Headers)
Web server / reverse proxy atau Laravel Middleware menyematkan header keamanan standar:

```http
# Perlindungan MIME Sniffing
X-Content-Type-Options: nosniff

# Perlindungan Clickjacking (Membatasi pemuatan iframe hanya dari origin yang sama)
X-Frame-Options: SAMEORIGIN

# Pembatasan Pengiriman Informasi Pengarah
Referrer-Policy: strict-origin-when-cross-origin

# Pembatasan Akses Fitur Perangkat Keras Browser
# ADDENDUM §20 / ADR 20 — [FINAL, Keputusan B2]: camera=(self) disahkan agar scanner barcode (kamera HP) berfungsi.
# Akses kamera tetap dikontrol oleh permission browser (user grant) DAN otorisasi server-side Guru Piket.
Permissions-Policy: camera=(self), microphone=(), geolocation=()

# Transport Security (Diaktifkan pada lingkungan produksi ber-HTTPS)
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

> **Catatan Content-Security-Policy (CSP):**  
> Penerapan CSP dirancang agar kompatibel dengan aset hasil build Vite dan styling antarmuka aplikasi.

---

## 6. KEAMANAN BASIS DATA MYSQL & PENGERASAN HOST (PLATFORM-AGNOSTIC)

> Detail skema, relasi, constraint, dan indeks mengacu pada `02-architecture-database.md`.

### 6.1 Keamanan Basis Data MySQL
1. **Isolasi Jaringan (No Public Exposure):** Port MySQL (`3306`) dilarang dibuka ke jaringan publik internet. Basis data hanya boleh menerima koneksi dari IP internal lokal (`127.0.0.1` atau subnet privat server aplikasi).
2. **Akun Basis Data Berizin Terendah (*Least Privilege User*):**
   * Aplikasi portal **DILARANG** menggunakan akun administrative `root`.
   * Digunakan akun basis data khusus aplikasi yang hanya memiliki hak manipulasi data (`SELECT, INSERT, UPDATE, DELETE`) pada skema tabel portal sekolah.
   * Hak modifikasi struktur DDL (`DROP, ALTER, TRUNCATE`) dicabut dari akun aplikasi saat beroperasi normal di fase produksi.
3. **Perlindungan Berkas Cadangan Database:** Berkas dump backup MySQL (`mysqldump`) disimpan pada lokasi terisolasi dan hanya dapat diakses oleh akun sistem yang berwenang.

### 6.2 Pengerasan Host (Platform-Agnostic Host Hardening)
1. **Pembaruan Sistem Berkala:** Sistem operasi server (baik berbasis Linux maupun Windows Server), runtime PHP, engine MySQL 8.0+, dan web server wajib dipelihara menggunakan pembaruan keamanan resmi (*security patches*).
2. **Konfigurasi Firewall Sistem Operasi:**
   * Port terbuka untuk akses publik dibatasi hanya pada port layanan web yang diperlukan (**HTTP/HTTPS**).
   * Seluruh port administratif (seperti remote shell / SSH / RDP) dan port basis data dibatasi hanya dapat diakses dari jaringan manajemen internal atau menggunakan autentikasi kunci terenkripsi.
3. **Pengaturan Izin Akses Berkas (Filesystem Permissions):**
   * Direktori kerja sementara dan cache aplikasi (`storage/`, cache directories) diberikan hak tulis terbatas hanya untuk akun service yang menjalankan aplikasi.
   * Seluruh direktori source code aplikasi berstatus *read-only* terhadap service web server/aplikasi guna mencegah penulisan atau modifikasi script secara dinamis.
   * Kredensial rahasia disimpan di luar web root dan dilindungi permission sistem operasi sehingga tidak dapat dibaca oleh pengguna yang tidak berwenang.

---

## 7. POSTUR KEAMANAN INTEGRASI GOOGLE DRIVE

> Detail arsitektur penyimpanan dokumen, hierarki folder, penamaan file deterministik, deduplikasi, dan mekanisme proksi streaming mengacu pada `03-document-google-drive.md`.

Sistem menerapkan standar keamanan ketat untuk penyimpanan cloud:
1. **Penyimpanan Privat Penuh (*Zero Public Sharing*):** Seluruh folder dan file di Google Drive dikonfigurasi privat. Dilarang keras mengaktifkan opsi berbagi publik (*"Anyone with the link can view"*).
2. **Pemberlakuan Least Privilege pada Kredensial Google:**
   * Google authentication credential yang dipilih pada `03-document-google-drive.md` harus disimpan secara aman, menggunakan least privilege, tidak dikirim ke browser, tidak masuk Git, dan dipisahkan antara development dan production.
   * *Catatan:* Jika Service Account dipilih pada Prompt 03, izin akses dibatasi hanya ke folder root portal sekolah; Jika OAuth 2.0 dipilih, scope Google Drive harus ditentukan berdasarkan kebutuhan akses final aplikasi dan prinsip least privilege. Scope drive.file dapat digunakan apabila seluruh file yang dikelola aplikasi memenuhi batasan scope tersebut; jika tidak, scope yang diperlukan harus ditetapkan pada tahap implementasi setelah skenario akses final dipastikan.
3. **Tanpa Kredensial di Sisi Klien (*Zero Client-Side Exposure*):** Browser pengguna tidak pernah menerima API key, token rahasia, atau berkas kredensial Google.
4. **Streaming Reverse Proxy:** Akses pratinjau dan pengunduhan berkas dilayani melalui endpoint internal server aplikasi (`StreamedResponse`) yang memvalidasi otorisasi pengguna terlebih dahulu sebelum mengalirkan data biner.
5. **Prosedur Rotasi Kredensial:** Jika dicurigai terjadi kebocoran kredensial Google, administrator dapat merevokasi kredensial lama dan menerbitkan kredensial baru melalui konsol administrasi resmi tanpa merusak integritas berkas biner yang telah tersimpan di cloud.

---

## 8. PROTEKSI BERKAS & PENANGANAN INPUT TIDAK TERPERCAYA

> Detail batasan format, ukuran maksimal, dan verifikasi checksum SHA-256 mengacu pada `03-document-google-drive.md`.

1. **Prinsip Input Tidak Terpercaya (*Untrusted Input*):** Seluruh berkas yang diunggah oleh pengguna diperlakukan sebagai konten tidak terpercaya sampai seluruh tahapan validasi lolos.
2. **Validasi Tiga Lapis (Triple-Layer Validation Server-Side):**
   * Lapisan 1: Pengecekan whitelist ekstensi berkas (`.pdf`, `.docx`, `.doc`, `.xlsx`, `.xls`).
   * Lapisan 2: Pengecekan tipe MIME klien dari request header.
   * Lapisan 3: Pemeriksaan mendalam tanda tangan biner berkas (*Magic Bytes / File Signature*) di sisi server untuk memastikan isi biner berkas sesuai dengan tipe dokumen yang diizinkan.
3. **Pencegahan Eksekusi Berkas Langsung:** Berkas yang diunggah tidak disimpan di dalam direktori publik web (`public/`). Berkas langsung dialirkan ke penyimpanan cloud privat sehingga risiko eksekusi script berbahaya pada server aplikasi dapat dicegah.
4. **Sanitasi Nama Berkas:** Nama berkas asli dari pengguna disanitasi dan tidak digunakan sebagai nama objek penyimpanan fisik guna mencegah eksploitasi *Path Traversal*.

---

## 9. STANDAR AUDIT LOG & AKUNTABILITAS SISTEM

> Detail struktur tabel `audit_logs` dan relasi integritasnya mengacu pada `02-architecture-database.md`.

### 9.1 Matriks Peristiwa yang Wajib Dicatat (Mandatory Audit Events)

| Modul Sistem | Kode Aksi Audit | Deskripsi Kejadian yang Dicatat |
| :--- | :--- | :--- |
| **Autentikasi** | `AUTH_LOGIN_SUCCESS` | Pengguna berhasil masuk ke sistem |
| | `AUTH_LOGIN_FAILED` | Percobaan login gagal (sandi salah / user tidak ditemukan) |
| | `AUTH_LOGOUT` | Pengguna mengakhiri sesi portal |
| **Manajemen Akun** | `USER_CREATED` | Pembuatan akun baru oleh Admin TU |
| | `USER_STATUS_TOGGLE`| Penonaktifan / pengaktifan kembali status akun pengguna |
| | `USER_PASSWORD_RESET`| Admin melakukan reset kata sandi akun pengguna |
| **Repositori Dokumen**| `DOC_UPLOAD` | Guru mengunggah draf dokumen baru |
| | `DOC_SUBMIT` | Guru mengajukan dokumen untuk ulasan Atasan |
| | `DOC_NEW_VERSION` | Guru mengunggah versi perbaikan revisi |
| | `DOC_REVIEW_APPROVED`| Supervisor menyetujui (*Approve*) dokumen guru |
| | `DOC_REVIEW_REVISED` | Supervisor meminta revisi (*Request Revision*) dokumen |
| **~~Presensi Guru~~** | ~~`ATT_TEACHER_SELF`~~ | ~~DIHAPUS — ADDENDUM §20 & §26~~ |
| | ~~`ATT_TEACHER_CORRECT`~~| ~~DIHAPUS — ADDENDUM §20 & §26~~ |
| **Absensi Masuk/Pulang Siswa** | `SCHOOL_ATT_SCAN` | Guru Piket melakukan scan barcode siswa (masuk/pulang) |
| | `SCHOOL_ATT_CORRECT` | Admin mengoreksi absensi masuk/pulang siswa (Wajib mencatat alasan) |
| **Jadwal Piket Guru** | `DUTY_SCHEDULE_CREATE` | Admin membuat jadwal piket baru |
| | `DUTY_SCHEDULE_UPDATE` | Admin mengubah jadwal piket (guru/tanggal) |
| | `DUTY_SCHEDULE_DELETE` | Admin menghapus jadwal piket |
| **Absensi Siswa** | `ATT_SESSION_OPEN` | Guru membuka sesi absensi kelas |
| | `ATT_SESSION_CLOSED`| Guru menyimpan dan mengunci sesi absensi kelas |
| | `ATT_STUDENT_CORRECT`| Admin mengoreksi absensi siswa (Wajib mencatat alasan) |
| **Master & Sistem** | `ACADEMIC_YEAR_CHANGE`| Admin mengubah status tahun ajaran aktif |
| | `TEACHING_ASSIGN_EDIT`| Perubahan penetapan guru pengampu kelas |

### 9.2 Integritas Jejak Audit (*Append-Only Log*)
1. Data pada tabel `audit_logs` bersifat append-only pada level aplikasi. Tidak tersedia fungsi UI/API untuk mengubah atau menghapus audit log. Hak akses database juga harus membatasi modifikasi audit log sesuai kebutuhan operasional. Aplikasi tidak menyediakan antarmuka atau fungsi untuk menghapus (*DELETE*) atau memanipulasi baris log audit oleh role manapun.
2. **Penyaringan Data Sensitif:** Payload perubahan data (`old_values` dan `new_values`) wajib melewati mekanisme filter pembersih agar kata sandi, token otentikasi, atau informasi rahasia tidak pernah tersimpan di tabel audit.

---

## 10. LOGGING APLIKASI, PENANGANAN GALAT & ERROR MASKING

### 10.1 Konfigurasi Produksi Anti-Kebocoran Informasi
* **Status Debug Wajib Nonaktif:** Pada lingkungan produksi, konfigurasi lingkungan wajib disetel:  
  `APP_DEBUG=false`
* **Error Masking Ramah Pengguna:** Jika terjadi kendala internal (HTTP 500), pengguna disajikan tampilan galat generik yang bersih. Sistem **dilarang menampilkan** *stack trace*, query database mentah, path filesystem server, atau variabel lingkungan kepada pengguna akhir.

### 10.2 Logging Aplikasi Terstruktur
1. Informasi teknis mengenai kegagalan sistem, pengecualian (*exceptions*), atau kendala komunikasi API eksternal dicatat secara terpusat ke berkas log internal aplikasi.
2. **Kebijakan Rotasi Log (*Log Rotation*):** Aplikasi dikonfigurasi dengan rotasi log berkala (misal: harian dengan batas retensi 14 hingga 30 hari) untuk menjaga ketersediaan kapasitas penyimpanan server.

---

## 11. STRATEGI CADANGAN (BACKUP) & PROSEDUR PEMULIHAN (RESTORE)

> Detail mekanisme rekonsiliasi data dan recovery Google Drive mengacu pada `03-document-google-drive.md`.

### 11.1 Strategi Pencadangan Basis Data MySQL
* **Otomasi Terjadwal:** Pencadangan basis data MySQL (`mysqldump` / MySQL logical backup) dijalankan secara terjadwal menggunakan scheduler/automation yang tersedia pada environment production (misal: scheduler sistem operasi atau task automation platform).
* **Retensi Cadangan Database:**
  * Cadangan harian disimpan selama 7 hari terakhir.
  * Cadangan mingguan disimpan selama 4 minggu terakhir.
  * Cadangan penutupan semester disimpan **permanen** sebagai arsip akreditasi (ADR 16 — `[FINAL]`).
* **Keamanan Berkas Cadangan:** Berkas arsip hasil backup dilindungi dengan izin akses terbatas hanya untuk akun administrator dan disalin ke media penyimpanan terpisah / offsite backup secara aman.

### 11.2 Prosedur Tingkat Tinggi Pemulihan Basis Data (Restore Workflow)
1. **Fase 1: Karantina & Mode Pemeliharaan**  
   Aktifkan mode pemeliharaan pada aplikasi web untuk mencegah transaksi baru selama proses pemulihan berlangsung.
2. **Fase 2: Eksekusi Pemulihan Database**  
   Pulihkan snapshot basis data cadangan yang valid ke target database menggunakan prosedur restorasi standar MySQL (`mysql` CLI / logical restore).
3. **Fase 3: Verifikasi Integritas Relasional**  
   Lakukan pengujian integritas struktural: verifikasi keberadaan dan konsistensi data pada tabel-tabel utama (`users`, `classes`, `documents`, `teaching_assignments`).
4. **Fase 4: Rekonsiliasi Terhadap Cloud Storage**  
   Jalankan rutinitas rekonsiliasi data aplikasi untuk mencocokkan referensi ID berkas di database hasil pemulihan dengan objek fisik yang ada di Google Drive.
5. **Fase 5: Validasi & Pembukaan Kembali Layanan**  
   Lakukan uji verifikasi fungsionalitas login dan akses dokumen, kemudian nonaktifkan mode pemeliharaan agar portal dapat kembali melayani pengguna.

---

## 12. BATAS WAKTU PEMULIHAN: RPO & RTO

Dalam kerangka keberlanjutan operasional sekolah (*Business Continuity*), target pemulihan bencana ditentukan berdasarkan kebutuhan operasional dan ketersediaan infrastruktur:

* **RPO (Recovery Point Objective):**  
  Batas toleransi maksimal kehilangan data transaksi jika terjadi kegagalan sistem.  
  *Status:* **`[FINAL - ADR 12]` — RPO 24 Jam** (pencadangan harian otomatis di luar jam sekolah).  
* **RTO (Recovery Time Objective):**  
  Batas waktu maksimal yang diperlukan untuk memulihkan sistem hingga beroperasi normal kembali sejak insiden terjadi.  
  *Status:* **`[FINAL - ADR 12]` — RTO 8 Jam kerja** (prosedur restorasi standar oleh staf teknis sekolah).

---

## 13. PEMANTAUAN OPERASIONAL (MONITORING) & HEALTH CHECKS

Pemantauan operasional dirancang esensial, handal, dan tidak membebani sumber daya server sekolah:

### 13.1 Endpoint Health Check Internal
Sistem menyediakan endpoint pemeriksaan kesehatan internal (dapat diproteksi dengan secret token atau dibatasi ke jaringan internal):
* Memeriksa keterjangkauan koneksi ke database MySQL.
* Memeriksa kapasitas ruang penyimpanan lokal server.
* Memeriksa status konektivitas integrasi Google Drive API.
* Memberikan respons berformat JSON ringkas (misal: `{"status": "ok", "db": "healthy", "storage": "healthy"}`) tanpa membocorkan versi komponen atau data sensitif.

### 13.2 Pemantauan Kapasitas Penyimpanan Server
* Pemantauan rutin terhadap kapasitas disk tempat aplikasi dan basis data beroperasi.
* Peringatan operasional (*warning notification/log*) dicatat apabila kapasitas ruang disk melampaui ambang batas keamanan (misal: > 85%) agar tim teknis dapat melakukan pembersihan berkas sementara atau perluasan storage.

---

## 14. KEAMANAN DEPENDENSI (COMPOSER & NPM SUPPLY CHAIN)

1. **Komit Berkas Pengunci (*Lockfiles*):**
   * Berkas `composer.lock` dan `package-lock.json` wajib di-commit ke dalam repositori untuk menjamin konsistensi versi paket pustaka antara lingkungan pengembangan, pengujian, dan produksi.
2. **Audit Kerentanan Berkala:**
   * Dependensi backend diaudit berkala menggunakan perangkat bawaan: `composer audit`.
   * Dependensi frontend diaudit berkala menggunakan: `npm audit`.
   * Pustaka dengan temuan kerentanan berisiko tinggi wajib diperbarui sebelum perilisan ke lingkungan produksi.
3. **Prinsip Minimalis Pustaka:** Menghindari penambahan dependensi pihak ketiga yang tidak esensial untuk meminimalkan permukaan serangan (*attack surface*).

---

## 15. RENCANA RESPONS INSIDEN KEAMANAN (INCIDENT RESPONSE PLAN)

Prosedur berikut merupakan panduan tingkat tinggi penanganan insiden keamanan. Detail eksekusi command spesifik sistem operasi didokumentasikan dalam operational runbook terpisah sesuai platform deployment yang digunakan.

### 15.1 Akun Pengguna Terkompromisi (Compromised Account)
1. **Nonaktifkan Akun:** Ubah status akun yang terdampak menjadi nonaktif (`is_active = FALSE`) melalui panel administrasi untuk menghentikan akses seketika.
2. **Invalidasi Sesi Aktif:** Hapus dan invalidasi seluruh sesi aktif pengguna terkait dari penyimpanan sesi server.
3. **Reset Kredensial:** Lakukan pergantian kata sandi dengan kredensial baru yang aman.
4. **Pemeriksaan Audit Log:** Periksa riwayat pencatatan pada `audit_logs` untuk melacak seluruh aktivitas dan mutasi data yang sempat dilakukan oleh akun tersebut.
5. **Evaluasi Dampak Data:** Evaluasi dan lakukan koreksi administratif terhadap data dokumen, presensi, atau master data yang terdampak manipulasi.
6. **Dokumentasikan Insiden:** Catat kronologi, penyebab kebocoran, dan tindakan perbaikan dalam laporan insiden.

### 15.2 Kebocoran Kredensial Google (Google Credential Compromise)
1. **Revokasi Kredensial:** Lakukan revokasi (*revoke*) atau penghapusan kunci/kredensial yang bocor melalui konsol administrasi Google terkait.
2. **Penerbitan Kredensial Baru:** Buat kredensial pengganti sesuai metode autentikasi yang dipilih pada `03-document-google-drive.md`.
3. **Pembaruan Konfigurasi Produksi:** Perbarui berkas atau environment secret pada server produksi secara aman.
4. **Verifikasi Koneksi Repositori:** Uji konektivitas integrasi aplikasi ke Google Drive untuk memastikan layanan upload, preview, dan download berfungsi normal.
5. **Review Log & Aktivitas:** Periksa log akses Google Cloud / Google Workspace dan audit log internal untuk mendeteksi potensi pengunduhan massal atau manipulasi berkas tanpa izin.
6. **Dokumentasikan Insiden:** Buat ringkasan insiden dan langkah pencegahan pengulangan.

### 15.3 Kebocoran Kredensial Basis Data (Database Credential Compromise)
1. **Rotasi Kredensial Database:** Ganti kata sandi akun pengguna basis data pada engine MySQL.
2. **Pembaruan Konfigurasi Produksi:** Perbarui nilai konfigurasi koneksi database pada environment produksi aplikasi secara aman.
3. **Verifikasi Konektivitas Aplikasi:** Pastikan aplikasi berhasil terhubung kembali ke basis data dengan hak akses yang benar.
4. **Review Aktivitas & Integritas:** Tinjau log aktivitas basis data untuk memastikan tidak terjadi ekstraksi data (*data exfiltration*) atau perusakan integritas skema.
5. **Dokumentasikan Insiden:** Catat kronologi dan pembaruan kredensial yang telah dilakukan.

### 15.4 Terdeteksi Berkas Unggahan Mencurigakan / Berbahaya
1. **Isolasi Berkas:** Kunci status dokumen di database dan isolasi atau pindahkan berkas terkait di penyimpanan cloud ke folder karantina khusus.
2. **Hentikan Akses:** Nonaktifkan akses pratinjau dan pengunduhan berkas tersebut dari antarmuka portal.
3. **Identifikasi Pengunggah:** Telusuri data pengguna pengunggah, riwayat unggahan, dan alamat IP terkait melalui `audit_logs`.
4. **Investigasi Teknis:** Lakukan analisis teknis terhadap struktur biner dan potensi ancaman berkas.
5. **Dokumentasikan & Tindak Lanjut:** Laporkan temuan kepada pimpinan sekolah untuk tindak lanjut kebijakan institusi.

---

## 16. PROTOKOL PENGUJIAN KEAMANAN (SECURITY TESTING PLAN)

Sebelum rilis produksi, rangkaian pengujian keamanan wajib diverifikasi pada lingkungan pengujian (*Staging/QA*):

1. **Uji Penetrasi Autentikasi & Sesi:**
   * Pengujian pembatasan login (*rate limiting*): verifikasi respons HTTP 429 setelah 5 kali percobaan gagal berturut-turut.
   * Verifikasi regenerasi ID sesi saat login berhasil dan penghapusan total sesi saat logout.
2. **Uji Otorisasi & Anti-IDOR (Resource Access):**
   * Pengujian Guru A mengakses rute unduh dokumen milik Guru B $\rightarrow$ Wajib diblokir (HTTP 403 Forbidden).
   * Pengujian Guru membuka sesi absensi kelas di luar jadwal penugasan (*teaching assignment*) $\rightarrow$ Wajib diblokir (HTTP 403 Forbidden).
   * Pengujian manipulasi status dokumen oleh role yang tidak berwenang $\rightarrow$ Wajib ditolak oleh Policy.
3. **Uji Validasi Unggahan Berkas:**
   * Pengujian unggah berkas dengan ekstensi terlarang (misal skrip eksekutabel) $\rightarrow$ Ditolak validasi (HTTP 422).
   * Pengujian manipulasi ekstensi berkas yang tidak cocok dengan isi tanda tangan biner (*Magic Bytes*) $\rightarrow$ Ditolak validasi.
   * Pengujian unggahan melebihi batas ukuran maksimal $\rightarrow$ Ditolak dengan pesan kesalahan yang bersih.
4. **Uji Ketahanan Terhadap Injeksi & CSRF:**
   * Form submission tanpa token CSRF yang valid $\rightarrow$ Ditolak dengan respons HTTP 419 Page Expired.
   * Pengujian payload XSS pada kolom input teks $\rightarrow$ Memastikan seluruh karakter ter-escape secara benar pada tampilan Blade.
   * Pengujian parameter pencarian dan filter laporan terhadap injeksi SQL.
5. **Uji Keamanan Scanner Barcode & Jadwal Guru Piket (WAJIB — ADDENDUM §23):**
   * Guru **bukan** piket mengakses URL scanner secara langsung $\rightarrow$ Wajib **HTTP 403** dengan teks terkunci "Anda hari ini bukan Guru Piket."
   * Guru yang **terdaftar piket hari ini** mengakses scanner $\rightarrow$ Wajib diizinkan.
   * Halaman scanner tetap terbuka, lalu jadwal piket diubah/dihapus Admin $\rightarrow$ scan berikutnya **wajib ditolak** dengan pesan "Jadwal piket Anda telah berubah".
   * Pemalsuan payload tanggal scan $\rightarrow$ Server mengabaikan input klien dan memakai tanggal server.
   * Pemalsuan payload `operator_id`/`check_in_by_teacher_id` $\rightarrow$ Server memakai ID dari sesi login, nilai klien ditolak/diabaikan.
   * Pemalsuan `mode` selain `MASUK`/`PULANG` $\rightarrow$ Ditolak validasi enum server-side.
   * Scan `MASUK` kedua untuk siswa & tanggal yang sama $\rightarrow$ Ditolak (duplikat), termasuk uji race/concurrent.
   * Scan `PULANG` ganda $\rightarrow$ Ditolak. Scan `PULANG` tanpa catatan `MASUK` $\rightarrow$ **DITOLAK (`[FINAL — KEPUTUSAN A1]`)**; server tidak membuat check-in palsu/auto-create; respons mengarahkan ke koreksi Admin/TU.
   * Perubahan jadwal piket $\rightarrow$ Wajib menimbulkan catatan pada `audit_logs` (`DUTY_SCHEDULE_CREATE/UPDATE/DELETE`) berisi aktor, aksi, tanggal, guru lama, guru baru, dan timestamp.
   * Data absensi historis $\rightarrow$ Setelah jadwal piket berubah, baris `student_school_attendances` lama **tidak boleh** berubah (operator & stempel waktu historis terkunci).

---

## 17. MATRIKS VERIFIKASI OWASP ASVS (APPLICATION SECURITY VERIFICATION STANDARD)

Matriks berikut mengadaptasi standar verifikasi **OWASP ASVS v4.0 (Level 1 - Baseline Web Application)** untuk aplikasi Portal Sekolah. Seluruh status mencerminkan status perancangan dan kebutuhan verifikasi sebelum sistem memasuki fase produksi:

| Kode ASVS | Area Verifikasi | Kebutuhan Keamanan Spesifik | Metode Verifikasi | Status Perancangan |
| :--- | :--- | :--- | :--- | :---: |
| **V1** | **Architecture** | Pemisahan role yang jelas, otorisasi server-side terpusat | Review Blueprint Arsitektur (01, 02, 03, 04) | **REQUIRED** |
| **V2** | **Authentication** | Hashing password kuat (Argon2id/Bcrypt), proteksi brute force rate limit | Automated Feature Testing & Auth Suite | **REQUIRED / TO BE VERIFIED** |
| **V3** | **Session Mgmt** | Cookie HttpOnly, SameSite=Lax, Secure HTTPS, Session Regeneration | Inspeksi Response Header & Test Sesi | **REQUIRED / TO BE VERIFIED** |
| **V4** | **Access Control** | Penegakan Policy pada setiap aksi mutasi data, mitigasi IDOR, **kontrol akses scanner berbasis jadwal piket yang direvalidasi tiap request** | Test Suite Otorisasi Multi-Role + Uji Scanner (Bab 16.5) | **REQUIRED / TO BE VERIFIED** |
| **V5** | **Input Validation**| Validasi Form Request ketat, sanitasi nama berkas, magic bytes check | Unit & Integration Testing | **REQUIRED / TO BE VERIFIED** |
| **V6** | **Cryptography** | Enkripsi transit via HTTPS TLS 1.2+, hashing SHA-256 internal file | Audit Konfigurasi Web Server & Hash Test | **REQUIRED / TO BE VERIFIED** |
| **V7** | **Error Handling** | Error masking di UI (`APP_DEBUG=false`), log teknis tersimpan internal | Pengujian Skenario Exception & UI Error | **REQUIRED / TO BE VERIFIED** |
| **V8** | **Data Protection**| Private Google Drive, tidak ada file biner atau secret di tabel DB | Audit Skema MySQL & Integrasi Drive | **REQUIRED / TO BE VERIFIED** |
| **V10** | **File Upload** | Whitelist format dokumen, larangan eksekusi direktori upload | Penetrasi Unggah File Berbahaya | **REQUIRED / TO BE VERIFIED** |
| **V11** | **Logging & Audit**| Pencatatan seluruh aksi login, status dokumen, koreksi absensi siswa, scan absensi masuk/pulang, dan CRUD jadwal piket | Inspeksi Isi Tabel `audit_logs` | **REQUIRED / TO BE VERIFIED** |
| **V13** | **API & Integrasi**| Streaming proxy tertutup ke Google API, proteksi kredensial cloud | Validasi Pola Proxy Tanpa URL Publik | **REQUIRED / TO BE VERIFIED** |
| **V14** | **Config & Server** | Port database privat, firewall aktif, hak akses izin berkas terisolasi | Audit Konfigurasi Host & Environment | **REQUIRED / TO BE VERIFIED** |

---

## 18. PEMETAAN ANCAMAN OWASP TOP 10 (2021)

| Kategori OWASP (2021) | Potensi Risiko pada Portal Sekolah | Kontrol Pertahanan yang Diterapkan |
| :--- | :--- | :--- |
| **A01: Broken Access Control** | Pengguna memanipulasi parameter URL untuk melihat dokumen pihak lain atau mengubah absensi kelas lain. | Penegakan Laravel Policy pada setiap Controller; verifikasi kepemilikan dan relasi *teaching assignment* di level server. |
| **A02: Cryptographic Failures** | Penyadapan lalu lintas jaringan sekolah atau kebocoran kata sandi pengguna. | Enkripsi HTTPS mutlak; Hashing Argon2id/Bcrypt; tidak menyimpan data mentah rahasia di database. |
| **A03: Injection** | Serangan SQL Injection melalui input formulir pencarian atau filter laporan. | Penggunaan Eloquent ORM / Query Builder dengan parameter binding pada seluruh query basis data. |
| **A04: Insecure Design** | Alur status dokumen dimanipulasi sehingga guru dapat menyetujui dokumennya sendiri. | *State machine* kaku dan deterministik; aturan transisi status dikunci berdasarkan role yang sah (`01-product-requirements.md`). |
| **A05: Security Misconfiguration** | Mode `APP_DEBUG=true` tertinggal di server produksi sehingga mengekspos variabel rahasia. | Konfigurasi production wajib menyetel `APP_DEBUG=false`; pengerasan konfigurasi web server dan environment. |
| **A06: Vulnerable & Outdated Components**| Celah keamanan pada pustaka pihak ketiga Composer atau npm. | Pemeriksaan berkala via `composer audit` dan `npm audit`; penguncian versi pada berkas lockfile. |
| **A07: Identification & Auth Failures** | Serangan tebak kata sandi massal (brute force) terhadap akun pengguna. | Rate limiting login (5x per 60 detik); regenerasi sesi login; invalidasi total saat logout. |
| **A08: Software & Data Integrity Failures**| Berkas dokumen di cloud storage termodifikasi atau rusak tanpa terdeteksi. | Perhitungan checksum SHA-256 internal oleh aplikasi yang disimpan di MySQL sebagai *Single Source of Truth*. |
| **A09: Security Logging & Monitoring Failures**| Modifikasi data akademik penting tidak terdeteksi oleh pimpinan sekolah. | Pencatatan otomatis ke tabel `audit_logs` yang bersifat append-only; pemantauan berkala oleh Admin. |
| **A10: Server-Side Request Forgery (SSRF)** | Eksploitasi panggilan jaringan server ke endpoint eksternal berbahaya. | Aplikasi tidak menerima input URL tujuan dari pengguna; panggilan API murni dibatasi ke endpoint resmi Google Drive API. |

---

## 19. DAFTAR PERIKSA KESIAPAN PRODUKSI (PRODUCTION READINESS CHECKLIST)

Sebelum Portal Sekolah dinyatakan siap beroperasi secara resmi pada lingkungan produksi, seluruh butir periksa berikut wajib dipenuhi:

### A. Aplikasi (Application Layer)
* [ ] Konfigurasi `APP_ENV=production` terpasang pada environment file.
* [ ] Konfigurasi `APP_DEBUG=false` terpasang pada environment file.
* [ ] Kunci enkripsi unik telah digenerate (`APP_KEY` valid dan unik).
* [ ] Seluruh kata sandi akun bawaan awal (*default seeder*) telah diganti dengan kata sandi baru yang kuat.
* [ ] Konfigurasi optimasi aplikasi telah dijalankan sesuai standar framework.
* [ ] Seluruh pengujian otorisasi Role & Policy telah terverifikasi sukses.
* [ ] Audit dependensi (`composer audit` & `npm audit`) bersih dari celah keamanan berstatus critical.
* [ ] **(ADDENDUM)** Rute scanner absensi masuk/pulang terlindungi kebijakan jadwal piket; akses langsung oleh guru non-piket menghasilkan HTTP 403.
* [ ] **(ADDENDUM)** Header `Permissions-Policy: camera=(self)` aktif pada scanner — **ADR 20 `[FINAL — Keputusan B2]`**; tanpa whitelist device tambahan.
* [ ] **(ADDENDUM)** Constraint `UNIQUE(student_id, attendance_date)` pada `student_school_attendances` dan `UNIQUE(schedule_date, teacher_id)` pada `teacher_duty_schedules` telah diterapkan pada skema MySQL.
* [ ] **(ADDENDUM)** Perubahan jadwal piket terverifikasi tercatat di `audit_logs` dan tidak mengubah data absensi historis.

### B. Infrastruktur & Basis Data (Infrastructure Layer)
* [ ] Layanan HTTPS aktif dengan sertifikat SSL/TLS valid serta pengalihan otomatis dari HTTP ke HTTPS.
* [ ] Port basis data MySQL (`3306`) tidak terekspos ke internet publik.
* [ ] Akun koneksi database aplikasi menggunakan user berhak akses minimum (non-superuser / non-root).
* [ ] Skrip/automasi pencadangan database terjadwal telah aktif pada scheduler environment produksi.
* [ ] Uji coba restorasi data dari berkas cadangan telah diverifikasi berhasil.
* [ ] Hak akses izin berkas pada direktori server telah disetel secara ketat sesuai permission sistem operasi yang digunakan.

### C. Integrasi Cloud Storage (Google Drive Layer)
* [ ] Google production credential menggunakan account/credential resmi sekolah sesuai arsitektur yang dipilih pada `03-document-google-drive.md`.
* [ ] Credential production disimpan di luar web root dan dilindungi dengan filesystem/environment permissions yang sesuai.
* [ ] Hak akses folder penyimpanan Google Drive dipastikan berstatus **Private (Zero Public Sharing)**.
* [ ] Alur unggah berkas, pratinjau streaming proxy, dan pengunduhan dokumen telah teruji dengan hak akses yang benar.

### D. Operasional & Pemantauan (Operations Layer)
* [ ] Mode rotasi log aplikasi aktif dan direktori log memiliki izin tulis bagi service aplikasi.
* [ ] Endpoint pemeriksaan kesehatan sistem (*Health check*) dapat diakses oleh pihak yang berwenang dan berfungsi normal.
* [ ] Prosedur tanggap insiden keamanan telah dipahami oleh tim teknis pengelola sistem sekolah.

---

## 20. KEBIJAKAN KREDENSIAL PRODUKSI VS PENGEMBANGAN

Untuk menjaga integritas dan kerahasiaan data sekolah:
1. **Lingkungan Pengembangan (Development Environment):**
   * Pengembang menggunakan basis data lokal dengan data pengujian (*dummy data*).
   * Integrasi Google Drive dapat memanfaatkan kredensial sandbox atau penyimpanan lokal aplikasi untuk pengujian awal.
   * Pemisahan kredensial pengembangan dan produksi bersifat mutlak.
2. **Lingkungan Produksi (Production Environment):**
   * Wajib menggunakan kredensial resmi milik institusi sekolah.
   * Seluruh data uji coba, akun simulasi, dan kredensial pengembangan wajib dibersihkan dan tidak digunakan di lingkungan produksi.

---

## 21. RIWAYAT KEPUTUSAN KEAMANAN & OPERASIONAL (FINAL)

Seluruh keputusan di bawah ini telah **disahkan oleh pimpinan sekolah (2026-09-27)** dan berstatus `[FINAL]`. Blok dipertahankan sebagai *historical decision trail*; sumber otoritatif ada di `06-decision-resolution.md`:

```text
[FINAL - ADR 10] (disahkan 2026-09-27 — Opsi A: reset terpusat oleh Admin/Staff TU, tanpa mail server pada MVP)
Topik: Mekanisme Pemulihan Akun / Reset Password Guru

Pertanyaan:
Bagaimana mekanisme pemulihan kata sandi bagi guru atau staf yang lupa password?

Pilihan:
A. Pemulihan Terpusat via Staf Tata Usaha:
   Guru yang lupa password melapor ke Staf TU. Staf TU melakukan "Force Reset Password" melalui panel Admin
   yang menghasilkan sandi sementara yang wajib diganti saat login pertama.
B. Pemulihan Mandiri via Email Institusi (Self-Service Password Reset):
   Sistem mengirimkan tautan reset password berbatas waktu ke email resmi terdaftar guru.

Dampak Arsitektur & Keamanan:
Opsi A sangat cocok untuk institusi sekolah (tidak memerlukan konfigurasi mail server dan domain email aktif guru).
Opsi B lebih mandiri namun mewajibkan sekolah memiliki layanan transactional email yang stabil.
```

```text
[FINAL - ADR 11] (disahkan 2026-09-27 — Opsi B: 120 Menit / 2 Jam)
Topik: Batas Waktu Ketidakaktifan Sesi Pengguna (Session Inactivity Timeout)

Pertanyaan:
Berapa lama batas waktu maksimal sesi login dibiarkan menganggur sebelum sistem otomatis melakukan logout?

Konteks:
Sistem membutuhkan inactivity timeout yang sesuai dengan risiko penggunaan komputer bersama di lingkungan sekolah.
Nilai final ditentukan sebelum fase produksi berdasarkan kesepakatan operasional.

Pilihan Pertimbangan:
A. 60 Menit (1 Jam): Standar keamanan lebih ketat untuk workstation bersama di ruang guru.
B. 120 Menit (2 Jam): Keseimbangan antara keamanan workstation dan kenyamanan pendidik saat jam mengajar.
C. 480 Menit (8 Jam / Jam Sekolah Penuh): Kenyamanan maksimal dengan konsekuensi risiko lebih tinggi pada perangkat bersama.
```

```text
[FINAL - ADR 12] (disahkan 2026-09-27 — Opsi A: RPO 24 Jam & RTO 8 Jam kerja; backup harian terjadwal, restorasi standar)
Topik: Target Pemulihan Bencana (RPO & RTO)

Pertanyaan:
Berapa target waktu pemulihan (RTO) dan batas toleransi kehilangan data transaksi (RPO) yang ditetapkan untuk operasional sekolah?

Konteks:
Nilai final harus ditentukan berdasarkan kebutuhan operasional sekolah dan kemampuan infrastruktur yang tersedia.

Pilihan Pertimbangan:
A. RPO Harian & RTO Beberapa Jam Kerja:
   Pencadangan harian otomatis di luar jam sekolah dengan prosedur restorasi standar. Realistis dan hemat sumber daya untuk fase awal.
B. RPO & RTO Diperketat:
   Pencadangan / replikasi dengan frekuensi lebih tinggi untuk meminimalkan data gap, memerlukan dukungan infrastruktur sekunder.
```

```text
[FINAL - ADR 13] (disahkan 2026-09-27 — Opsi A: validasi server-side 3 lapis TANPA ClamAV pada MVP; modul upload dirancang modular agar adapter AV mudah ditambahkan)
Topik: Validasi dan Pemindaian Berkas Unggahan (Antivirus / Malware Scanning)

Pertanyaan:
Bagaimana arsitektur pemindaian keamanan berkas dokumen yang diunggah oleh pengguna ke sistem portal?

Prinsip Keamanan:
Validasi file pada aplikasi tetap wajib dilakukan server-side (pemeriksaan whitelist ekstensi, MIME type, dan Magic Bytes).
Google Drive memiliki mekanisme keamanan dan pemeriksaan file sendiri, tetapi mekanisme tersebut tidak boleh dianggap
sebagai satu-satunya kontrol keamanan aplikasi.

Pilihan:
A. Validasi Server-Side Ketat Tanpa Antivirus Lokal Tambahan pada MVP:
   Mengandalkan validasi multi-lapis di aplikasi dan isolasi penyimpanan cloud privat untuk meminimalkan beban CPU/RAM server sekolah.
B. Validasi Server-Side Ketat Ditambah Antivirus Engine Lokal (Hardening Tambahan):
   Menambahkan daemon antivirus lokal (misal: ClamAV) pada server sebelum berkas dialirkan ke cloud storage,
   dengan konsekuensi peningkatan kebutuhan alokasi memori dan CPU server.

Status:
FINAL (Keputusan disahkan 2026-09-27: Opsi A — tanpa daemon antivirus lokal pada MVP).
```

```text
[FINAL - ADR 20 - KEPUTUSAN B2] (disahkan 2026-09-27)
Topik: Kebijakan Permissions-Policy camera untuk Scanner Barcode Berbasis Kamera HP

Konteks:
Scanner absensi masuk/pulang siswa berjalan di browser kamera HP (ADDENDUM §3 & §4).
Header `Permissions-Policy: camera=()` akan MEMBLOKASI akses kamera dan membuat scanner tidak berfungsi.

Kebijakan Final (diterapkan pada Bab 5.3 dokumen ini):
    Permissions-Policy: camera=(self), microphone=(), geolocation=()

Keputusan:
Sekolah mengizinkan `camera=(self)` untuk browser aplikasi sendiri pada fitur scanner.
DILARANG menggunakan `camera=()` karena scanner wajib dapat berjalan.
DILARANG menambah whitelist device/origin yang kompleks.

Pilihan:
A. camera=(self) secara global (Default): Sederhana, scanner berfungsi di seluruh rute aplikasi pada origin yang sama.
B. camera=(self) hanya pada rute scanner: Lebih ketat, diterapkan via header per-rute/middleware khusus.
C. camera=(self) + whitelist device/origin internal sekolah: Paling ketat, menambah kompleksitas operasional.

Dampak Keamanan:
Kamera tetap dikontrol berlapis: (1) permission browser — pengguna harus memberi izin kamera secara eksplisit per situs; (2) otorisasi server-side Guru Piket — hanya guru yang terjadwal pada `teacher_duty_schedules` hari berjalan yang dapat membuka rute scanner dan mengirim scan; (3) X-Frame-Options: SAMEORIGIN mencegah pemanggilan kamera dari iframe pihak ketiga. Tidak ada pengambilan gambar yang dikirim tanpa aksi scan eksplisit.

Status:
[FINAL - KEPUTUSAN B2] — camera=(self) disahkan. Whitelist device tidak ditambahkan; camera=() ditolak.
```

---

## 22. DIAGRAM ARSITEKTUR KEAMANAN & OPERASIONAL FINAL

Arsitektur pertahanan berlapis dirancang netral terhadap platform sistem operasi (valid baik untuk lingkungan Linux maupun Windows Server):

```text
Browser
   │
   │ HTTPS
   ▼
Web Server / Reverse Proxy
   │
   ▼
Laravel 13 Application
   │
   ├──────────────► MySQL 8.0+
   │
   └──────────────► Private Google Drive

Operational Layer
   ├── Backup (mysqldump)
   ├── Monitoring
   ├── Health Check
   └── Incident Response
```

### Penjelasan Komponen Arsitektur:
1. **Klien (Browser):** Berinteraksi secara aman melalui protokol HTTPS dan mengelola session cookie berflag `HttpOnly`, `SameSite=Lax`, dan `Secure`.
2. **Web Server / Reverse Proxy:** Menangani terminasi SSL/TLS, menyematkan security headers (`X-Content-Type-Options`, `X-Frame-Options`, dll.), serta mengalirkan request ke aplikasi Laravel.
3. **Laravel 13 Application:** Bertindak sebagai pusat otentikasi, otorisasi role dan policy server-side, validasi berkas multi-lapis, pencatatan jejak audit, dan reverse proxy streaming privat ke cloud storage.
4. **MySQL Database:** Menyimpan data relasional akademik, referensi metadata berkas, dan jejak audit `audit_logs` pada port privat (`3306`) yang terisolasi dari akses publik.
5. **Private Google Drive:** Menyimpan objek berkas biner dokumen guru secara privat tanpa akses publik langsung dari browser klien.
6. **Operational Layer:** Menyediakan fondasi keberlanjutan sistem yang mencakup otomasi pencadangan data (*Backup*), pemantauan kapasitas dan performa (*Monitoring*), verifikasi kesiapan layanan (*Health Check*), serta prosedur mitigasi kedaruratan (*Incident Response*).

---

## 23. KONSISTENSI & BATASAN BLUEPRINT

Dokumen ini merupakan spesifikasi keamanan dan operasional tingkat tinggi (*blueprint*). Dokumen ini:
* Tidak memuat kode implementasi, migration, atau controller aplikasi.
* Tidak mengubah maupun menggantikan spesifikasi kebutuhan produk (`01-product-requirements.md`), arsitektur database (`02-architecture-database.md`), atau integrasi Google Drive (`03-document-google-drive.md`).
* Berfungsi sebagai panduan bagi perancangan teknis, konfigurasi deployment, pengujian keamanan, dan prosedur kesiapan produksi Portal Sekolah.
