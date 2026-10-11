# BLUEPRINT INTEGRASI REPOSITORI DOKUMEN & GOOGLE DRIVE
## APLIKASI PORTAL SEKOLAH

**Nomor Dokumen:** ARCH-SEKOLAH-003  
**Referensi Induk:** PRD-SEKOLAH-001 (`01-product-requirements.md`) & ARCH-SEKOLAH-002 (`02-architecture-database.md`)  
**Versi Dokumen:** 1.0.0  
**Status:** DRAFT FOR ARCHITECTURAL REVIEW  
**Role Penyusun:** Senior Software Architect, Senior Laravel Architect, Google Drive API Integration Architect, Security Architect, Database/Application Integration Specialist  

---

## DAFTAR ISI
1. [Prinsip Arsitektur Integrasi & Batasan Desain](#1-prinsip-arsitektur-integrasi--batasan-desain)
2. [Arsitektur Penyimpanan Terdistribusi (Hybrid Storage Model)](#2-arsitektur-penyimpanan-terdistribusi-hybrid-storage-model)
3. [Analisis Autentikasi Google: OAuth 2.0 vs Service Account](#3-analisis-autentikasi-google-oauth-20-vs-service-account)
4. [Arsitektur Private Google Drive & Akses Server-Side](#4-arsitektur-private-google-drive--akses-server-side)
5. [Topologi Struktur Folder Google Drive](#5-topologi-struktur-folder-google-drive)
6. [Metadata File & Database sebagai Single Source of Truth](#6-metadata-file--database-sebagai-single-source-of-truth)
7. [Strategi Penamaan Berkas & Sanitasi Data](#7-strategi-penamaan-berkas--sanitasi-data)
8. [Spesifikasi Tipe File, Validasi MIME & Batasan Ukuran](#8-spesifikasi-tipe-file-validasi-mime--batasan-ukuran)
9. [Alur Komprehensif Siklus Dokumen (Upload, View, Review, Revisi)](#9-alur-komprehensif-siklus-dokumen-upload-view-review-revisi)
10. [Model Konsistensi Terdistribusi & Pola Kompensasi (Rollback Failure)](#10-model-konsistensi-terdistribusi--pola-kompensasi-rollback-failure)
11. [Penanganan Berkas Yatim (Orphan Files Detection & Cleanup)](#11-penanganan-berkas-yatim-orphan-files-detection--cleanup)
12. [Penanganan Berkas Hilang (Missing / Deleted Files in Drive)](#12-penanganan-berkas-hilang-missing--deleted-files-in-drive)
13. [Kebijakan Penghapusan & Pengarsipan Berkas (Retention & Deletion Policy)](#13-kebijakan-penghapusan--pengarsipan-berkas-retention--deletion-policy)
14. [Arsitektur Preview & Streaming Dokumen Terproteksi (Anti-Public URL)](#14-arsitektur-preview--streaming-dokumen-terproteksi-anti-public-url)
15. [Proteksi IDOR & Otorisasi Server-Side Bertingkat](#15-proteksi-idor--otorisasi-server-side-bertingkat)
16. [Penanganan Error Google Drive API, Retry Strategy & Exponential Backoff](#16-penanganan-error-google-drive-api-retry-strategy--exponential-backoff)
17. [Mitigasi Rate Limiting & Optimasi Kuota Google API](#17-mitigasi-rate-limiting--optimasi-kuota-google-api)
18. [Manajemen Kredensial, Rahasia Lingkungan & Pemisahan Dev/Prod](#18-manajemen-kredensial-rahasia-lingkungan--pemisahan-devprod)
19. [Strategi Cadangan (Backup) & Matriks Skenario Pemulihan (Disaster Recovery)](#19-strategi-cadangan-backup--matriks-skenario-pemulihan-disaster-recovery)
20. [Proses Rekonsiliasi Database vs Google Drive](#20-proses-rekonsiliasi-database-vs-google-drive)
21. [Integrasi Audit Log Aktivitas Penyimpanan](#21-integrasi-audit-log-aktivitas-penyimpanan)
22. [Operasional, Pemantauan Sistem & Kinerja Sederhana](#22-operasional-pemantauan-sistem--kinerja-sederhana)
23. [Simplicity & Anti-Overengineering Review](#23-simplicity--anti-overengineering-review)
24. [Open Decisions & Architectural Decision Records [DECISION REQUIRED]](#24-open-decisions--architectural-decision-records-decision-required)
25. [Diagram Arsitektur Final](#25-diagram-arsitektur-final)

---

## 1. PRINSIP ARSITEKTUR INTEGRASI & BATASAN DESAIN

Rancangan integrasi repositori berkas antara Laravel, MySQL, dan Google Drive berpedoman pada prinsip inti:
1. **Security-First (Zero Public Storage):** Google Drive disetel murni sebagai *Private Cloud Object Store*. Tidak ada folder publik, tidak ada berkas "Anyone with the link", dan tidak ada kredensial yang terekspos ke sisi klien (*browser*).
2. **MySQL as Single Source of Truth:** Database MySQL adalah rujukan mutlak kepemilikan dokumen, otorisasi akses, riwayat versi, dan status siklus review. Google Drive hanya bertindak sebagai wadah penyimpanan *binary stream* fisik.
3. **Pemisahan Konteks Autentikasi Pengguna:** Guru dan staf sekolah login ke Portal Sekolah menggunakan kredensial database lokal (NIP/Password). Pengguna **tidak perlu** memiliki atau login ke akun Google pribadi saat berinteraksi dengan repositori. Seluruh komunikasi ke Google Drive ditangani di belakang layar (*server-side proxy*) oleh sistem Laravel.
4. **Resilient Eventual Consistency:** Mengakui secara sadar bahwa MySQL dan Google Drive API adalah dua sistem terdistribusi independen tanpa *two-phase commit* (2PC) native. Kegagalan parsial diatasi melalui pola *compensating action* dan *scheduled reconciliation*.
5. **Anti-Overengineering untuk Skala Sekolah:** Menghindari penggunaan infrastruktur perantara yang tidak esensial seperti Kafka, RabbitMQ, S3 storage gateway pihak ketiga, atau custom file indexing engine. Integrasi dipertahankan sederhana, andal, dan dapat dioperasikan oleh institusi sekolah.

---

## 2. ARSITEKTUR PENYIMPANAN TERDISTRIBUSI (HYBRID STORAGE MODEL)

Penyimpanan dokumen mengadopsi model terpisah (*decoupled storage pattern*):

```
                                  PORTAL SEKOLAH
                                 (Laravel Monolith)
                                         |
                       +-----------------+-----------------+
                       |                                   |
                       v                                   v
                [DATABASE MYSQL]                 [PRIVATE GOOGLE DRIVE]
              Single Source of Truth              Raw Binary Storage
              -----------------------             ----------------------
              - metadata dokumen                  - file binary (.pdf, .docx, .xlsx)
              - nomor versi (v1, v2)              - folder hierarki terstruktur
              - status persetujuan                - drive_file_id unik
              - pemilik berkas (teacher_id)       - drive_folder_id
              - catatan review & evaluasi         - enkripsi Google-at-rest
              - SHA-256 integrity hash            - zero public web access
              - timestamp & audit trail           - zero authorization logic
```

### Aturan Pemisahan Tanggung Jawab:
* **MySQL:** Dilarang keras menyimpan data binary dokumen (tipe data `BLOB` atau `LONGBLOB` ditiadakan). Database hanya menyimpan string penunjuk `gdrive_file_id`, ukuran file dalam bytes, MIME type terverifikasi, serta riwayat relasi sosial dokumen (komentar, ulasan, revisi).
* **Google Drive:** Dilarang diperlakukan sebagai basis data otorisasi. Folder di Google Drive tidak digunakan untuk mengatur hak baca/tulis per user guru. Siapapun yang berhasil mengakses Google Drive secara langsung (misal administrator IT sekolah) tidak dapat mengubah status bisnis dokumen di portal sekolah.

---

## 3. ANALISIS AUTENTIKASI GOOGLE: OAUTH 2.0 VS SERVICE ACCOUNT

Penetapan metode autentikasi mesin ke mesin (*machine-to-cloud*) adalah keputusan arsitektur paling fundamental.

### 3.1 Tabel Perbandingan Komparatif

| Dimensi Evaluasi | Opsi A: OAuth 2.0 (User Delegation / Refresh Token) | Opsi B: Google Service Account (App Identity) |
| :--- | :--- | :--- |
| **Pola Interaksi** | Memerlukan intervensi awal manusia via browser untuk *consent screen* dan pertukaran *authorization code* menjadi *refresh token*. | Murni *server-to-server* tanpa antarmuka interaksi manusia. Menggunakan kunci asimetris privat (JSON key file). |
| **Siklus Sesi Server** | Rentan gagal otomatis jika *refresh token* kedaluwarsa, dicabut, atau akun Google admin pengotorisasi berganti sandi. | Bekerja mandiri tanpa batas waktu login pengguna. Token akses (*bearer token*) diperbarui otomatis oleh SDK Google. |
| **Konteks Kepemilikan Storage** | File disimpan di Google Drive milik akun pengguna perorangan yang memberikan consent awal. | File disimpan di Google Workspace Shared Drive (Google Tim) atau Drive institusi dengan pendelegasian wewenang domain. |
| **Dukungan Lingkungan Sekolah** | Membingungkan operator sekolah karena jika akun admin yang dipakai "terhapus", seluruh integrasi portal terputus. | Terikat pada *Google Cloud Project* resmi sekolah, independen dari akun mutasi staf perorangan. |
| **Manajemen Kredensial** | Menyimpan `client_id`, `client_secret`, dan `refresh_token` yang dinamis di database / env. | Menyimpan berkas `service-account.json` terenkripsi di server lokal atau dipetakan via environment variables. |
| **Kompleksitas Operasional** | Menengah: Memerlukan penanganan *token refresh rotation* manual jika terjadi anomali OAuth. | Rendah: Ditangani otomatis oleh `google/apiclient` resmi Google via JWT bearer exchange. |

### 3.2 Rekomendasi Arsitektur Autentikasi: Service Account Terkelola
**Rekomendasi:** Gunakan **Google Service Account** (Opsi B) sebagai identitas mesin aplikasi, dengan keputusan final penempatan storage ditandai **`[DECISION REQUIRED]`** berdasarkan jenis akun Google yang dimiliki institusi sekolah:

#### Opsi Implementasi Akun Google Sekolah:
* **Pilihan 1: Google Workspace for Education / Shared Drive (Jika Tersedia):**
  * Service Account ditambahkan sebagai anggota dengan peran *Content Manager* ke dalam **Shared Drive (Google Drive Bersama)** resmi sekolah.
  * Berkas otomatis menjadi aset organisasi sekolah dan tidak memakan kuota akun perorangan.
* **Pilihan 2: Akun Google/Gmail Institusi Biasa (Jika Belum Memiliki Workspace):**
  * Akun institusi resmi sekolah membuat folder induk repositori di My Drive miliknya, lalu membagikan (*share*) folder tersebut dengan hak akses *Editor* kepada email Service Account.
  * Service Account membuat subfolder dan mengunggah berkas ke dalam folder tersebut. Kapasitas penyimpanan menggunakan kuota akun institusi tersebut.

#### Justifikasi Teknis Service Account:
1. **Server-Side Independence:** Portal Sekolah melayani pertukaran berkas kapan saja tanpa risiko gagal akibat kedaluwarsa sesi token login pengguna Google perorangan.
2. **Kesesuaian Dua Opsi Akun:** Service Account dapat beroperasi secara fleksibel baik pada Google Workspace Shared Drive maupun pada folder Google Drive akun institusi biasa.
3. **Pemisahan Kredensial:** Guru login menggunakan NIP/password database internal portal tanpa perlu mengetahui ataupun memiliki akun Google institusi.

---

## 4. ARSITEKTUR PRIVATE GOOGLE DRIVE & AKSES SERVER-SIDE

Sistem menerapkan model **Arsitektur Private Access & Server-Side Proxy** antara pengguna akhir dan Google Drive:

```text
  [Browser Guru / Supervisor]
               │
      (1) HTTP Request + Session Cookie
               │
               ▼
   [Laravel Web Application] ◄────── (2) Evaluasi Otorisasi (Policy & RBAC)
               │
      (3) Otorisasi Valid:
          Laravel inisialisasi Google Client
          menggunakan Service Account JWT
               │
               ▼
   [Private Google Drive API] ◄────── (4) Akses Privat Internal Sekolah
               │
      (5) Stream Binary File
               │
               ▼
   [Laravel Stream Proxy]
               │
      (6) HTTP Response (Content-Disposition: inline/attachment)
               │
               ▼
  [Browser Guru / Supervisor]
```

### Karakteristik Keamanan Utama:
1. Klien browser tidak pernah menerima endpoint `drive.google.com` mentah.
2. Klien browser tidak pernah berinteraksi dengan API Key atau Access Token Google.
3. Seluruh payload file mengalir melewati server Laravel yang bertindak sebagai *Streaming Reverse Proxy*.
4. Jika tautan internal di-copy oleh pihak tidak bertanggung jawab, penyerang tetap akan diarahkan ke halaman login Portal Sekolah dan diblokir oleh Policy otorisasi.

---

## 5. TOPOLOGI STRUKTUR FOLDER GOOGLE DRIVE

Struktur folder pada Google Drive dirancang hierarkis, deterministik, dan mudah diaudit secara visual oleh administrator IT sekolah tanpa kedalaman (*nesting level*) yang berlebihan.

### 5.1 Diagram Hierarki Folder
Struktur folder Google Drive sengaja dibuat teratur, sederhana, dan mudah dikelola baik oleh sistem maupun manusia:

```text
Portal_Sekolah/
└── Tahun_Ajaran/
    └── Guru/
        └── Kategori/
            └── File

Contoh Konkret:
Portal_Sekolah/
└── 2026_2027/
    ├── Guru_001_Budi_Santoso/
    │   ├── RPP/
    │   │   ├── RPP_Matematika_7A_19850101_v1_1720584000.pdf
    │   │   └── RPP_Matematika_7A_19850101_v2_1720756800.pdf
    │   ├── ATP/
    │   ├── PROTA/
    │   ├── PROSEM/
    │   └── LAINNYA/
    │
    └── Guru_002_Siti_Aminah/
        ├── RPP/
        ├── ATP/
        ├── PROTA/
        ├── PROSEM/
        └── LAINNYA/
```

### 5.2 Alasan Desain Struktur:
1. **Sederhana & Mudah Dikelola (Clean & Maintainable):** Struktur ini memetakan dokumen secara alami berdasarkan periode tahun akademik, identitas guru, dan jenis perangkat ajar tanpa menambahkan hierarki yang tidak perlu (misal subfolder versi yang berlebihan).
2. **Pemisahan Tahun Ajaran:** Memudahkan pengarsipan massal (*bulk archive*) di akhir siklus tahun akademik atau pemindahan folder lama ke cold storage jika kuota storage sekolah mendekati batas.
3. **Pemisahan Identitas Guru & Kategori:** Memberikan kenyamanan penelusuran audit fisik bagi pimpinan sekolah jika sewaktu-waktu Google Drive dibuka langsung oleh Kepala Sekolah.
4. **Versi Berdampingan:** Seluruh versi file dari dokumen yang sama ($v1, v2, v3, \dots$) diletakkan berdampingan di dalam folder kategori yang bersangkutan, sehingga histori revisi dapat langsung dilihat sekilas.

---

## 6. METADATA FILE & DATABASE SEBAGAI SINGLE SOURCE OF TRUTH

### 6.1 Matriks Atribut Metadata di MySQL vs Google Drive

| Bidang Atribut | Disimpan di MySQL (`document_versions`) | Disimpan di Google Drive File Property | Peran Sistem |
| :--- | :---: | :---: | :--- |
| **Drive File ID** | `gdrive_file_id` (VARCHAR 100) | `id` (Native Drive ID) | Kunci penghubung utama (*Foreign Identifier*). |
| **Drive Parent Folder ID** | `drive_folder_id` (Opsional) | `parents[]` | Penunjuk lokasi penempatan berkas. |
| **Versi Dokumen** | `version_number` (INT: 1, 2) | Disematkan di nama file | Database memegang kendali urutan versi. |
| **Kepemilikan (Ownership)**| `document.teacher_id` | Pemilik Service Account | Otorisasi ditentukan murni oleh database. |
| **Catatan Revisi** | `uploader_notes`, `reviews` | Tidak ada | Umpan balik hanya ada di database aplikasi. |
| **Checksum Integritas** | `file_hash_sha256` (VARCHAR 64) | Tidak ada (Dihitung internal Laravel) | Dihitung oleh Laravel sebelum/saat upload sebagai **Source of Truth** integritas aplikasi (*anti-tampering*). |
| **Ukuran File (Bytes)** | `file_size_bytes` (BIGINT) | `size` | Untuk kuota internal dan pelaporan UI. |
| **MIME Type** | `mime_type` (VARCHAR 100) | `mimeType` | Memastikan browser merender viewer yang tepat. |

### 6.2 Mekanisme Checksum SHA-256 Internal Aplikasi
* **Kalkulasi Server-Side:** Laravel menghitung nilai hash SHA-256 dari stream berkas lokal (`hash_file('sha256', $tempPath)`) sebelum proses upload ke Google Drive dilakukan.
* **Penyimpanan di MySQL:** Nilai hash 64 karakter heksadesimal disimpan pada kolom `document_versions.file_hash_sha256`.
* **Integritas Aplikasi (Source of Truth):** Untuk kebutuhan audit integritas dokumen, nilai SHA-256 hasil perhitungan Laravel adalah rujukan kebenaran (*single source of truth*). Sistem tidak bergantung pada field native Google Drive yang bervariasi per tipe akun (misal MD5 checksum), melainkan memverifikasi ulang kesesuaian biner jika dilakukan audit forensik.

```text
Uploaded File
     ↓
Laravel calculates SHA-256
     ↓
MySQL stores file_hash_sha256
     ↓
Upload binary to Google Drive
```

---

## 7. STRATEGI PENAMAAN BERKAS & SANITASI DATA

Meskipun `gdrive_file_id` adalah pengenal utama sistem, nama berkas fisik di Google Drive distandarisasi untuk menjaga keterbacaan manusia (*human-readability*) saat diinspeksi langsung:

### 7.1 Pola Standarisasi Nama Berkas Fisik (Stored Name)
Format baku nama file di Google Drive:
$$\text{[Kategori]}\_\text{[NamaMapel]}\_\text{[Kelas]}\_\text{[NIP]}\_\text{v[NomorVersi]}\_\text{[Timestamp]}\text{.[ext]}$$

*Contoh Nyata:*  
`RPP_Matematika_7A_19850101_v1_1720584000.pdf`  
`ATP_BahasaIndonesia_8B_19870202_v2_1720584100.docx`

### 7.2 Aturan Sanitasi String:
1. Karakter spasi diganti dengan garis bawah (*underscore* `_`).
2. Karakter non-alfanumerik berbahaya (`/`, `\`, `..`, `:`, `*`, `?`, `"`, `<`, `>`, `|`, `%`, `;`) dihapus secara mutlak (*stripped*) untuk mencegah serangan *Path Traversal* dan *Header Injection*.
3. Nama berkas asli guru yang diunggah dari laptopnya (misal: `Draft RPP Pak Budi Revisi Final (1).pdf`) tetap disimpan secara utuh pada kolom `document_versions.original_filename` agar guru mengenali berkasnya saat mengunduh kembali.

---

## 8. SPESIFIKASI TIPE FILE, VALIDASI MIME & BATASAN UKURAN

### 8.1 Daftar Format Dokumen yang Diizinkan (Whitelist Only)
Sistem menerapkan prinsip *Deny by Default*. Hanya ekstensi dan MIME type berikut yang diterima:

| Kategori Dokumen | Ekstensi | MIME Type Resmi (IANA) | Magic Bytes Signature (Hex) |
| :--- | :---: | :--- | :--- |
| **Dokumen Portabel (PDF)** | `.pdf` | `application/pdf` | `25 50 44 46` (`%PDF`) |
| **Dokumen Microsoft Word** | `.docx` | `application/vnd.openxmlformats-officedocument.wordprocessingml.document` | `50 4B 03 04` (PK zip) |
| **Dokumen Word Warisan** | `.doc` | `application/msword` | `D0 CF 11 E0 A1 B1 1A E1` |
| **Lembar Kerja Excel** | `.xlsx` | `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` | `50 4B 03 04` (PK zip) |
| **Lembar Kerja Warisan** | `.xls` | `application/vnd.ms-excel` | `D0 CF 11 E0 A1 B1 1A E1` |

### 8.2 Lapisan Validasi Server-Side (Deep Inspection)
Validasi unggah berkas dilarang hanya mengandalkan ekstensi nama file. Laravel Form Request wajib melakukan validasi 3 lapis:
1. **Validasi Ekstensi:** Memastikan ekstensi berada dalam whitelist (`pdf,doc,docx,xls,xlsx`).
2. **Validasi MIME Type Klien:** Memeriksa `getClientMimeType()`.
3. **Validasi Magic Bytes (File Content Sniffing):** Membaca byte pembuka berkas fisik di direktori sementara server menggunakan PHP `finfo` (Fileinfo extension) untuk mendeteksi berkas biner berbahaya (seperti file `.exe` atau `.php` yang sengaja diganti ekstensinya menjadi `.pdf`). Berkas yang tidak cocok langsung ditolak dengan pesan kesalahan ramah pengguna.

### 8.3 Batasan Ukuran Berkas (File Size Limit)
* **Batas Maksimal MVP:** **10 MB** ($10.485.760$ bytes) per berkas `[DECISION REQUIRED]`.
* **Justifikasi:** RPP, modul ajar, dan silabus sekolah umumnya berupa dokumen teks dengan sedikit diagram yang berukuran antara $500$ KB hingga $4$ MB. Batas 10 MB sangat mencukupi, mencegah pemborosan bandwidth server sekolah, dan memitigasi serangan *Denial of Service (DoS)* via pengunggahan file masif.

---

## 9. ALUR KOMPREHENSIF SIKLUS DOKUMEN (UPLOAD, VIEW, REVIEW, REVISI)

### 9.1 Alur 1: Unggah Dokumen Baru (Versi 1)
```text
[Guru: Isi Form & Pilih File]
             │
             ▼
[Laravel HTTP Request Engine] ──(Gagal)──► [Kembalikan Error Validasi 422]
             │ (Validasi Ekstensi, Magic Bytes & Ukuran <= performa tergantung ukuran file, internet, server, dan Drive API; bottleneck baru diputuskan lewat load test.)
             ▼
[Simpan Sementara di Local Storage: /tmp]
             │
             ▼
[Hitung Checksum SHA-256 File Lokal]
             │
             ▼
[GoogleDriveService: Unggah File ke Drive]
             │
     ┌───────┴────────────────────────┐
 (Gagal)                           (Sukses)
     │                                │
[Log Error & Hapus /tmp]        [Dapatkan drive_file_id]
     │                                │
[Kembalikan Pesan HTTP 500]           ▼
                             [DB Transaction: Buka Koneksi]
                             - INSERT INTO documents (status='DRAFT'/'DIKIRIM')
                             - INSERT INTO document_versions (v1, drive_file_id, hash)
                             - INSERT INTO audit_logs
                                      │
                              ┌───────┴───────┐
                          (Gagal)          (Sukses)
                              │               │
                     [Pola Kompensasi]   [DB Commit]
                     - Hapus di Drive         │
                     - Rollback DB            ▼
                                         [Hapus File /tmp]
                                              │
                                              ▼
                                         [Redirect UI + Notifikasi Sukses]
```

### 9.2 Alur 2: Guru Mengunggah Berkas Revisi (Versi Baru: v2, v3, dst.)
1. Dokumen induk saat ini berstatus `PERLU_REVISI` dengan versi aktif $v_n$.
2. Guru memilih tombol **"Unggah Revisi"**, mengisi catatan perbaikan (*uploader_notes*), dan memilih file baru.
3. Server memvalidasi bahwa hanya dokumen `PERLU_REVISI` yang boleh menerima unggahan baru.
4. File diunggah ke Google Drive dengan penamaan versi baru $v_{n+1}$ (file $v_n$ lama **tidak ditimpa/di-overwrite**).
5. Record baru dibuat di `document_versions` dengan `version_number = n + 1`.
6. Status dokumen pada tabel induk `documents` beralih dari `PERLU_REVISI` menjadi `DIKIRIM` (atau `DIREVIEW` kembali).
7. Transaksi dicatat ke `audit_logs`.

### 9.3 Alur 3: Supervisor Memeriksa & Memberikan Keputusan
1. Supervisor membuka daftar antrean dokumen berstatus `DIKIRIM`.
2. Supervisor membuka antarmuka preview dokumen versi aktif.
3. Supervisor memilih aksi:
   * **APPROVE (Setujui):** Status dokumen pada tabel `documents` diubah menjadi `DISETUJUI`. Record ulasan dibuat di `document_reviews` dengan `action = 'SETUJUI'`. Dokumen kini terkunci permanen.
   * **REQUEST_REVISION (Minta Revisi):** Supervisor **wajib** mengisi formulir catatan perbaikan (minimal 10 karakter). Status dokumen beralih menjadi `PERLU_REVISI`. Record dibuat di `document_reviews` dengan `action = 'MINTA_REVISI'` dan `notes`.
4. Sistem mencatat jejak audit dan mengirimkan notifikasi status ke dashboard guru pemilik.

---

## 10. MODEL KONSISTENSI TERDISTRIBUSI & POLA KOMPENSASI (ROLLBACK FAILURE)

Sistem mengakui bahwa transaksi ACID MySQL tidak dapat mengontrol status API pihak ketiga (Google Drive). Oleh karena itu, diterapkan pola **Saga Kompensasi Sederhana (Compensating Transaction Pattern)**:

```text
+-------------------------------------------------------------------------------+
|                      POLA KOMPENSASI KEGAGALAN DUA FASE                       |
+-------------------------------------------------------------------------------+
| FASE 1: UPLOAD CLOUD                                                          |
| Upload binary file ke Google Drive API.                                       |
| - Jika Gagal: Batalkan proses seketika, hapus file tmp lokal, return error.   |
| - Jika Berhasil: Dapatkan drive_file_id, lanjutkan ke Fase 2.                 |
+-------------------------------------------------------------------------------+
| FASE 2: TRANSAKSI DATABASE LOKAL                                              |
| Eksekusi DB::transaction (Insert documents, versions, audit).                 |
| - Jika Berhasil: Commit database, return sukses ke pengguna.                  |
| - Jika Gagal (Database Exception / Crash):                                    |
|   1. Tangkap exception (catch block).                                         |
|   2. Database secara otomatis melakukan DB::rollback().                       |
|   3. PICU AKSI KOMPENSASI:                                                    |
|      Panggil GoogleDriveService::deleteFile(drive_file_id).                   |
|   4. Jika pembersihan Google Drive berhasil:                                  |
|      State kembali bersih (Zero Orphan).                                      |
|   5. Jika pembersihan Google Drive gagal (Network Timeout):                   |
|      Tulis record ke tabel log 'drive_orphan_candidates' / audit log          |
|      untuk dibersihkan oleh Scheduled Reconciliation Job.                     |
+-------------------------------------------------------------------------------+
```

---

## 11. PENANGANAN BERKAS YATIM (ORPHAN FILES DETECTION & CLEANUP)

### 11.1 Definisi Berkas Yatim (*Orphan File*)
Sebuah berkas binary yang ada dan memakan ruang penyimpanan di Google Drive sekolah, namun **tidak memiliki rekaman padanan yang valid** pada tabel `document_versions` di MySQL (akibat kegagalan jaringan saat kompensasi Fase 2 di atas).

### 11.2 Mekanisme Pendeteksian & Pembersihan Aman
Sistem **DILARANG** menghapus berkas Google Drive secara agresif atau otomatis tanpa masa tenggang (*grace period*). Pembersihan dilakukan melalui langkah terukur:
1. **Grace Period (24 Jam):** Berkas yang baru dibuat dalam 24 jam terakhir tidak boleh disentuh untuk menghindari salah hapus file yang sedang dalam proses upload lambat.
2. **Scheduled Audit Command (`php artisan drive:detect-orphans`):**
   * Berjalan terjadwal (misal: setiap hari Minggu malam).
   * Melakukan listing berkas di Google Drive via Service Account.
   * Mengumpulkan `file_id` di Drive, lalu membandingkannya dengan query:
     `SELECT gdrive_file_id FROM document_versions WHERE gdrive_file_id IN (...)`.
   * Berkas di Drive yang tidak ditemukan di DB dan berusia $> 24$ jam dipindahkan ke folder khusus:
     `Portal_Sekolah/_Orphan_Quarantine/`.
   * Administrator IT sekolah menerima laporan daftar file karantina pada panel Admin untuk verifikasi manual sebelum dihapus permanen (*safe purge*).

---

## 12. PENANGANAN BERKAS HILANG (MISSING / DELETED FILES IN DRIVE)

### 12.1 Definisi Berkas Hilang (*Missing File*)
Record metadata dokumen ada di database MySQL lengkap dengan `gdrive_file_id`, namun ketika pengguna (Guru/Supervisor) mencoba melihat atau mengunduh dokumen, Google Drive API mengembalikan respon `404 Not Found` (misal karena file terhapus manual oleh admin Google Workspace atau folder terpindah).

### 12.2 Strategi Penanganan Sistem Terhadap Missing Files:
1. **Pencegahan Error 500 / System Crash:** Controller membungkus panggilan stream dalam blok `try-catch (Google\Service\Exception $e)`.
2. **User-Facing Notification:** Alih-alih menampilkan error teknis, sistem merender halaman pesan informatif yang ramah:  
   > *"Berkas fisik dokumen ini sementara tidak dapat ditemukan di penyimpanan cloud sekolah. Sistem telah mencatat insiden ini. Silakan hubungi Staf Tata Usaha / Tim IT Sekolah."*
3. **Pencatatan Insiden Kritis:** Sistem otomatis mencatat log pada `audit_logs` dengan level peringatan tinggi (`FILE_MISSING_ALERT`) menyertakan `document_id`, `version_number`, dan `gdrive_file_id`.
4. **Recovery & Tindak Lanjut Administratif:** Dokumen terkait ditandai di dashboard Admin dengan badge peringatan *"Berkas Cloud Hilang"*. Pemulihan berkas yang terhapus dapat dicoba terlebih dahulu melalui mekanisme Trash/recovery Google Drive sesuai kebijakan retensi yang berlaku pada jenis akun Google yang digunakan (misal Google Workspace Admin Console / Drive Trash). Namun perlu ditekankan bahwa **fitur Trash Google Drive bukan pengganti sistem cadangan resmi**. Jika berkas fisik tidak dapat dipulihkan dari cloud, sistem menyediakan opsi pemulihan administratif via Admin untuk meminta guru mengunggah ulang (*re-upload*) dokumen pengganti atau menerbitkan versi baru.

---

## 13. KEBIJAKAN PENGHAPUSAN & PENGARSIPAN BERKAS (RETENTION & DELETION POLICY)

### 13.1 Prinsip Preservasi Jejak Akademik & Retensi Versi Lama
* **Prinsip Utama Retensi:** **Semua versi dokumen fisik ($v1, v2, v3, \dots$) dipertahankan sepenuhnya** sampai pihak sekolah menetapkan kebijakan retensi resmi `[DECISION REQUIRED]`.
* **Immutability & Audit Trail:**
  * Versi 1 ($v1$) tetap ada dan dapat ditelusuri.
  * Versi 2 ($v2$) adalah berkas fisik baru yang berdiri sendiri di Google Drive.
  * Versi lama tidak ditimpa (*no overwrite*), sehingga histori evaluasi dan catatan review masa lalu tetap utuh.
* **Larangan Penghapusan:** Guru **TIDAK DIIZINKAN** menghapus fisik berkas versi terdahulu yang telah melalui proses ulasan pimpinan. Guru hanya dapat membatalkan/menghapus draft awal yang belum pernah diajukan.

### 13.2 Soft-Delete & Pengarsipan Logis
1. Jika suatu dokumen dibatalkan atau guru yang bersangkutan mutasi/keluar, data di MySQL ditandai dengan status `ARCHIVED` (atau memanfaatkan Laravel `SoftDeletes`).
2. Berkas fisik di Google Drive **TIDAK DIHAPUS SECARA OTOMATIS** berdasarkan hitungan hari jika belum ada keputusan formal sekolah.
3. Tindakan pembersihan berkas lama hanya dapat diaktifkan jika sekolah secara resmi mengesahkan kebijakan retensi berkas (misal pasca 1 tahun ajaran berakhir) melalui otorisasi khusus Administrator Sekolah `[DECISION REQUIRED]`.

---

## 14. ARSITEKTUR PREVIEW & STREAMING DOKUMEN TERPROTEKSI (ANTI-PUBLIC URL)

### 14.1 Pemilihan Pola Streaming: Server-Side Streaming Proxy
Terdapat dua opsi teknis untuk menampilkan dokumen ke browser:
* *Opsi A: Temporary Pre-signed URL Google Drive.* (Memerlukan pembagian hak akses per user Google yang kompleks).
* *Opsi B: Laravel Server-Side Stream Proxy.* (Server Laravel mengunduh stream chunk dari Google API dan mem-pipe langsung ke HTTP response browser).

**Keputusan Arsitektur:** Gunakan **Opsi B (Server-Side Stream Proxy)**.

```text
Browser User ──(GET /documents/{id}/versions/{v}/preview)──► Laravel Controller
                                                                  │
                                                        [Policy Check Lolos]
                                                                  │
                                                       Google Drive API Client
                                                      (Download Stream Buffer)
                                                                  │
                                                     HTTP Response Headers:
                                                     - Content-Type: application/pdf
                                                     - Content-Disposition: inline
                                                     - X-Content-Type-Options: nosniff
                                                     - Cache-Control: private, no-store
                                                                  │
Browser merender tampilan PDF native ◄────────────────────────────┘
```

### 14.2 Keunggulan Pola Streaming Proxy:
1. **Kerahasiaan Mutlak:** Browser tidak pernah mengetahui URL eksternal Google Drive.
2. **Anti-Sharing:** Dokumen tidak dapat dibuka di tab incognito atau perangkat lain tanpa session login portal yang aktif.
3. **Format Support:** Dokumen format `.pdf` dapat langsung dibuka oleh native PDF viewer bawaan browser (Chrome, Edge, Firefox, Safari). Untuk dokumen `.docx` dan `.xlsx`, sistem menyajikan tombol unduh (*attachment download*) atau viewer berbasis pratinjau lokal `[SHOULD HAVE]`.

### 14.3 Manajemen Memori & Efisiensi Streaming Server
Untuk menjaga stabilitas server dan mencegah *PHP Memory Exhaustion*:
* **Chunked Stream Transfer:** Laravel **dilarang membaca seluruh isi file biner ke dalam memori/RAM PHP sekaligus** (hindari `file_get_contents()` pada file besar). Sistem mengalirkan data secara bertahap (*chunked output buffer stream*, misal buffer 8 KB - 64 KB per chunk) langsung dari Google API stream ke output HTTP response (`fpassthru` / `StreamedResponse`).
* **Dukungan HTTP Range Request:** Untuk preview dokumen PDF multi-halaman berukuran besar, controller mendukung header `Range: bytes=...` (HTTP 206 Partial Content) agar browser dapat memuat halaman PDF secara cepat tanpa mengunduh keseluruhan berkas dari awal.
* **Isolasi Klien:** Klien browser tetap hanya berkomunikasi dengan Laravel Stream Proxy, tanpa pernah mendapatkan kredensial Google ataupun URL langsung ke Google Drive.
* **Kesederhanaan MVP:** Tidak menggunakan CDN, caching proxy terpisah, object storage sekunder, atau microservice transcoding. Stream langsung via Laravel `StreamedResponse` standar sudah sangat efisien dan memadai.

---

## 15. PROTEKSI IDOR & OTORISASI SERVER-SIDE BERTINGKAT

Setiap endpoint akses berkas (unduh maupun pratinjau) dilindungi dari serangan *Insecure Direct Object Reference (IDOR)* melalui **Laravel Policy Server-Side**:

```php
// Representasi Logika Policy (Konseptual):
public function view(User $user, DocumentVersion $version): bool
{
    $document = $version->document;

    // 1. Admin / TU selalu memiliki hak akses administratif
    if ($user->role === 'admin') {
        return true;
    }

    // 2. Guru hanya boleh melihat dokumen miliknya sendiri
    if ($user->role === 'teacher') {
        return $document->teacher_id === $user->teacher->id;
    }

    // 3. Supervisor boleh melihat seluruh dokumen yang diajukan untuk review
    if ($user->role === 'supervisor') {
        return in_array($document->status, ['DIKIRIM', 'DIREVIEW', 'PERLU_REVISI', 'DISETUJUI']);
    }

    return false;
}
```

### Pertahanan Terhadap Eksploitasi Parameter Angka (Enumeration Attack):
* Jika Guru A (`user_id = 5`) mencoba memanipulasi parameter URL:  
  `GET /documents/versions/102/download` (di mana versi 102 milik Guru B),  
  Laravel Policy mendeteksi ketidakcocokan kepemilikan dan langsung melempar respon **HTTP 403 Forbidden**. Tidak ada panggilan API yang diteruskan ke Google Drive.

---

## 16. PENANGANAN ERROR GOOGLE DRIVE API, RETRY STRATEGY & EXPONENTIAL BACKOFF

Komunikasi jaringan ke Google Cloud API dapat mengalami kendala transien (*transient network glitches*). Sistem membedakan penanganan berdasarkan kategori galat HTTP status code:

### 16.1 Matriks Penanganan Kategori Galat Google Drive API

| HTTP Status Code | Kategori Galat | Tindakan Sistem | Kebijakan Retry | Pesan Pengguna (User-Facing) |
| :--- | :--- | :--- | :---: | :--- |
| **401 Unauthorized** | Token / Kredensial Tidak Valid | Log error kritis, refresh access token via JWT | **1x Retry** setelah refresh | *"Kendala autentikasi penyimpanan cloud. Hubungi admin sekolah."* |
| **403 Rate Limit / User Rate Limit Exceeded** | Quota Limit Google API | Tunda eksekusi menggunakan Exponential Backoff | **3x Retry** ($2^n$ detik) | *"Server penyimpanan sedang sibuk. Mohon tunggu beberapa detik."* |
| **403 Storage Quota Exceeded** | Kapasitas Drive Penuh | Log error darurat, beri alert ke Admin | **Tidak Ada Retry** | *"Kapasitas penyimpanan dokumen sekolah penuh. Segera hubungi TU."* |
| **404 Not Found** | File ID Tidak Ditemukan di Drive | Log incident missing file, tandai di database | **Tidak Ada Retry** | *"Berkas fisik tidak ditemukan di cloud storage."* |
| **500, 502, 503, 504** | Google Cloud Service Unavailable | Backend Google sedang down atau timeout jaringan | **3x Retry** dengan jitter | *"Layanan cloud sedang mengalami gangguan sejenak. Coba sesaat lagi."* |

### 16.2 Algoritma Exponential Backoff dengan Jitter
Untuk galat transien (429, 500, 503), sistem menerapkan penundaan bertingkat dengan variasi acak (*jitter*):
$$T_{\text{wait}} = 2^{\text{attempt}} \times 1.000\text{ ms} + \text{random}(100, 500)\text{ ms}$$
* Percobaan 1: Gagal $\rightarrow$ Tunggu $\sim 2{,}2$ detik $\rightarrow$ Percobaan 2.
* Percobaan 2: Gagal $\rightarrow$ Tunggu $\sim 4{,}3$ detik $\rightarrow$ Percobaan 3.
* Percobaan 3: Gagal $\rightarrow$ Berhenti (*Fail Fast*), lemparkan exception resmi, picu transaksi kompensasi.

---

## 17. MITIGASI RATE LIMITING & OPTIMASI KUOTA GOOGLE API

Google Drive API menetapkan kuota default: $12.000$ request per menit per proyek dan batasan per pengguna. Portal Sekolah dirancang sangat hemat request (*API Request Conservation*):

### Strategi Efisiensi Pemanggilan API:
1. **Hindari Metadata Polling ke Google Drive:** Informasi ukuran berkas, tipe MIME, tanggal unggah, dan nama berkas dibaca satu kali saat upload dan disimpan di MySQL. Sistem **DILARANG** memanggil endpoint `drive.files.get` hanya untuk menampilkan daftar tabel dokumen di web portal.
2. **Caching ID Folder Induk:** ID folder tahun ajaran dan folder guru di-cache dalam memori aplikasi atau tabel lokal sehingga sistem tidak perlu melakukan query `files.list` (pencarian folder) berulang-ulang setiap kali ada guru mengunggah berkas.
3. **No Batch Polling:** Tidak ada background worker yang terus-menerus mem-polling Google Drive tanpa adanya interaksi pengguna nyata.

---

## 18. MANAJEMEN KREDENSIAL, RAHASIA LINGKUNGAN & PEMISAHAN DEV/PROD

### 18.1 Larangan Keras Penyimpanan Kredensial di Source Code
* Berkas berkas kredensial Google (`service-account.json`), private key PEM, dan refresh token **DILARANG KERAS MASUK KE REPOSITORY GIT**.
* File `.gitignore` wajib mengecualikan:
  ```gitignore
  storage/credentials/*.json
  storage/credentials/*.pem
  .env
  ```

### 18.2 Konfigurasi Environment Variables (`.env`)
Pengaturan kredensial diisolasi pada variabel lingkungan server:
```dotenv
# Konfigurasi Storage Portal Sekolah
DOCUMENT_STORAGE_DISK=google
GOOGLE_DRIVE_APPLICATION_NAME="Portal Sekolah App"
GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON="/var/secrets/google/service-account.json"
GOOGLE_DRIVE_SHARED_DRIVE_ID="0Axxxxxxxxxxxxxxx"
GOOGLE_DRIVE_ROOT_FOLDER_ID="1Bxxxxxxxxxxxxxxx"
GOOGLE_DRIVE_MAX_UPLOAD_SIZE_MB=10
```

### 18.3 Pemisahan Lingkungan (Development vs Production)
* **Lingkungan Development / Pengujian Lokal:**
  * Pengembang dapat menggunakan *Google Cloud Project* sandbox gratis atau akun pengujian terisolasi.
  * Opsi fallback lokal: Pengembang dapat mengarahkan `DOCUMENT_STORAGE_DISK=local` (menyimpan di storage lokal `/storage/app/documents`) untuk mempercepat siklus testing offline tanpa ketergantungan internet.
* **Lingkungan Production:**
  * Wajib menggunakan Service Account yang dibuat di bawah Google Cloud Console milik organisasi resmi sekolah.
  * Berkas JSON Service Account disimpan di direktori terproteksi sistem operasi dengan permission/akses yang aman (hanya dapat dibaca oleh service proses web server).

---

## 19. STRATEGI CADANGAN (BACKUP) & MATRIKS SKENARIO PEMULIHAN (DISASTER RECOVERY)

Sistem menyadari bahwa cadangan database tidak mencakup berkas biner di cloud, dan sebaliknya. Kedua komponen harus memiliki strategi pemulihan terpadu:

### 19.1 Matriks Skenario Pemulihan Bencana (Disaster Recovery Matrix)

| Skenario Insiden | Dampak Kerusakan | Prosedur Pemulihan (Recovery Procedure) | Estimasi Data Loss |
| :--- | :--- | :--- | :--- |
| **Skenario 1: Database MySQL Rusak / Crash** (Drive Fisik Masih Utuh) | Metadata, akun, riwayat ulasan hilang. File binary di Google Drive tetap aman. | 1. Restore database dari snapshot backup harian terakhir (`mysqldump`).<br>2. Jalankan command rekonsiliasi `php artisan drive:reconcile` untuk mendeteksi file yang diunggah antara waktu snapshot dan waktu crash. | Data transaksi sejak backup terakhir (RPO $\le 24$ jam). File binary tetap utuh di Drive. |
| **Skenario 2: File di Google Drive Terhapus Tidak Sengaja** (DB Masih Utuh) | Metadata valid tetapi file stream 404 saat dibuka. | 1. Upayakan pemulihan via mekanisme **Trash / Admin Recovery Google Drive** sesuai kebijakan retensi yang berlaku pada jenis akun Google yang digunakan.<br>2. Perlu ditekankan bahwa Trash bukan pengganti sistem cadangan resmi.<br>3. Jika terhapus permanen dari Drive, tandai record di DB sebagai missing, lakukan prosedur recovery/unggah ulang via Admin. | Dokumen terkait yang terhapus permanen dan tidak dapat dipulihkan dari Drive. |
| **Skenario 3: Kredensial Service Account Bocor / Dicabut** | Seluruh operasi repositori gagal (401/403). Database lokal normal. | 1. Buka Google Cloud Console, generate key JSON baru untuk Service Account.<br>2. Timpa file JSON rahasia di server produksi.<br>3. Restart process web server / PHP service. File di Drive tidak mengalami kerusakan. | Zero Data Loss (Downtime $\sim 10$ menit). |
| **Skenario 4: Server Mati saat Fase Upload** (Fase Kompensasi Terputus) | Terdapat orphan file di Drive tanpa record database. | 1. Server menyala kembali.<br>2. Scheduled job `drive:detect-orphans` menemukan file tidak bertuan dan memindahkannya ke karantina. | Zero Data Loss pada database. Kuota Drive kembali bersih. |
| **Skenario 5: Folder Google Drive Dipindahkan Manual oleh Staf IT** | Path folder berubah di web Drive. | Karena sistem mengidentifikasi berkas dan folder menggunakan `drive_file_id` (yang bersifat permanen dan tidak terpengaruh perubahan nama/pemindahan di Drive), sistem **tetap bekerja normal** tanpa error! | Zero Data Loss & Zero Downtime. |

---

## 20. PROSES REKONSILIASI DATABASE VS GOOGLE DRIVE

Rekonsiliasi adalah proses pemindaian periodik untuk memastikan integritas dua arah:

$$\text{Kesesuaian Data} = (\text{DB} \cap \text{Drive})$$

### Alur Kerja Perintah Rekonsiliasi (`php artisan drive:reconcile`):
1. **Langkah 1 (DB $\rightarrow$ Drive Verification):**
   * Mengambil sampel berkas aktif dari tabel `document_versions`.
   * Melakukan panggilan ringan batch `files.get` (hanya meminta field `id, trashed`).
   * Menandai record yang mengembalikan status `trashed = true` atau `404` sebagai *Missing File*.
2. **Langkah 2 (Drive $\rightarrow$ DB Verification):**
   * Membaca daftar berkas pada folder Google Drive sekolah.
   * Mencocokkan `file_id` dengan tabel `document_versions`.
   * Berkas yang tidak tercatat diklasifikasikan sebagai *Orphan File* dan dicatat dalam laporan audit.
3. **Hasil Akhir:** Administrator dapat mengunduh ringkasan audit rekonsiliasi berformat tabel dari dashboard Admin.

---

## 21. INTEGRASI AUDIT LOG AKTIVITAS PENYIMPANAN

Seluruh interaksi yang menyentuh repositori berkas dicatat secara ketat pada tabel `audit_logs` aplikasi dengan rincian berikut:

```text
[Contoh Record Audit Log Unggah Berkas]
- action:          "DOCUMENT_VERSION_UPLOAD"
- user_id:         14 (Guru Budi)
- role:            "teacher"
- auditable_type:  "App\Models\DocumentVersion"
- auditable_id:    105
- new_values:      {
                     "version_number": 2,
                     "file_name": "RPP_Matematika_7A_v2.pdf",
                     "gdrive_file_id": "1abcXYZ...999",
                     "file_size": 2451020,
                     "sha256": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"
                   }
- ip_address:      "192.168.1.45"
- created_at:      "2026-09-19T14:15:22+07:00"
```

*Keamanan Audit:* Kredensial Service Account, Bearer Token, dan Private Key Google tidak pernah dicatat ke dalam log.

---

## 22. OPERASIONAL, PEMANTAUAN SISTEM & KINERJA SEDERHANA

1. **Pemantauan Kapasitas Penyimpanan:** Panel Admin menampilkan kartu widget estimasi penggunaan Google Drive (mengambil data agregasi `SUM(file_size_bytes)` dari database lokal tanpa perlu membebani Google API).
2. **Log Error Lokal:** Seluruh galat Google API dicatat ke log aplikasi Laravel (`storage/logs/laravel.log`) dengan prefix `[GOOGLE_DRIVE_INTEGRATION_ERROR]`.
3. **Validasi Kebutuhan Bandwidth & Akses Bersamaan:** Kebutuhan bandwidth jaringan dan kemampuan akses bersamaan (*concurrent access*) **wajib divalidasi melalui pengujian nyata (load/stress testing) sebelum deployment ke lingkungan produksi**, bukan ditetapkan secara kaku di awal. Faktor evaluasi pengujian mencakup:
   * Estimasi jumlah pengguna aktif bersamaan (saat jam sibuk sekolah).
   * Rata-rata dan batas maksimal ukuran dokumen yang diakses.
   * Jumlah transaksi upload berkas secara bersamaan (misal saat batas akhir pengumpulan perangkat ajar).
   * Jumlah preview/streaming dan download secara bersamaan oleh guru dan supervisor.
   * Kualitas dan stabilitas koneksi lokal server sekolah serta throughput tautan internet menuju Google Drive API.

---

## 23. SIMPLICITY & ANTI-OVERENGINEERING REVIEW

Pemeriksaan kepatuhan prinsip kesederhanaan sistem:
* [x] **Apakah membutuhkan Redis Queue khusus untuk upload?** **TIDAK.** Untuk MVP dengan kapasitas sekolah menengah (puluhan guru), proses upload berkas $\le 10$ MB ke Google Drive memakan waktu rata-rata 1-3 detik via synchronous HTTP request standar. Jika terjadi lonjakan akhir semester, antrean database default Laravel (`php artisan queue:work`) dapat diaktifkan tanpa instalasi broker eksternal.
* [x] **Apakah membutuhkan Custom Document Converter (misal LibreOffice di server)?** **TIDAK.** Konversi format file dihindari pada MVP. Dokumen ditampilkan menggunakan native viewer browser (PDF) atau diunduh langsung (.docx/.xlsx).
* [x] **Apakah struktur folder Google Drive terlalu kompleks?** **TIDAK.** Struktur folder dibuat teratur dan sederhana (Tahun Ajaran $\rightarrow$ Guru $\rightarrow$ Kategori) tanpa menambah hierarki yang tidak perlu.

---

## 24. OPEN DECISIONS & ARCHITECTURAL DECISION RECORDS [DECISION REQUIRED]

Berikut adalah keputusan arsitektur integrasi Google Drive yang memerlukan konfirmasi formal dari pimpinan/pengelola IT sekolah:

```text
[DECISION REQUIRED - ADR 06]
Topik: Jenis Akun Google Organisasi Sekolah (Google Workspace vs Akun Institusi Biasa)

Pertanyaan:
Apakah sekolah memiliki langganan Google Workspace for Education (dengan fitur Shared Drive / Google Tim)?

Pilihan:
A. Ya, Memiliki Google Workspace for Education:
   Service Account akan diintegrasikan langsung ke dalam Shared Drive resmi sekolah (Kapasitas terpusat institusi).
B. Tidak, Hanya Memiliki Akun Gmail/Google Drive Institusi Standar:
   Folder repositori dibuat di Drive pribadi akun sekolah, lalu folder tersebut di-"Share" dengan akses Editor ke Service Account.

Dampak Arsitektur:
Opsi A adalah praktik terbaik industri (Enterprise standard) yang menjamin kepemilikan file berada di bawah institusi sekolah.
Opsi B memerlukan konfigurasi izin sharing folder manual di awal dan kapasitas bergantung pada kuota akun pemilik folder.
```

```text
[DECISION REQUIRED - ADR 07]
Topik: Batas Maksimal Ukuran Unggah Dokumen (Upload Limit)

Pertanyaan:
Berapa batas maksimal ukuran file yang diizinkan untuk diunggah oleh guru?

Pilihan:
A. 10 MB per berkas (Rekomendasi Arsitektur untuk efisiensi server dan bandwidth sekolah).
B. 25 MB per berkas (Memberikan fleksibilitas lebih bagi guru yang menyertakan banyak gambar beresolusi tinggi di modul ajar).
C. 50 MB per berkas.

Dampak Arsitektur:
Opsi A meminimalkan risiko timeout dan menghemat konsumsi RAM server Laravel saat streaming proxy.
Opsi B dan C membutuhkan peningkatan nilai 'upload_max_filesize' dan 'post_max_size' pada konfigurasi PHP-FPM dan Nginx sekolah.
```

```text
[DECISION REQUIRED - ADR 08]
Topik: Kebijakan Pembersihan Berkas Versi Lama yang Berstatus Perlu Revisi

Pertanyaan:
Apakah berkas fisik dari versi lama yang berstatus 'PERLU_REVISI' (misal file v1 setelah guru mengunggah v2) tetap disimpan selamanya di Google Drive?

Pilihan:
A. Disimpan Selamanya (Infinite Retention):
   Seluruh file fisik v1, v2, v3 tetap ada di Google Drive untuk kebutuhan audit jejak rekam supervisi akreditasi.
B. Dihapus Otomatis Pasca Kelulusan/Persetujuan:
   Setelah versi final (misal v3) berstatus 'DISETUJUI', berkas fisik versi sebelumnya (v1, v2) dihapus dari Google Drive
   untuk menghemat kuota, namun catatan ulasan dan metadata histori revisi di database tetap dipertahankan.

Dampak Arsitektur:
Opsi A membutuhkan kuota penyimpanan cloud yang lebih besar namun menjamin 100% data audit fisik tetap utuh.
Opsi B membutuhkan background cron job pembersihan berkas (*garbage collector*) di server Laravel.
```

```text
[DECISION REQUIRED - ADR 09]
Topik: Ruang Lingkup Otorisasi Review Dokumen oleh Atasan / Supervisor

Pertanyaan:
Apakah seorang Supervisor (misal Wakil Kepala Sekolah atau Pengawas) dapat memeriksa dokumen seluruh guru di sekolah, ataukah dibatasi berdasarkan pembagian rumpun mata pelajaran?

Pilihan:
A. Supervisi Terbuka Sekolah (Open School Scope):
   Seluruh Supervisor yang terdaftar dapat memeriksa, membaca, dan menyetujui dokumen dari seluruh guru tanpa memandang mata pelajaran.
B. Supervisi Berbasis Rumpun / Penugasan Khusus (Scoped Review Assignment):
   Supervisor X hanya dapat memeriksa berkas guru rumpun MIPA, sedangkan Supervisor Y khusus rumpun IPS dan Bahasa.

Dampak Arsitektur:
Opsi A sangat direkomendasikan untuk fase MVP karena menyederhanakan logika Policy otorisasi.
Opsi B membutuhkan tabel relasi penugasan baru di database admin: 'supervisor_assignments (supervisor_id, subject_id / grade_level_id)'.
```

---

## 25. DIAGRAM ARSITEKTUR FINAL

Berikut adalah topologi arsitektur lengkap interaksi antara Klien, Aplikasi Laravel Portal Sekolah, Database MySQL, dan Private Google Drive:

```text
                                  ┌───────────────────────────────┐
                                  │   Browser Guru / Supervisor   │
                                  │   (HTML5 / Tailwind / JS)     │
                                  └──────────────┬────────────────┘
                                                 │
                                                 │ HTTP Request (Cookie Auth)
                                                 ▼
                                  ┌───────────────────────────────┐
                                  │    Laravel 13 Web Server      │
                                  │ ───────────────────────────── │
                                  │  - Route Group & Middleware   │
                                  │  - Form Request Validation    │
                                  │  - Role & Model Policy (RBAC) │
                                  │  - Document & Version Service │
                                  └───────┬───────────────┬───────┘
                                          │               │
                  SQL Prepared Statements │               │ Google API Client (JWT)
                  (Metadata, Status, RBAC)│               │ (Binary Stream Transfer)
                                          ▼               ▼
                        ┌───────────────────┐   ┌───────────────────────────────────┐
                        │     MySQL DB      │   │     Private Google Drive API      │
                        │ ───────────────── │   │ ───────────────────────────────── │
                        │ - Single Source   │   │ - Private School Drive (WS / Gmail)│
                        │   of Truth        │   │ - Folder: Tahun_Ajaran            │
                        │ - documents       │   │   └── Folder: Guru (NIP & Nama)   │
                        │ - doc_versions    │   │       └── Folder: Kategori (RPP)  │
                        │ - doc_reviews     │   │ - Immutable Files: v1, v2, v3     │
                        │ - audit_logs      │   │ - Zero Public Direct Access       │
                        └───────────────────┘   └───────────────────────────────────┘
```

---

## 26. RINGKASAN PENYELESAIAN TUGAS

Dokumen integrasi repositori berkas dan penyimpanan cloud Google Drive telah selesai disusun secara komprehensif dalam berkas:
**`d:\anti\portal-sekolah-prd\03-document-google-drive.md`**

Dokumen ini menjadi dasar spesifikasi teknis operasional dan keamanan sebelum melangkah ke blueprint berikutnya (**Prompt 04: Security & Operations Specification**).
