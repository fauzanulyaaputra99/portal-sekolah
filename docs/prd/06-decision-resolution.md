# DOKUMEN DISCOVERY & RESOLUSI KEPUTUSAN ARSITEKTUR & PRODUK (ADR)
## SISTEM PORTAL SEKOLAH

**Nomor Dokumen:** ADR-SEKOLAH-006  
**Referensi Dokumen:**
* PRD-SEKOLAH-001 (`01-product-requirements.md`) — *Kebutuhan Produk & Fungsional*
* ARCH-SEKOLAH-002 (`02-architecture-database.md`) — *Arsitektur Aplikasi & Skema MySQL*
* ARCH-SEKOLAH-003 (`03-document-google-drive.md`) — *Integrasi Repositori Dokumen & Private Google Drive*
* SEC-OPS-SEKOLAH-004 (`04-security-operations.md`) — *Spesifikasi Keamanan Sistem & Operasional*
* PRD-CONSOLIDATED-005 (`05-consolidated-prd.md`) — *Consolidated Pre-Development Blueprint*

**Versi Dokumen:** 2.2.0 (FINAL — ADDENDUM + Keputusan A1/B2/C1 disahkan)  
**Status Dokumen:** APPROVED FOR DEVELOPMENT — ADR 01–03 `[OBSOLETE]`, ADR 04–17 `[FINAL]` (C1), ADR 18 `[FINAL — ADDENDUM + A1]`, ADR 19 `[FINAL — ADDENDUM]`, ADR 20 `[FINAL — B2]`  
**Tanggal:** 2026-09-25 · **ADDENDUM diterapkan 2026-09-27 · Keputusan A1/B2/C1 disahkan 2026-09-27**  
**Role Penyusun:** Lead System Analyst, Enterprise Solutions Architect, Security Architect, Database Specialist  

---

## 1. PENDAHULUAN & TUJUAN DISCOVERY

Dokumen ini menyajikan **hasil penelusuran menyeluruh (Full Discovery & Analytical Resolution)** terhadap seluruh keputusan terbuka, parameter yang belum final, opsi arsitektur alternatif, dan kebijakan yang membutuhkan ketetapan manusia (*Human Decision*) pada proyek **Portal Sekolah**.

Tujuan utama dokumen ini:
1. Mengidentifikasi seluruh parameter yang belum diputuskan dari dokumen fondasi (`01`, `02`, `03`, `04`, dan `05`).
2. Melakukan analisis komparatif yang obyektif, mendalam, dan terstruktur tanpa memaksakan keputusan sepihak (*No Silent Resolution*).
3. Memberikan rekomendasi teknis berbasis konteks sekolah menengah tanpa melakukan *over-engineering*.
4. Menyiapkan matriks keputusan yang siap ditinjau dan disahkan oleh pimpinan sekolah dan tim teknis sebelum fase koding dimulai.
5. Menerapkan aturan final dari **ADDENDUM — ATURAN MODE MASUK/PULANG & PERUBAHAN JADWAL PIKET** (2026-09-25) pada dokumen `01`, `02`, `04`, `05`, dan `06`: mode scanner eksplisit, jadwal piket berbasis database, dan penghapusan total `teacher_attendances` (ADDENDUM §20 & §26).

---

## 2. METODOLOGI DISCOVERY & REKONSILIASI KEPUTUSAN

Proses discovery dilakukan melalui dua tahap inspeksi:
* **Tahap 1 — Pencarian Literal:** Memindai seluruh kemunculan penanda eksplisit `[DECISION REQUIRED]`, `[TO BE VERIFIED]`, `TBD`, `TBC`, `OPTION A`, `OPTION B`, dan `OPTION C`.
* **Tahap 2 — Penelusuran Semantik (Semantic Review):** Memeriksa kalimat berklausul "belum ditentukan", "tergantung kebijakan sekolah", "opsional", "jika diperlukan", serta kontradiksi atau pilihan arsitektur yang tersirat pada dokumen sumber.

### 2.1 Hasil Rekonsiliasi Terhadap 14 Keputusan di 05-consolidated-prd.md
Pada dokumen `05-consolidated-prd.md`, telah tercatat 14 keputusan awal (ADR 01 s.d. ADR 14). Melalui proses audit discovery komprehensif terhadap dokumen `01` s.d. `04`, ditemukan:
* **14 Keputusan Terkenal (Known Decisions):** Seluruhnya berhasil ditemukan kembali; **ADR 04–17 kini berstatus `[FINAL]`** (pengesahan C1, 2026-09-27 — opsi terpilih sesuai `05-consolidated-prd.md` §35; isi keputusan tidak diubah, hanya status yang disinkronkan), dengan catatan **ADR 01, ADR 02, dan ADR 03 berstatus `[OBSOLETE]`** karena fitur Presensi Mandiri Guru (`teacher_attendances`) dihapus total oleh ADDENDUM §20 & §26.

> [!NOTE]
> **KONFLIK STATUS ADR 04–17 = CLOSED (Keputusan C1, 2026-09-27):** Sebelumnya dokumen ini mencatat `[UNRESOLVED]` sementara `05` §35 mencatat `[FINAL]`. Konflik telah diputuskan oleh manusia: **ADR 04–17 dinyatakan resmi FINAL**, dan seluruh status di dokumen ini disinkronkan accordingly. Pilihan/angka yang sudah menjadi dasar dokumen 01–05 dipertahankan.
* **3 Keputusan Tambahan Baru (New Discovered Decisions):**
  1. **ADR 15 — Format Identifier Kunci Primer Publik (BIGINT vs UUID v4):** Ditemukan pada `02-architecture-database.md` Bab 8.1 baris 315.
  2. **ADR 16 — Kebijakan Retensi Arsip Cadangan Akhir Semester:** Ditemukan pada `02` Bab 18.1, `04` Bab 11.1 baris 269, dan `05` Bab 25.1 baris 701.
  3. **ADR 17 — Hak Akses Peninjauan Audit Log Sistem untuk Supervisor:** Ditemukan pada `01-product-requirements.md` Bab 5.4 tabel RBAC baris 181.
* **3 Keputusan Addendum (ADDENDUM 2026-09-25 — Aturan Final WAJIB; disahkan penuh 2026-09-27):**
  1. **ADR 18 — Mode Scanner Masuk/Pulang Eksplisit:** Mode dipilih manual oleh Guru Piket; logika auto-detect (scan pertama/scan kedua) DILARANG (ADDENDUM §1–§7); aturan turunan **Keputusan A1 (FINAL): PULANG tanpa MASUK DITOLAK**.
  2. **ADR 19 — Jadwal Guru Piket Berbasis Data:** Tabel `teacher_duty_schedules` dikelola Admin/TU; server mengecek jadwal pada setiap request scanner (ADDENDUM §8–§18).
  3. **ADR 20 — Kebijakan Permissions-Policy Kamera Browser:** **Keputusan B2 (FINAL): `camera=(self)`** disahkan; `camera=()` ditolak; tanpa whitelist device kompleks.

Dengan demikian, dokumen kini menyajikan **20 Keputusan**: 3 `[OBSOLETE]` (ADR 01–03) dan 17 `[FINAL]` (ADR 04–20; ADR 18 diperkuat Keputusan A1, ADR 20 = Keputusan B2). **Tidak ada lagi keputusan terbuka.**

---

## 3. MASTER DECISION INVENTORY TABLE

| ID ADR | Topik Keputusan Terbuka | Dokumen Sumber Primer | Tipe Penemuan | Kategori Domain | Prioritas Dampak | Status Keputusan |
| :--- | :--- | :--- | :--- | :--- | :---: | :---: |
| **ADR 01** | Mekanisme Penandaan Status ALPA Presensi Guru (fitur dihapus — ADDENDUM) | `02` §22, `05` §35 | Explicit Decision | Operations | **HIGH** | `[OBSOLETE]` |
| **ADR 02** | Batas Toleransi Jam Masuk Presensi Guru (fitur dihapus — ADDENDUM) | `01` §23.1, `02` §22 | Explicit Decision | Product / Policy | **MEDIUM** | `[OBSOLETE]` |
| **ADR 03** | Batasan Jaringan IP untuk Presensi Guru (fitur dihapus — ADDENDUM) | `02` §22, `05` §35 | Explicit Decision | Security / Network | **HIGH** | `[OBSOLETE]` |
| **ADR 04** | Hak Akun dengan Peran Ganda (Guru & Supervisor) | `02` §4.2, `02` §22 | Explicit Decision | Architecture / RBAC | **HIGH** | `[FINAL]` |
| **ADR 05** | Otorisasi Pembatalan Dokumen yang Telah Disetujui | `01` §23.2, `02` §22 | Explicit Decision | Product / Workflow | **MEDIUM** | `[FINAL]` |
| **ADR 06** | Model Akun Google Drive (Workspace Shared Drive vs Standar) | `03` §3.2, `03` §24 | Explicit Decision | Cloud Storage | **HIGH** | `[FINAL]` |
| **ADR 07** | Batas Maksimal Ukuran Unggah Berkas Dokumen | `03` §8.3, `03` §24 | Configuration Decision | Storage / Infrastructure | **MEDIUM** | `[FINAL]` |
| **ADR 08** | Kebijakan Retensi Berkas Fisik Versi Lama di Drive | `03` §13, `03` §24 | Policy Dependent | Cloud Storage / Policy | **HIGH** | `[FINAL]` |
| **ADR 09** | Ruang Lingkup Otorisasi Supervisi Dokumen Guru | `01` §23.6, `03` §24 | Explicit Decision | Product / RBAC | **HIGH** | `[FINAL]` |
| **ADR 10** | Mekanisme Pemulihan Akun / Reset Password Pengguna | `01` §23.4, `04` §21 | Explicit Decision | Security / Operations | **HIGH** | `[FINAL]` |
| **ADR 11** | Batas Waktu Inactivity Timeout Sesi Login | `04` §5.1, `04` §21 | Configuration Decision | Security / Web | **MEDIUM** | `[FINAL]` |
| **ADR 12** | Target Batas Waktu Pemulihan Bencana (RPO & RTO) | `04` §12, `04` §21 | Policy Dependent | Operations / BCP | **HIGH** | `[FINAL]` |
| **ADR 13** | Integrasi Antivirus Engine Lokal (ClamAV) | `04` §21, `05` §35 | Explicit Decision | Security / Infrastructure | **HIGH** | `[FINAL]` |
| **ADR 14** | Batas Waktu Auto-Lock Sesi Absensi Siswa | `01` §12.2, `01` §23.3 | Configuration Decision | Operations / Workflow | **MEDIUM** | `[FINAL]` |
| **ADR 15** | Format Identifier Kunci Primer Publik (BIGINT vs UUID v4) | `02` §8.1 baris 315 | Alternative Choice | Architecture / DB | **HIGH** | `[FINAL]` |
| **ADR 16** | Kebijakan Retensi Arsip Cadangan Akhir Semester | `04` §11.1, `05` §25.1 | Policy Dependent | Operations / Storage | **LOW** | `[FINAL]` |
| **ADR 17** | Hak Akses Peninjauan Audit Log Sistem untuk Supervisor | `01` §5.4 baris 181 | Open Question | Security / RBAC | **LOW** | `[FINAL]` |
| **ADR 18** | Mode Scanner Absensi Masuk/Pulang (Eksplisit vs Auto-Detect) | ADDENDUM §1–§7, `01` §13 | Addendum Rule | Product / Workflow | **HIGH** | `[FINAL — ADDENDUM]` |
| **ADR 19** | Jadwal Guru Piket Berbasis Data (Duty Schedule) | ADDENDUM §8–§18, `02` §10 | Addendum Rule | Architecture / RBAC | **HIGH** | `[FINAL — ADDENDUM]` |
| **ADR 20** | Kebijakan Permissions-Policy Kamera Browser (Scanner Barcode) | `04` §5.3, `05` §22, ADDENDUM §25 | Conflict Flag | Security / Web | **HIGH** | `[FINAL — B2]` |

---

## 4. ANALISIS MENDALAM SETIAP KEPUTUSAN ARSITEKTUR (ADR 01 — ADR 20)

> [!NOTE]
> Kutipan *"Exact Source Context"* pada bab ini adalah **snapshot dokumen pada saat penemuan (pra-finalisasi)**. Penanda `[DECISION REQUIRED]` yang dikutip telah berstatus `[FINAL]`/`[CLOSED]` pasca pengesahan A1/B2/C1 (2026-09-27) dan tidak lagi terdapat di dokumen sumber. Rekomendasi analis yang tercantum bersifat historis; hasil final yang berlaku adalah yang terdokumentasi di dokumen 01–05 (lihat catatan ratifikasi pada §7).

---

### ADR 01 — Mekanisme Penandaan Status ALPA Presensi Guru  *(`[OBSOLETE]` — Fitur Dihapus Total oleh ADDENDUM)*

* **Status:** `OBSOLETE — SUPERSEDED BY ADDENDUM (Teacher Attendance HAPUS TOTAL)`
* **Discovery Type:** EXPLICIT DECISION
* **Source:** `01-product-requirements.md` §13, `02-architecture-database.md` §22 (ADR 01), `05-consolidated-prd.md` §35
* **Exact Source Context:**  
  > *"Bagaimana sistem menetapkan status 'ALPA' pada guru yang tidak hadir dan tidak mencatat presensi mandiri? Opsi A: Scheduled Cron Job Otomatis pukul 16:00 WIB. Opsi B: Tindakan Manual Staf Tata Usaha."* (`02-architecture-database.md`)
* **Problem:**  
  Guru yang tidak hadir dan tidak mengabarkan izin/sakit pada hari kerja tidak akan memiliki record di tabel `teacher_attendances`. Jika sistem membiarkan baris kosong, rekap kehadiran bulanan akan bias atau harus mengandalkan operasi `OUTER JOIN` kalender yang berat. Namun jika sistem mengisi status `ALPA` secara otomatis, ada risiko *false ALPA* bagi guru yang sebenarnya hadir di sekolah tetapi lupa mengklik tombol presensi di portal.
* **Current Requirement:** Guru melakukan absensi mandiri harian 1 kali per hari kerja.
* **Why This Is Still Open:** Belum ada kesepakatan tertulis mengenai toleransi kelupaan guru dan beban kerja operasional staf TU dalam memverifikasi ketidakhadiran fisik.
* **Option A: Scheduled Task / Cron Job Otomatis Setiap Sore Hari Kerja**  
  *Deskripsi:* Scheduler menjalankan perintah otomatis (contoh pukul 16:00 WIB pada hari Senin-Jumat). Sistem mengambil seluruh guru aktif yang belum memiliki record presensi pada tanggal tersebut, lalu meng-insert record dengan status `'ALPA'`.  
  *Pros:* Otomatis, menjamin seluruh guru memiliki record lengkap setiap hari kalender kerja, rekap laporan bulanan sangat cepat dihitung.  
  *Cons:* Risiko *false ALPA* tinggi jika guru lupa presensi atau terkendala koneksi internet sekolah; membutuhkan kalender hari libur nasional/sekolah agar tidak menandai ALPA pada hari libur.
* **Option B: Verifikasi & Penetapan Manual oleh Staf Tata Usaha (Admin)**  
  *Deskripsi:* Sistem tidak membuat baris otomatis. Pada dashboard Admin TU disediakan tab *"Guru Belum Presensi Hari Ini"*. Staf TU memeriksa fisik guru di ruang kelas/guru, lalu secara manual menetapkan status (`ALPA`, `IZIN`, atau `HADIR SUSULAN`) dengan mencantumkan alasan tertulis.  
  *Pros:* Akurat secara faktual di lapangan, mencegah gesekan antar guru dan pihak manajemen sekolah akibat salah penandaan sistem, tidak memerlukan logic kalender libur yang rumit pada MVP.  
  *Cons:* Bergantung penuh pada kedisiplinan dan rutinitas harian staf TU; jika TU lalai, data absensi menjadi menggantung (*missing entries*).
* **Option C: Hybrid (Pengingat Otomatis Siang Hari + Auto-Lock ALPA Tengah Malam)**  
  *Deskripsi:* Sistem memberikan alert in-app pada pukul 12:00 WIB bagi guru yang belum presensi. Pada pukul 23:59 WIB, record yang masih kosong otomatis ditandai `ALPA` dengan flag `is_automated = true`.  
  *Pros:* Memberikan waktu bagi guru untuk melapor sebelum hari berganti.  
  *Cons:* Memerlukan logic scheduler tambahan.
* **Technical & Architecture Impact:** Opsi A dan C mewajibkan konfigurasi task scheduling pada server aplikasi. Opsi B murni transaksi UI standard.
* **Operational Impact:** Opsi B menambah tugas harian staf TU sebesar 10-15 menit per hari.
* **Security & Audit Impact:** Seluruh penetapan atau perubahan status wajib tercatat pada `audit_logs`.
* **Dependencies:** Terkait langsung dengan ADR 02 (Batas Jam Masuk), ADR 03 (Batasan IP), dan fitur Laporan Presensi Guru.
* **Recommended Direction:** **Option B untuk fase awal MVP**, dengan transisi ke **Option A** setelah sistem memiliki data referensi kalender akademik/libur yang matang.
* **Reason for Recommendation:** Menghindari kepanikan administratif dan komplain guru pada bulan-bulan awal adopsi aplikasi.
* **Human Decision Required:** Pimpinan sekolah harus memutuskan apakah staf TU diberi tanggung jawab verifikasi harian (Opsi B) atau sistem mengunci otomatis berbasis cron (Opsi A).
* **Final Decision:** `[CLOSED — OBSOLETE]` — Fitur `teacher_attendances` dihapus total (ADDENDUM §20 & §26). Tidak ada lagi absensi mandiri guru di Portal Sekolah; status ALPA guru tidak lagi relevan.

---

### ADR 02 — Batas Toleransi Jam Masuk Presensi Guru (Late Cutoff)  *(`[OBSOLETE]` — Fitur Dihapus Total oleh ADDENDUM)*

* **Status:** `OBSOLETE — SUPERSEDED BY ADDENDUM (Teacher Attendance HAPUS TOTAL)`
* **Discovery Type:** EXPLICIT DECISION / POLICY DEPENDENT
* **Source:** `01-product-requirements.md` §7.4 & §23.1, `02-architecture-database.md` §22 (ADR 02), `05-consolidated-prd.md` §35
* **Exact Source Context:**  
  > *"Pukul berapa batas toleransi kedatangan guru dinyatakan TERLAMBAT? Opsi A: 07.00 WIB tepat. Opsi B: 07.15 WIB (grace period 15 menit). Opsi C: Dinamis berbasis konfigurasi tabel settings."* (`01-product-requirements.md`)
* **Problem:**  
  Sistem membutuhkan angka jam server yang deterministik untuk membedakan apakah presensi mandiri guru tercatat sebagai `HADIR` (tepat waktu) atau `TERLAMBAT`.
* **Current Requirement:** Status mencakup `HADIR` dan `TERLAMBAT`.
* **Why This Is Still Open:** Kebijakan jam operasional bel masuk dan toleransi keterlambatan belum disahkan oleh Kepala Sekolah.
* **Option A: Pukul 07:00:00 WIB Tepat (Tanpa Grace Period)**  
  *Pros:* Menegakkan disiplin kehadiran ketat sesuai jam bel masuk kelas.  
  *Cons:* Tidak fleksibel terhadap dinamika kemacetan jalan raya atau antrean perangkat di ruang guru.
* **Option B: Pukul 07:15:00 WIB (Grace Period 15 Menit)**  
  *Pros:* Sangat realistis untuk operasional sekolah menengah; memberikan kompensasi wajar kendala perjalanan guru.  
  *Cons:* Berisiko dimanfaatkan sebagai jam masuk efektif baru oleh sebagian staf.
* **Option C: Parameter Dinamis via Menu Pengaturan Sekolah (Admin Settings)**  
  *Pros:* Fleksibel; dapat diubah sewaktu-waktu oleh Admin/Kepala Sekolah (misal penyesuaian jam masuk saat bulan Ramadan) tanpa menyentuh kode program.  
  *Cons:* Memerlukan pembuatan tabel `settings` dan antarmuka konfigurasi di panel Admin.
* **Technical Impact:** Opsi A dan B dapat didefinisikan sebagai konstanta konfigurasi di `.env` / `config/school.php`. Opsi C memerlukan tabel database.
* **Operational Impact:** Mempengaruhi perhitungan akumulasi jam kerja dan pelaporan evaluasi guru bulanan.
* **Dependencies:** Terkait dengan ADR 01 (Status ALPA).
* **Recommended Direction:** **Option C (Dinamis)** dengan nilai bawaan awal disetel ke **07:15:00 WIB (Option B)**.
* **Reason for Recommendation:** Sekolah membutuhkan fleksibilitas musiman (seperti jam masuk khusus Ramadan atau ujian semester) tanpa harus meminta developer mengubah source code.
* **Human Decision Required:** Konfirmasi persetujuan jam batas default dari Kepala Sekolah.
* **Final Decision:** `[CLOSED — OBSOLETE]` — Fitur Presensi Mandiri Guru dihapus total (ADDENDUM §20 & §26); batas toleransi jam masuk guru tidak lagi relevan.

---

### ADR 03 — Batasan Jaringan IP untuk Presensi Mandiri Guru  *(`[OBSOLETE]` — Fitur Dihapus Total oleh ADDENDUM)*

* **Status:** `OBSOLETE — SUPERSEDED BY ADDENDUM (Teacher Attendance HAPUS TOTAL)`
* **Discovery Type:** EXPLICIT DECISION
* **Source:** `02-architecture-database.md` §22 (ADR 03), `05-consolidated-prd.md` §35
* **Exact Source Context:**  
  > *"Apakah presensi mandiri guru dibatasi hanya dapat dilakukan saat perangkat terhubung ke Wi-Fi / IP publik sekolah? Opsi A: Bebas Jaringan. Opsi B: Validasi Subnet / IP Publik Sekolah."* (`02-architecture-database.md`)
* **Problem:**  
  Aplikasi dapat diakses via web browser di internet. Jika tidak dibatasi, guru berpotensi melakukan presensi dari rumah atau perjalanan sebelum tiba di lokasi fisik sekolah.
* **Current Requirement:** Sistem mencatat `ip_address` dan `device_info` pada setiap transaksi presensi.
* **Why This Is Still Open:** Sekolah belum mengonfirmasi apakah memiliki sambungan internet dengan IP publik statis yang andal dan jangkauan Wi-Fi yang memadai untuk seluruh guru.
* **Option A: Bebas Jaringan (Perekaman IP Informatif Tanpa Pemblokiran)**  
  *Deskripsi:* Guru dapat mengklik tombol presensi dari koneksi internet manapun (termasuk kuota seluler pribadi). Alamat IP hanya dicatat ke database untuk kebutuhan audit forensik.  
  *Pros:* Zero failure akibat gangguan Wi-Fi sekolah; guru tetap bisa presensi meskipun internet sekolah sedang padam atau bandwidth penuh.  
  *Cons:* Kehadiran fisik tidak terjamin 100% (potensi guru presensi dari luar sekolah).
* **Option B: Pembatasan Subnet IP Publik Sekolah (Enforced IP Whitelist)**  
  *Deskripsi:* Tombol presensi hanya dapat disubmit jika request berasal dari subnet/IP publik resmi sekolah. Akses dari IP luar menghasilkan penolakan otorisasi (HTTP 403).  
  *Pros:* Menjamin presensi hanya terjadi saat guru telah berada di dalam lingkungan jaringan sekolah.  
  *Cons:* Gagal total jika sekolah menggunakan IP publik dinamis yang sering berubah; guru tidak bisa presensi jika Wi-Fi sekolah mengalami gangguan teknis; potensi komplain tinggi.
* **Technical Impact:** Opsi B membutuhkan middleware `EnsureSchoolNetworkIp` dan manajemen whitelist IP di database/konfigurasi.
* **Operational Impact:** Mengharuskan staf IT memelihara kestabilan jaringan internet sekolah setiap pagi hari.
* **Dependencies:** Terkait erat dengan ADR 01 dan ADR 02.
* **Recommended Direction:** **Option A untuk fase MVP**, dengan pelaporan audit IP yang transparan di dashboard Admin.
* **Reason for Recommendation:** Infrastruktur jaringan sekolah menengah sering kali memiliki keterbatasan kapasitas AP Wi-Fi dan stabilitas ISP. Pemblokiran IP pada rilis awal berisiko tinggi menimbulkan kekacauan operasional.
* **Human Decision Required:** Pimpinan sekolah harus memutuskan apakah faktor kepraktisan (Opsi A) lebih diutamakan daripada kontrol lokasi fisik ketat (Opsi B).
* **Final Decision:** `[CLOSED — OBSOLETE]` — Batasan jaringan IP presensi mandiri guru tidak lagi relevan (fitur dihapus total). Cadangan: mode scanner tetap mencatat `ip_address`/`device_info` informatif untuk audit (ADDENDUM §23), tanpa pemblokiran.

---

### ADR 04 — Hak Akses pada Akun dengan Peran Ganda (Guru Merangkap Supervisor)

* **Status:** `FINAL — DISAHKAN 2026-09-27`
* **Discovery Type:** EXPLICIT DECISION
* **Source:** `02-architecture-database.md` §4.2 baris 159 & §22 (ADR 04), `05-consolidated-prd.md` §35
* **Exact Source Context:**  
  > *"Jika seorang pengguna memiliki peran ganda (contoh: Guru yang juga menjadi Wakil Kepala Sekolah/Supervisor), peran operasional didefinisikan pada profil yang bersangkutan. Opsi A: Multi-Peran dalam 1 Akun. Opsi B: Dua Akun Terpisah."* (`02-architecture-database.md`)
* **Problem:**  
  Di sekolah menengah, pejabat struktural (seperti Wakil Kepala Sekolah Bidang Kurikulum) sering kali tetap mengajar mata pelajaran tertentu di kelas. Mereka wajib mengunggah RPP sebagai Guru, sekaligus memiliki wewenang menyetujui dokumen guru lain sebagai Supervisor.
* **Current Requirement:** Kolom `users.role` menggunakan enum tunggal (`'admin', 'teacher', 'supervisor'`).
* **Why This Is Still Open:** Belum diputuskan apakah kompleksitas peran ganda diselesaikan pada level model database (relasi many-to-many) atau level kebijakan akun (2 akun terpisah).
* **Option A: Satu Akun dengan Fitur Pergantian Peran (Role Switcher / Context Switching)**  
  *Deskripsi:* User hanya memiliki 1 pasang kredensial login. Setelah login, sistem menyediakan dropdown di navbar untuk beralih konteks antara mode "Guru" dan mode "Supervisor", yang disimpan pada session `active_role`.  
  *Pros:* Pengguna tidak perlu menghafal dua username/password dan tidak perlu logout-login berulang kali.  
  *Cons:* Membutuhkan modifikasi arsitektur relasi user-role menjadi many-to-many atau penambahan tabel perantara `user_roles`; middleware wajib mengevaluasi `session('active_role')`.
* **Option B: Dua Akun Terpisah (Separation of Accounts)**  
  *Deskripsi:* Pejabat terkait diberikan dua akun yang terisolasi: Akun NIP untuk menjalankan fungsi Guru (mengajar, absensi, upload RPP), dan Akun Khusus (misal: `waka.kurikulum`) untuk fungsi Supervisi.  
  *Pros:* Menjaga kesederhanaan arsitektur basis data (kolom `users.role` tetap tunggal); audit trail sangat bersih dan tidak ambigu; mencegah konflik kepentingan (misal Waka tidak sengaja meng-approve dokumen miliknya sendiri).  
  *Cons:* Pejabat bersangkutan harus mengingat dua kredensial dan melakukan login ulang pada tab terpisah.
* **Technical & Database Impact:** Opsi A berdampak signifikan pada skema database dan seluruh Laravel Policy. Opsi B tidak membutuhkan perubahan skema kode sama sekali.
* **Security Impact:** Opsi B secara inheren menerapkan prinsip *Least Privilege* dan *Separation of Duties*.
* **Recommended Direction:** **Option B untuk fase MVP**.
* **Reason for Recommendation:** Sesuai filosofi anti-overengineering. Jumlah guru yang merangkap jabatan di sekolah biasanya sangat sedikit (hanya 2-4 orang), sehingga tidak sebanding dengan kompleksitas perombakan skema RBAC multi-role.
* **Human Decision Required:** Pimpinan sekolah mengonfirmasi apakah pejabat merangkap bersedia menggunakan dua akun terpisah.
* **Final Decision:** `[FINAL]` — opsi terpilih sesuai `05-consolidated-prd.md` §35 (pengesahan C1, 2026-09-27).

---

### ADR 05 — Otorisasi Pembatalan (Revocation) Dokumen yang Telah Disetujui

* **Status:** `FINAL — DISAHKAN 2026-09-27`
* **Discovery Type:** EXPLICIT DECISION
* **Source:** `01-product-requirements.md` §9.2 baris 381 & §23.2, `02-architecture-database.md` §22 (ADR 05), `05-consolidated-prd.md` §35
* **Exact Source Context:**  
  > *"Apakah Atasan/Admin diizinkan membatalkan status 'DISETUJUI' pada berkas perangkat ajar guru jika ditemukan kekeliruan fatal? Opsi A: Tidak Bisa Dibatalkan. Opsi B: Bisa Dibatalkan Khusus Pimpinan."* (`02-architecture-database.md`)
* **Problem:**  
  Secara konseptual, status `DISETUJUI` adalah kondisi terminal (dokumen terkunci). Namun dalam praktik riil, terkadang dokumen yang sudah disetujui Supervisor ditemukan memiliki plagiasi atau kesalahan silabus fatal saat diperiksa oleh Pengawas Dinas.
* **Current Requirement:** Status `DISETUJUI` mengunci dokumen dari aksi perubahan guru.
* **Why This Is Still Open:** Menyeimbangkan prinsip immutability audit dengan kebutuhan penanganan kekeliruan administratif di dunia nyata.
* **Option A: Status 'DISETUJUI' Bersifat Terminal Permanen (Mutlak Tidak Bisa Dibatalkan)**  
  *Pros:* Integritas state machine linier dan sederhana; tidak ada celah penyalahgunaan wewenang untuk menganulir persetujuan sepihak.  
  *Cons:* Jika terjadi kekeliruan fatal, guru terpaksa membuat dokumen baru dari nol sehingga merusak kontinuitas pelaporan dan penomoran versi.
* **Option B: Pembatalan Khusus Pimpinan (Approval Revocation dengan Audit Ketat)**  
  *Pros:* Fleksibel; Kepala Sekolah atau Admin TU dapat menurunkan status dari `DISETUJUI` kembali ke `PERLU_REVISI` dengan kewajiban mencantumkan nomor memo/berita acara pembatalan.  
  *Cons:* Memerlukan penambahan action endpoint `RevokeDocumentApprovalAction`, modifikasi `DocumentPolicy`, dan penanganan pencatatan audit log pembatalan.
* **Technical Impact:** Opsi B menambah 1 endpoint controller dan 1 form modal alasan di antarmuka Admin/Pimpinan.
* **Audit Impact:** Wajib mencatat record `DOC_APPROVAL_REVOKED` pada `audit_logs` dengan snapshot metadata lama dan baru.
* **Recommended Direction:** **Option B**.
* **Reason for Recommendation:** Praktik administrasi pendidikan selalu membutuhkan pintu darurat resmi (*administrative override*) untuk mengakomodasi temuan pengawas tanpa harus merusak struktur arsip tahun ajaran.
* **Human Decision Required:** Penetapan hak eksekusi pembatalan: apakah hanya Kepala Sekolah, ataukah Admin TU juga berhak atas disposisi tertulis.
* **Final Decision:** `[FINAL]` — opsi terpilih sesuai `05-consolidated-prd.md` §35 (pengesahan C1, 2026-09-27).

---

### ADR 06 — Model Akun Google Drive Organisasi Sekolah

* **Status:** `FINAL — DISAHKAN 2026-09-27`
* **Discovery Type:** EXPLICIT DECISION
* **Source:** `03-document-google-drive.md` §3.2 baris 97 & §24 (ADR 06), `05-consolidated-prd.md` §35
* **Exact Source Context:**  
  > *"Apakah sekolah memiliki langganan Google Workspace for Education (Shared Drive) atau hanya memiliki Akun Google/Gmail Institusi Biasa? Opsi A: Google Workspace. Opsi B: Akun Institusi Standar."* (`03-document-google-drive.md`)
* **Problem:**  
  Arsitektur penyimpanan cloud bergantung pada model kepemilikan ruang penyimpanan Google Drive yang dimiliki sekolah.
* **Current Requirement:** Service Account terkelola digunakan sebagai identitas mesin aplikasi, melayani penyimpanan secara privat.
* **Why This Is Still Open:** Status kepemilikan domain dan langganan Google Workspace for Education sekolah bersangkutan belum diverifikasi secara administratif.
* **Option A: Google Workspace for Education dengan Fitur Shared Drive (Google Tim)**  
  *Deskripsi:* Service Account ditambahkan sebagai *Content Manager* ke dalam Shared Drive resmi sekolah.  
  *Pros:* Standar enterprise; kepemilikan file berada di bawah organisasi (bukan perorangan); kuota penyimpanan terpusat institusi; kebal terhadap risiko kehilangan akses jika staf IT mutasi/pensiun.  
  *Cons:* Mensyaratkan sekolah memiliki domain resmi (`sch.id`) yang telah disetujui Google for Education.
* **Option B: Akun Google/Gmail Institusi Standar (Folder Sharing Model)**  
  *Deskripsi:* Akun Gmail sekolah membuat folder induk repositori, lalu membagikan (*share*) folder tersebut dengan hak akses *Editor* kepada email Service Account.  
  *Pros:* Dapat diimplementasikan seketika tanpa memerlukan langganan Google Workspace berbayar/terverifikasi.  
  *Cons:* Kapasitas penyimpanan bergantung pada kuota akun pemilik (15 GB gratis); kepemilikan file dapat terancam jika kredensial akun Gmail tersebut hilang atau terblokir.
* **Technical Impact:** Hanya berbeda pada konfigurasi variabel lingkungan `GOOGLE_DRIVE_SHARED_DRIVE_ID` (diisi pada Opsi A, dikosongkan pada Opsi B). Pola kode Laravel Flysystem tetap 100% identik.
* **Dependencies:** Terkait dengan ADR 07 (Ukuran Berkas) dan ADR 08 (Retensi).
* **Recommended Direction:** **Option A jika sekolah sudah memiliki Workspace for Education; Option B sebagai fallback transisi jika belum**.
* **Reason for Recommendation:** Kode aplikasi dirancang agnostik terhadap kedua opsi ini, sehingga keputusan murni berada pada kepemilikan lisensi akun sekolah.
* **Human Decision Required:** Pihak sekolah wajib mengonfirmasi ketersediaan akun Google Workspace for Education resmi sekolah.
* **Final Decision:** `[FINAL]` — opsi terpilih sesuai `05-consolidated-prd.md` §35 (pengesahan C1, 2026-09-27).

---

### ADR 07 — Batas Maksimal Ukuran Unggah Dokumen (Maximum File Size)

* **Status:** `FINAL — DISAHKAN 2026-09-27`
* **Discovery Type:** CONFIGURATION DECISION / EXPLICIT DECISION
* **Source:** `03-document-google-drive.md` §8.3 & §24 (ADR 07), `05-consolidated-prd.md` §35
* **Exact Source Context:**  
  > *"Berapa batas maksimal ukuran file yang diizinkan untuk diunggah oleh guru? Opsi A: 10 MB per berkas. Opsi B: 25 MB per berkas. Opsi C: 50 MB per berkas."* (`03-document-google-drive.md`)
* **Problem:**  
  Menetapkan batas ukuran berkas yang seimbang antara kebutuhan guru (yang sering menyisipkan gambar/diagram pada modul ajar) dengan kapasitas bandwidth internet sekolah, kecepatan streaming reverse proxy, dan alokasi memori RAM server aplikasi.
* **Current Requirement:** Default rancangan adalah 10 MB per berkas.
* **Why This Is Still Open:** Belum dilakukan pengujian beban (*load test*) koneksi internet di lingkungan server sekolah.
* **Option A: 10 MB Per Berkas (Rekomendasi Arsitektur MVP)**  
  *Pros:* Sangat aman bagi bandwidth sekolah; proses streaming reverse proxy sangat cepat; meminimalkan risiko timeout HTTP PHP; hemat kuota Google Drive.  
  *Cons:* Guru yang mengunggah modul ajar dengan banyak foto/gambar beresolusi tinggi mungkin mengalami penolakan validasi.
* **Option B: 25 MB Per Berkas**  
  *Pros:* Memberikan keleluasaan bagi guru seni budaya atau kejuruan yang menyertakan ilustrasi grafis besar tanpa perlu kompresi mandiri.  
  *Cons:* Membutuhkan penyesuaian konfigurasi runtime web server (`upload_max_filesize = 30M`, `post_max_size = 35M`); waktu tunggu upload dan preview menjadi lebih panjang.
* **Option C: 50 MB Per Berkas**  
  *Pros:* Hampir tidak ada keluhan file ditolak akibat batas ukuran.  
  *Cons:* Beban memori dan risiko timeout koneksi tinggi; membuka celah pemborosan storage dan potensi serangan upload DoS.
* **Technical Impact:** Mempengaruhi pengaturan `upload_max_filesize` di PHP dan batas buffer streaming.
* **Dependencies:** Terkait dengan performa streaming reverse proxy (`03` Bab 14) dan kapasitas kuota Drive (ADR 06).
* **Recommended Direction:** **Option A (10 MB)** untuk rilis awal MVP.
* **Reason for Recommendation:** Format dokumen teks ajar standar (PDF/DOCX) jarang melebihi 5 MB. Batas 10 MB sudah memberikan ruang kompensasi dua kali lipat yang sangat memadai.
* **Human Decision Required:** Penetapan batas megabyte resmi oleh tim kurikulum dan pengelola IT sekolah.
* **Final Decision:** `[FINAL]` — opsi terpilih sesuai `05-consolidated-prd.md` §35 (pengesahan C1, 2026-09-27).

---

### ADR 08 — Kebijakan Retensi Berkas Fisik Versi Lama di Google Drive

* **Status:** `FINAL — DISAHKAN 2026-09-27`
* **Discovery Type:** POLICY DEPENDENT / EXPLICIT DECISION
* **Source:** `03-document-google-drive.md` §13 & §24 (ADR 08), `05-consolidated-prd.md` §35
* **Exact Source Context:**  
  > *"Apakah berkas fisik dari versi lama yang berstatus 'PERLU_REVISI' (misal file v1 setelah guru mengunggah v2) tetap disimpan selamanya di Google Drive? Opsi A: Disimpan Selamanya. Opsi B: Dihapus Otomatis Pasca Persetujuan."* (`03-document-google-drive.md`)
* **Problem:**  
  Jika setiap revisi menghasilkan file fisik baru ($v1, v2, v3$), volume penyimpanan di Google Drive akan terus bertambah. Jika versi lama dihapus, histori fisik dokumen hilang; jika tidak dihapus, kuota cloud sekolah terancam cepat penuh.
* **Current Requirement:** Seluruh versi fisik disimpan utuh sampai adanya penetapan kebijakan retensi resmi sekolah.
* **Why This Is Still Open:** Sekolah belum menetapkan kebijakan jangka panjang terkait audit akreditasi (apakah asesor mewajibkan pembuktian jejak perbaikan dari versi draft ke versi final).
* **Option A: Retensi Tak Terbatas (Infinite Retention — Disimpan Selamanya)**  
  *Pros:* 100% jejak rekam supervisi akademik utuh; berkas versi lampau selalu dapat dibuka kembali untuk pembuktian akreditasi; tidak membutuhkan background job pembersihan berkas (*garbage collector*).  
  *Cons:* Membutuhkan alokasi kuota Google Drive yang besar dalam jangka panjang.
* **Option B: Pembersihan Berkas Fisik Pasca Persetujuan (Selective Purge)**  
  *Deskripsi:* Setelah dokumen versi akhir disetujui (`DISETUJUI`), berkas fisik versi lama ($v1, v2$) dihapus dari Google Drive, namun metadata histori revisi dan catatan evaluasi di MySQL tetap dipertahankan.  
  *Pros:* Menghemat kuota Google Drive hingga $50\% - 70\%$.  
  *Cons:* File biner lama tidak bisa diunduh lagi; membutuhkan scheduler pembersihan berkas dan penanganan status *missing file* yang disengaja.
* **Technical Impact:** Opsi B memerlukan pembuatan *scheduled console command* pembersihan berkas.
* **Recommended Direction:** **Option A (Infinite Retention)** selama fase MVP.
* **Reason for Recommendation:** Berkas dokumen teks berukuran kecil (rata-rata 1-3 MB), sehingga kuota cloud tidak akan habis dalam 1-2 tahun ajaran pertama. Preservasi data audit jauh lebih berharga daripada penghematan kuota kecil.
* **Human Decision Required:** Kebijakan resmi pimpinan sekolah mengenai kewajiban preservasi dokumen versi lama untuk keperluan akreditasi.
* **Final Decision:** `[FINAL]` — opsi terpilih sesuai `05-consolidated-prd.md` §35 (pengesahan C1, 2026-09-27).

---

### ADR 09 — Ruang Lingkup Otorisasi Supervisi Dokumen Guru (Supervisor Scope)

* **Status:** `FINAL — DISAHKAN 2026-09-27`
* **Discovery Type:** EXPLICIT DECISION
* **Source:** `01-product-requirements.md` §23.6, `03-document-google-drive.md` §24 (ADR 09), `05-consolidated-prd.md` §35
* **Exact Source Context:**  
  > *"Apakah seorang Supervisor dapat memeriksa dokumen seluruh guru di sekolah, ataukah dibatasi berdasarkan pembagian rumpun mata pelajaran? Opsi A: Supervisi Terbuka Sekolah. Opsi B: Supervisi Berbasis Rumpun / Penugasan Khusus."* (`03-document-google-drive.md`)
* **Problem:**  
  Di sekolah menengah dengan puluhan guru, apakah seluruh peninjau (Kepala Sekolah, Waka Kurikulum, Pengawas) melihat satu antrean bersama yang sama, ataukah antrean kerja dipecah berdasarkan spesialisasi bidang ilmu.
* **Current Requirement:** Atasan/Supervisor dapat melihat dan me-review dokumen yang diajukan.
* **Why This Is Still Open:** Struktur pembagian tugas supervisi internal sekolah belum diputuskan oleh Kepala Sekolah.
* **Option A: Supervisi Terbuka Satu Sekolah (Open School Scope)**  
  *Deskripsi:* Seluruh pengguna ber-role `supervisor` dapat melihat antrean, membuka preview, dan menyetujui dokumen dari seluruh guru di sekolah tanpa memandang mata pelajaran.  
  *Pros:* Sangat sederhana; antarmuka kerja fleksibel; tidak membutuhkan konfigurasi pembagian tugas rumit; sangat ideal untuk fase MVP.  
  *Cons:* Antrean dokumen di dashboard Supervisor menjadi panjang jika jumlah guru banyak.
* **Option B: Supervisi Terdistribusi Berbasis Rumpun / Penugasan (Scoped Supervision)**  
  *Deskripsi:* Admin menetapkan penugasan supervisi (misal: Waka A membawahi Rumpun MIPA, Waka B membawahi Rumpun IPS dan Bahasa). Supervisor hanya dapat melihat dan me-review guru di rumpunnya.  
  *Pros:* Beban kerja review terdistribusi rapi; relevansi keilmuan peninjau lebih tepat sasaran.  
  *Cons:* Memerlukan penambahan tabel pivot `supervisor_assignments` di database; meningkatkan kompleksitas logika `DocumentPolicy` secara signifikan.
* **Technical & Database Impact:** Opsi B menambah 1 tabel database dan mengubah arsitektur otorisasi.
* **Recommended Direction:** **Option A (Open School Scope)** untuk rilis awal MVP.
* **Reason for Recommendation:** Pada sekolah menengah skala normal (20-50 guru), Kepala Sekolah dan Waka Kurikulum biasanya bekerja secara kolaboratif dalam satu tim penjamin mutu sekolah.
* **Human Decision Required:** Penetapan kebijakan manajerial oleh Kepala Sekolah.
* **Final Decision:** `[FINAL]` — opsi terpilih sesuai `05-consolidated-prd.md` §35 (pengesahan C1, 2026-09-27).

---

### ADR 10 — Mekanisme Pemulihan Akun / Reset Password Pengguna

* **Status:** `FINAL — DISAHKAN 2026-09-27`
* **Discovery Type:** EXPLICIT DECISION / SECURITY DECISION
* **Source:** `01-product-requirements.md` §23.4, `04-security-operations.md` §21 (ADR 10), `05-consolidated-prd.md` §35
* **Exact Source Context:**  
  > *"Bagaimana mekanisme pemulihan kata sandi bagi guru atau staf yang lupa password? Opsi A: Pemulihan Terpusat via Staf Tata Usaha. Opsi B: Pemulihan Mandiri via Email Institusi."* (`04-security-operations.md`)
* **Problem:**  
  Guru yang lupa kata sandi membutuhkan mekanisme pemulihan yang aman namun tidak terganjal kendala teknis ketiadaan layanan pengiriman email.
* **Current Requirement:** Admin TU memiliki wewenang mereset kata sandi pengguna.
* **Why This Is Still Open:** Ketersediaan dan keandalan server SMTP / transactional email domain sekolah belum dipastikan.
* **Option A: Pemulihan Terpusat Melalui Staf Tata Usaha (Administrative Force Reset)**  
  *Deskripsi:* Guru yang lupa sandi melapor langsung ke Staf TU. Staf TU membuka panel Admin, memilih user, lalu menekan tombol *"Reset Password"*. Sistem menghasilkan sandi acak sementara (atau sandi default yang wajib diganti saat login berikutnya).  
  *Pros:* 100% mandiri tanpa ketergantungan layanan pihak ketiga (SMTP/Mailgun); tidak ada biaya langganan email gateway; kebal dari kegagalan email masuk folder spam.  
  *Cons:* Menambah beban interaksi tatap muka pada staf TU; guru harus melapor pada jam kerja sekolah.
* **Option B: Pemulihan Mandiri via Email Terdaftar (Self-Service Password Reset)**  
  *Deskripsi:* Guru menekan tautan *"Lupa Kata Sandi"* di halaman login, memasukkan email, dan sistem mengirimkan tautan reset berbatas waktu (expire 60 menit) via email.  
  *Pros:* Mandiri, guru dapat mereset sandi kapan saja (termasuk malam hari atau akhir pekan).  
  *Cons:* Mensyaratkan sekolah memiliki SMTP mail server yang aktif dan terkonfigurasi dengan reputasi DNS (SPF/DKIM/DMARC) yang baik agar tidak masuk spam; banyak guru yang jarang memeriksa email resmi.
* **Option C: Hybrid (Mandiri via Email dengan Fallback Tombol Reset oleh TU)**  
  *Deskripsi:* Menyediakan tombol reset email, sekaligus mempertahankan tombol force-reset di panel Admin TU.  
  *Pros:* Paling lengkap.  
  *Cons:* Tetap memerlukan konfigurasi SMTP server di awal.
* **Technical Impact:** Opsi B dan C mewajibkan konfigurasi driver email di `.env` dan antrean pengiriman email. Opsi A murni berbasis web logic lokal.
* **Security & Audit Impact:** Seluruh aksi reset kata sandi wajib dicatat pada `audit_logs` (`USER_PASSWORD_RESET`).
* **Recommended Direction:** **Option A untuk fase MVP**, dengan persiapan arsitektur menuju **Option C** setelah domain email sekolah stabil.
* **Reason for Recommendation:** Mencegah keterlambatan peluncuran aplikasi akibat kendala teknis pengiriman email SMTP pada server lokal sekolah.
* **Human Decision Required:** Konfirmasi kesediaan staf TU untuk menangani permohonan reset sandi guru secara manual.
* **Final Decision:** `[FINAL]` — opsi terpilih sesuai `05-consolidated-prd.md` §35 (pengesahan C1, 2026-09-27).

---

### ADR 11 — Batas Waktu Ketidakaktifan Sesi Pengguna (Session Inactivity Timeout)

* **Status:** `FINAL — DISAHKAN 2026-09-27`
* **Discovery Type:** CONFIGURATION DECISION / SECURITY DECISION
* **Source:** `04-security-operations.md` §5.1 & §21 (ADR 11), `05-consolidated-prd.md` §35
* **Exact Source Context:**  
  > *"Berapa lama batas waktu maksimal sesi login dibiarkan menganggur sebelum sistem otomatis melakukan logout? Opsi A: 60 Menit. Opsi B: 120 Menit. Opsi C: 480 Menit."* (`04-security-operations.md`)
* **Problem:**  
  Di lingkungan sekolah, komputer di ruang guru dan laboratorium sering digunakan secara bergantian (*shared workstation*). Jika sesi dibiarkan aktif terlalu lama, ada risiko pembajakan akun oleh pihak lain (*Session Hijacking*). Namun jika batas waktu terlalu singkat, guru akan mengeluh karena sesi terputus saat sedang menyusun rincian absensi kelas di tengah jam mengajar.
* **Current Requirement:** Sistem membutuhkan inactivity timeout yang sesuai dengan risiko penggunaan workstation bersama.
* **Why This Is Still Open:** Menyeimbangkan aspek kenyamanan pengalaman pengguna (*User Experience*) dengan postur keamanan fisik sekolah.
* **Option A: 60 Menit (1 Jam)**  
  *Pros:* Standar keamanan tinggi; sangat efektif memitigasi risiko pembajakan sesi pada komputer bersama ruang guru.  
  *Cons:* Guru yang mengajar 2 jam pelajaran berturut-turut akan mendapati sesinya telah kedaluwarsa saat kembali ke meja.
* **Option B: 120 Menit (2 Jam) — Rekomendasi Konsolidasi**  
  *Pros:* Titik keseimbangan ideal antara keamanan dan kenyamanan; mencakup rata-rata durasi 1 blok pertemuan jam belajar sekolah.  
  *Cons:* Jendela paparan risiko tetap ada jika komputer ditinggalkan terbuka tanpa penguncian layar.
* **Option C: 480 Menit (8 Jam / Jam Sekolah Penuh)**  
  *Pros:* Kenyamanan maksimal; guru hanya perlu login satu kali di pagi hari dan tetap aktif hingga jam pulang sekolah.  
  *Cons:* Risiko keamanan tinggi pada PC ruang guru jika pengguna lupa menekan tombol logout.
* **Technical Impact:** Nilai parameter konfigurasi pada file `config/session.php` (`'lifetime' => 120`).
* **Dependencies:** Terkait dengan mitigasi OWASP ASVS V3 (Session Management).
* **Recommended Direction:** **Option B (120 Menit)**.
* **Reason for Recommendation:** Telah menjadi standar praktis aplikasi akademik berbasis web yang ramah terhadap jadwal jam mengajar guru.
* **Human Decision Required:** Persetujuan nilai timeout dari pengelola keamanan IT sekolah.
* **Final Decision:** `[FINAL]` — opsi terpilih sesuai `05-consolidated-prd.md` §35 (pengesahan C1, 2026-09-27).

---

### ADR 12 — Target Batas Waktu Pemulihan Bencana (RPO & RTO)

* **Status:** `FINAL — DISAHKAN 2026-09-27`
* **Discovery Type:** POLICY DEPENDENT / OPERATIONS DECISION
* **Source:** `04-security-operations.md` §12 & §21 (ADR 12), `05-consolidated-prd.md` §35
* **Exact Source Context:**  
  > *"Berapa target waktu pemulihan (RTO) dan batas toleransi kehilangan data transaksi (RPO) yang disepakati sekolah? Opsi A: RPO 24 Jam & RTO 4 Jam. Opsi B: RPO 1 Jam & RTO 1 Jam."* (`04-security-operations.md`)
* **Problem:**  
  Menetapkan ekspektasi resmi pimpinan sekolah mengenai seberapa banyak data yang boleh hilang jika server utama mengalami kerusakan total, dan berapa lama staf IT diizinkan memulihkan sistem hingga hidup kembali.
* **Current Requirement:** Menggunakan pencadangan otomatis harian terjadwal.
* **Why This Is Still Open:** Terkait langsung dengan anggaran infrastruktur dan ketersediaan tenaga ahli server di sekolah.
* **Option A: Kebijakan Standar Sekolah (RPO $\le 24$ Jam, RTO $\le 4$ Jam Kerja)**  
  *Deskripsi:* Basis data dicadangkan otomatis satu kali setiap malam. Jika server crash di siang hari, data dikembalikan ke posisi backup tadi malam (data transaksi hari itu hilang dan diinput ulang manual). Pemulihan membutuhkan waktu 2-4 jam oleh staf IT.  
  *Pros:* Biaya infrastruktur nol; sangat realistis; tidak membutuhkan server standby atau lisensi software replikasi database yang mahal.  
  *Cons:* Potensi kehilangan data absensi dan dokumen yang diunggah pada hari terjadinya bencana.
* **Option B: Kebijakan High Availability (RPO $\le 1$ Jam, RTO $\le 1$ Jam)**  
  *Deskripsi:* Menerapkan *MySQL Binary Logging (binlog) continuous backup* atau replikasi aktif ke server cadangan sekunder secara real-time.  
  *Pros:* Kehilangan data hampir nol; waktu pemulihan sangat singkat.  
  *Cons:* Membutuhkan minimal 2 unit server fisik/cloud yang menyala terus-menerus, bandwidth stabil tinggi, dan keahlian administrator database tingkat lanjut.
* **Technical Impact:** Opsi A cukup dengan script automation scheduler harian. Opsi B membutuhkan arsitektur clustering/replikasi database.
* **Operational Impact:** Opsi A sangat sesuai dengan ritme kerja sekolah menengah.
* **Recommended Direction:** **Option A**.
* **Reason for Recommendation:** Portal Sekolah bukan sistem perbankan finansial real-time. Kehilangan data presensi setengah hari dapat direkonstruksi dengan mudah melalui absensi manual guru, sehingga biaya Opsi B tidak dapat dijustifikasi untuk fase MVP.
* **Human Decision Required:** Pengesahan parameter target bencana oleh Kepala Sekolah dalam dokumen kelangsungan operasional (*Business Continuity Plan*).
* **Final Decision:** `[FINAL]` — opsi terpilih sesuai `05-consolidated-prd.md` §35 (pengesahan C1, 2026-09-27).

---

### ADR 13 — Integrasi Pemindaian Antivirus Berkas Unggahan (ClamAV)

* **Status:** `FINAL — DISAHKAN 2026-09-27`
* **Discovery Type:** EXPLICIT DECISION / SECURITY DECISION
* **Source:** `04-security-operations.md` §21 (ADR 13), `05-consolidated-prd.md` §35
* **Exact Source Context:**  
  > *"Bagaimana arsitektur pemindaian keamanan berkas dokumen yang diunggah oleh pengguna ke sistem portal? Opsi A: Validasi Server-Side Ketat tanpa Antivirus Lokal. Opsi B: Validasi Server-Side ditambah Daemon ClamAV Lokal."* (`04-security-operations.md`)
* **Problem:**  
  Dokumen Word/Excel berpotensi disusupi makro jahat (*macro virus*). Validasi tipe biner (magic bytes) hanya memastikan tipe file asli, bukan mendeteksi muatan skrip berbahaya di dalam konten dokumen.
* **Current Requirement:** Validasi 3 lapis di sisi server (ekstensi, MIME, magic bytes via `finfo`) bersifat wajib. Pemeriksaan bawaan Google Drive ada namun tidak dijadikan jaminan tunggal keamanan aplikasi.
* **Why This Is Still Open:** Mempertimbangkan keterbatasan alokasi memori RAM pada server fisik atau VPS yang dimiliki sekolah.
* **Option A: Validasi Server-Side Ketat Tanpa Antivirus Lokal Tambahan pada MVP**  
  *Deskripsi:* Aplikasi memvalidasi whitelist ekstensi, MIME, dan Magic Bytes secara mendalam. Berkas langsung dipindahkan ke Private Google Drive dan tidak pernah dieksekusi di server web lokal.  
  *Pros:* Sangat ringan; menghemat kapasitas RAM server sebesar 2 hingga 4 GB; proses upload berjalan instan tanpa jeda antrean scanning.  
  *Cons:* Makro berbahaya pada berkas `.doc` atau `.xls` tidak terdeteksi secara lokal sebelum masuk ke cloud storage.
* **Option B: Validasi Server-Side Ditambah Antivirus Engine Lokal (ClamAV Daemon)**  
  *Deskripsi:* Server aplikasi menjalankan background service ClamAV (`clamd`). Setiap berkas yang masuk ke direktori `/tmp` dipindai terlebih dahulu via socket sebelum diizinkan mengalir ke Google Drive.  
  *Pros:* Lapisan proteksi malware dan trojan lebih komprehensif; meningkatkan skor kepatuhan audit keamanan institusi.  
  *Cons:* Daemon ClamAV memakan alokasi RAM server minimal 1,5 GB - 3 GB secara terus menerus; memperlambat waktu respon upload berkas; membutuhkan pembaruan basis data virus berkala (*freshclam*) yang menghabiskan kuota internet sekolah.
* **Technical & Infrastructure Impact:** Opsi B mensyaratkan spesifikasi hardware server dengan kapasitas RAM minimal 4 GB - 8 GB.
* **Recommended Direction:** **Option A untuk fase MVP**, dengan perancangan modul upload yang modular sehingga mudah disisipkan adapter ClamAV di masa depan jika server sekolah telah di-upgrade.
* **Reason for Recommendation:** Mayoritas server sekolah memiliki spesifikasi hardware terbatas. Menjalankan ClamAV pada server berspesifikasi pas-pasan berisiko menyebabkan crash sistem akibat kehabisan memori (*Out Of Memory*).
* **Human Decision Required:** Tim IT sekolah wajib memeriksa kapasitas RAM server yang tersedia sebelum memutuskan Opsi B.
* **Final Decision:** `[FINAL]` — opsi terpilih sesuai `05-consolidated-prd.md` §35 (pengesahan C1, 2026-09-27).

---

### ADR 14 — Batas Waktu Penguncian Otomatis Sesi Absensi Siswa (Auto-Lock Attendance)

* **Status:** `FINAL — DISAHKAN 2026-09-27`
* **Discovery Type:** CONFIGURATION DECISION / OPERATIONS DECISION
* **Source:** `01-product-requirements.md` §12.2 baris 461 & §23.3, `05-consolidated-prd.md` §35
* **Exact Source Context:**  
  > *"Kapan batas akhir sistem mengunci secara otomatis sesi absensi siswa yang lupa dikunci oleh guru pengampu? Opsi A: Tepat saat jam pelajaran berakhir. Opsi B: Akhir jam sekolah pukul 15.00 WIB. Opsi C: Pukul 23:59:59 pada hari yang sama."* (`01-product-requirements.md`)
* **Problem:**  
  Guru yang membuka sesi absensi di kelas terkadang lupa menekan tombol "Simpan & Kunci Sesi Absensi" karena terburu-buru berpindah ke kelas berikutnya. Jika sesi dibiarkan `OPEN`, data absensi rawan dimanipulasi di kemudian hari.
* **Current Requirement:** Sesi absensi yang telah `CLOSED` berstatus *read-only* bagi guru.
* **Why This Is Still Open:** Jam kerja dan ritme pelaporan harian tenaga pendidik membutuhkan kesepakatan manajerial.
* **Option A: Tepat Saat Jam Pelajaran Berakhir (Sesuai Jadwal Slot Mengajar)**  
  *Pros:* Disiplin waktu maksimal; tidak ada celah pengubahan data setelah kelas selesai.  
  *Cons:* Sangat kaku; jika guru mengajar di kelas tanpa sinyal internet dan baru bisa menginput data saat jam istirahat, sesi sudah terkunci otomatis.
* **Option B: Akhir Jam Operasional Sekolah (Pukul 15:30 WIB)**  
  *Pros:* Memberikan toleransi waktu bagi guru untuk menyelesaikan rekap absensi di ruang guru sebelum pulang sekolah.  
  *Cons:* Memerlukan logic jam operasional sekolah yang statis.
* **Option C: Pukul 23:59:59 pada Hari Kalender yang Sama (End of Day Auto-Lock)**  
  *Pros:* Sangat fleksibel; memberikan keleluasaan bagi guru yang baru sempat melengkapi catatan materi atau absensi dari rumah pada malam hari; paling realistis untuk ritme sekolah menengah.  
  *Cons:* Jendela waktu pengeditan terbuka hingga larut malam pada hari yang bersangkutan.
* **Technical Impact:** Dijalankan via scheduler harian Laravel:  
  `UPDATE student_attendance_sessions SET status = 'CLOSED', closed_at = NOW() WHERE status = 'OPEN' AND session_date < CURRENT_DATE;`
* **Operational Impact:** Meminimalkan beban staf TU dalam membuka kunci sesi gantung.
* **Recommended Direction:** **Option C (Pukul 23:59:59)**.
* **Reason for Recommendation:** Mencegah terjadinya konflik operasional guru yang terburu-buru mengajar di beberapa kelas dalam satu hari.
* **Human Decision Required:** Persetujuan waka kurikulum atas toleransi penginputan absensi hingga akhir hari berjalan.
* **Final Decision:** `[FINAL]` — opsi terpilih sesuai `05-consolidated-prd.md` §35 (pengesahan C1, 2026-09-27).

---

### ADR 15 — Format Identifier Kunci Primer Publik (BIGINT vs UUID v4)

* **Status:** `FINAL — DISAHKAN 2026-09-27`
* **Discovery Type:** ALTERNATIVE CHOICE / ARCHITECTURAL CHOICE
* **Source:** `02-architecture-database.md` §8.1 & `05-consolidated-prd.md` §12
* **Exact Source Context:**  
  > *"Primary Key: BIGINT UNSIGNED AUTO_INCREMENT untuk performa indexing dan efisiensi join relasi internal yang tinggi. UUID v4 dapat dipertimbangkan untuk identifier publik jika diperlukan pencegahan enumeration [DECISION REQUIRED]."* (`02-architecture-database.md`)
* **Problem:**  
  Penggunaan ID numerik integer terurut (`/documents/1`, `/classes/5`) memudahkan pengguna menebak ID rekaman lain (*ID Enumeration*). Sebaliknya, UUID v4 mengacak ID secara aman namun memakan ruang penyimpanan 16 bytes per baris dan menurunkan efisiensi indexing B-Tree pada basis data MySQL.
* **Current Requirement:** Dokumen 02 menetapkan `BIGINT UNSIGNED AUTO_INCREMENT` sebagai primary key baku di MySQL.
* **Why This Is Still Open:** Menentukan apakah mitigasi IDOR murni dibebankan pada Policy Laravel ataukah membutuhkan lapisan obfuscation UUID pada URL publik.
* **Option A: Murni BIGINT Identity / AUTO_INCREMENT di Seluruh Tabel (Termasuk Route URL)**  
  *Pros:* Performa relasi join dan pengindeksan MySQL (InnoDB) maksimal; konsumsi memori cache RAM basis data sangat hemat; kode model Laravel sangat standar dan ringkas.  
  *Cons:* Angka ID terekspos di URL browser (mitigasi penyerangan ditegakkan 100% oleh validasi Laravel Policy server-side).
* **Option B: Dual Identifier (Internal BIGINT untuk Relasi FK + Kolom UUID untuk Public Route Model Binding)**  
  *Deskripsi:* Tabel memiliki PK `id BIGINT UNSIGNED` untuk seluruh relasi Foreign Key internal, ditambah kolom `uuid CHAR(36) UNIQUE` yang digunakan pada parameter rute URL publik (`/documents/{uuid}`).  
  *Pros:* Menutup celah tebak angka URL secara total sekaligus mempertahankan efisiensi relasi internal database.  
  *Cons:* Menambah 1 kolom dan 1 index unik per tabel; sedikit menambah penulisan kode pada model Laravel (`getRouteKeyName()`).
* **Technical & Database Impact:** Opsi B menambah kompleksitas migrasi dan model, tetapi memberikan keamanan berlapis.
* **Security Impact:** Menghilangkan ancaman *URL Parameter Tampering* dan *ID Enumeration*.
* **Recommended Direction:** **Option A untuk fase MVP**, karena seluruh endpoint akses telah diproteksi ketat oleh Laravel Policies yang menolak manipulasi ID (`HTTP 403`).
* **Reason for Recommendation:** Mencegah over-engineering skema database di awal; proteksi otorisasi server-side sudah sangat solid menolak akses non-pemilik.
* **Human Decision Required:** Tim pengembang dan arsitek menyepakati apakah ekspos ID angka di URL dapat diterima selama Policy aktif.
* **Final Decision:** `[FINAL]` — opsi terpilih sesuai `05-consolidated-prd.md` §35 (pengesahan C1, 2026-09-27).

---

### ADR 16 — Kebijakan Retensi Arsip Cadangan Akhir Semester Basis Data

* **Status:** `FINAL — DISAHKAN 2026-09-27`
* **Discovery Type:** POLICY DEPENDENT / OPERATIONS DECISION
* **Source:** `02-architecture-database.md` §18.1, `04-security-operations.md` §11.1, `05-consolidated-prd.md` §25.1
* **Exact Source Context:**  
  > *"Backup akhir semester disimpan permanen untuk arsip akreditasi [DECISION REQUIRED]."* (`04-security-operations.md` / `05-consolidated-prd.md`)
* **Problem:**  
  Pencadangan rutin MySQL mencakup retensi harian (7 hari) dan mingguan (4 minggu). Belum ada ketetapan resmi mengenai berapa lama berkas snapshot penutupan semester (saat pembagian rapor) wajib disimpan secara permanen di media penyimpanan offline/arsip.
* **Current Requirement:** Cadangan harian dan mingguan berotasi otomatis.
* **Why This Is Still Open:** Regulasi sekolah mengenai masa simpan dokumen portofolio digital guru belum ditetapkan oleh tim akreditasi.
* **Option A: Disimpan Permanen Selamanya (Infinite Offline Archive)**  
  *Pros:* Data historis setiap angkatan siswa dan rekam jejak guru tidak pernah hilang; siap kapan pun dibutuhkan untuk audit akreditasi 5 tahunan.  
  *Cons:* Memerlukan media penyimpanan cadangan eksternal (harddisk eksternal atau cold storage) yang terawat rapi.
* **Option B: Disimpan Selama Siklus Akreditasi (Maksimal 5 Tahun Ajaran)**  
  *Pros:* Sesuai dengan siklus akreditasi BAN-S/M; ruang penyimpanan cadangan dapat dibersihkan secara teratur setelah masa berlakunya lewat.  
  *Cons:* Memerlukan prosedur inventarisasi pembersihan berkas arsip setiap 5 tahun sekali.
* **Technical Impact:** Berkas dump database berukuran teks terkompresi (`.sql.gz`) sangat kecil (biasanya < 50 MB per snapshot semester), sehingga beban disk sangat rendah.
* **Recommended Direction:** **Option A (Disimpan Permanen)**.
* **Reason for Recommendation:** Kapasitas snapshot database sekolah sangat hemat, sehingga tidak ada urgensi untuk menghapus data historis akademik.
* **Human Decision Required:** Kebijakan tata kelola arsip digital dari Kepala Tata Usaha.
* **Final Decision:** `[FINAL]` — opsi terpilih sesuai `05-consolidated-prd.md` §35 (pengesahan C1, 2026-09-27).

---

### ADR 17 — Hak Akses Peninjauan Audit Log Sistem untuk Role Supervisor

* **Status:** `FINAL — DISAHKAN 2026-09-27`
* **Discovery Type:** OPEN QUESTION / SECURITY RBAC
* **Source:** `01-product-requirements.md` §5.4 tabel RBAC baris 181
* **Exact Source Context:**  
  > *"Lihat Audit Log Sistem | Admin: Penuh | Guru: Tidak Ada | Supervisor: Tidak Ada [DECISION REQUIRED]"* (`01-product-requirements.md` baris 181)
* **Problem:**  
  Pada matriks RBAC dokumen 01, hak akses Supervisor terhadap audit log ditandai *Tidak Ada [DECISION REQUIRED]*. Pimpinan sekolah (Kepala Sekolah/Supervisor) mungkin memerlukan akses untuk melihat jejak rekam koreksi kehadiran yang dilakukan oleh Admin TU atau histori persetujuan dokumen guna menjaga transparansi internal.
* **Current Requirement:** Audit log saat ini hanya dapat dilihat oleh Admin/Staff TU.
* **Why This Is Still Open:** Pertimbangan apakah Kepala Sekolah sebagai Supervisor boleh meninjau log audit langsung dari portal atau cukup meminta laporan rekap dari staf TU.
* **Option A: Khusus Admin TU Saja (Supervisor: Tidak Ada Akses)**  
  *Pros:* Membatasi menu Supervisor hanya fokus pada antrean pemeriksaan dokumen ajar; antarmuka pimpinan tetap bersih dan sederhana.  
  *Cons:* Kepala Sekolah tidak memiliki visibilitas mandiri jika mencurigai adanya manipulasi data yang dilakukan oleh oknum staf administrasi.
* **Option B: Supervisor Diberikan Hak Akses Baca Penuh (Read-Only Audit Logs)**  
  *Pros:* Mendukung fungsi *check-and-balance*; Kepala Sekolah dapat mengaudit secara langsung seluruh koreksi kehadiran siswa, koreksi guru, dan perubahan data master tanpa perantara staf TU.  
  *Cons:* Menambah menu baru pada navigasi dashboard Supervisor.
* **Technical Impact:** Menambahkan pengecekan role `role:supervisor` pada middleware rute `/audit-logs`.
* **Security & Governance Impact:** Opsi B meningkatkan tata kelola akuntabilitas kelembagaan secara signifikan (*anti-fraud*).
* **Recommended Direction:** **Option B (Diberikan Hak Baca bagi Supervisor)**.
* **Reason for Recommendation:** Kepala Sekolah adalah penanggung jawab tertinggi institusi yang memiliki legitimasi penuh untuk memeriksa jejak audit operasional sekolah.
* **Human Decision Required:** Konfirmasi Kepala Sekolah apakah menghendaki akses langsung ke menu audit log.
* **Final Decision:** `[FINAL]` — opsi terpilih sesuai `05-consolidated-prd.md` §35 (pengesahan C1, 2026-09-27).

---

### ADR 18 — Mode Scanner Absensi Masuk/Pulang (Eksplisit vs Auto-Detect)

* **Status:** `RESOLVED — ADDENDUM FINAL (WAJIB)`
* **Discovery Type:** ADDENDUM RULE / PRODUCT DECISION
* **Source:** `ADDENDUM — ATURAN MODE MASUK/PULANG & PERUBAHAN JADWAL PIKET` §1–§7 & §21 (AC-SCAN-01 s.d. AC-SCAN-06, termasuk AC-SCAN-06 keputusan A1)
* **Exact Source Context:**  
  > *"Fitur scanner Absensi Masuk/Pulang tidak boleh menentukan mode secara otomatis berdasarkan jumlah scan. JANGAN menggunakan aturan: scan pertama = MASUK, scan kedua = PULANG. Gunakan mode scanner yang dipilih secara eksplisit oleh Guru Piket."* (`ADDENDUM` §1)
* **Problem:**  
  Logika *first-scan/second-scan* mencatat mode secara diam-diam tanpa eksplisit keputusan Guru Piket. Guru Piket wajib memilih mode sebelum scan agar tidak tercatat PULANG pada siswa yang baru datang, atau MASUK pada siswa yang sebenarnya pulang.
* **Current Requirement (Lama):** Fitur scanner belum ada pada dokumen 01/02/05 sebelum ADDENDUM; tidak ada mekanisme mode scanner yang ditetapkan.
* **Why This Is Still Open:** Tidak terbuka — aturan final ADDENDUM telah dimasukkan, dan pertanyaan "PULANG tanpa record MASUK" telah **disahkan sebagai Keputusan A1 (FINAL, 2026-09-27)** — lihat di bawah.
* **Option A: Auto-Detect (scan pertama = MASUK, scan kedua = PULANG)**  
  *Deskripsi:* Sistem menginfer mode dari jumlah scan siswa pada hari tersebut.  
  *Pros:* Tidak membutuhkan interaksi Guru Piket tambahan.  
  *Cons:* **DILARANG oleh ADDENDUM §1** — mode tidak eksplisit; risk salah operasi dan tidak ada kontrol Guru Piket.
* **Option B: Mode Eksplisit (Tombol [🟢 MASUK] / [🔵 PULANG])**  
  *Deskripsi:* Guru Piket wajib memilih mode sebelum membuka kamera. Mode aktif tampak jelas terus pada layar scanner. Guru dapat berpindah MASUK → PULANG tanpa logout (ADDENDUM §7).  
  *Pros:* Operasi jelas dan auditable; duplikasi dicegah dengan kombinasi validasi aplikasi + unique constraint database (ADDENDUM §3, §5); waktu menggunakan **server timestamp** bukan waktu HP (ADDENDUM §2).  
  *Cons:* Guru wajib disiplin memilih mode; UI harus menampilkan mode aktif secara menonjol terus-menerus.
* **Technical & Database Impact:**  
  - Tabel `student_school_attendances` mencatat `check_in_time`, `check_out_time`, `check_in_by_teacher_id`, `check_out_by_teacher_id`, `scan_mode_in` (`'MASUK'`), `scan_mode_out` (`'PULANG'`); unique `(student_id, attendance_date)`.  
  - PULANG tanpa record MASUK hari itu: **DITOLAK (`[FINAL — KEPUTUSAN A1]`, disahkan 2026-09-27)**.  
  - Sistem tidak pernah membuat check-in palsu dan tidak auto-create record MASUK. Penyelesaian resmi: koreksi manual Admin/TU (alasan wajib + audit `SCHOOL_ATT_CORRECT`).
* **Security & Audit Impact:** Guru bukan piket → HTTP 403; pemalsuan tanggal/user/operator/mode → ditolak server (ADDENDUM §23).
* **Recommended Direction:** **Option B (Mode Eksplisit)**.
* **Reason for Recommendation:** ADDENDUM §1–§7 bersifat WAJIB (aturan final tambahan).
* **Human Decision Required:** ~~Keputusan sekolah atas PULANG tanpa MASUK~~ **CLOSED — Keputusan A1 disahkan 2026-09-27: DITOLAK.**
* **Final Decision:** `[RESOLVED — ADDENDUM §1–§7 + KEPUTUSAN A1 (FINAL)]` Mode eksplisit; tidak ada logika auto first/second scan; duplikasi MASUK/PULANG ditolak; **PULANG tanpa record MASUK DITOLAK — tanpa check-in palsu/auto-create, jalur resmi koreksi Admin/TU + audit**; pergantian mode tanpa logout; server timestamp; histori scan immutable.

---

### ADR 19 — Jadwal Guru Piket Berbasis Data (Duty Schedule)

* **Status:** `RESOLVED — ADDENDUM FINAL (WAJIB)`
* **Discovery Type:** ADDENDUM RULE / ARCHITECTURAL CHOICE
* **Source:** `ADDENDUM` §8–§18 & §22 (AC-DUTY-01 s.d. AC-DUTY-04), `02-architecture-database.md` §10
* **Exact Source Context:**  
  > *"JANGAN membuat: users.is_piket = true. JANGAN membuat: if user == 'Fauzan'. JANGAN membuat: if today == 'Monday'. Gunakan data jadwal di database."* (`ADDENDUM` §10)
* **Problem:**  
  Hak akses scanner harus bergantung pada status Guru Piket yang berubah dinamik sesuai jadwal. Logika hardcoded (nama user / hari minggu / flag user) tidak dapat diedit oleh Admin dan violasi aturan addendum.
* **Current Requirement (Lama):** Role tetap `admin`, `teacher`, `supervisor`; Guru Piket **bukan role baru** (ADDENDUM §8).
* **Why This Is Still Open:** Tidak terbuka — aturan final telah dimasukkan oleh ADDENDUM.
* **Option A: Hardcoded / Flag User (`users.is_piket`, `if user == "Fauzan"`, `if today == "Monday"`)**  
  *Cons:* **DILARANG (ADDENDUM §10)** — perubahan jadwal memerlukan perubahan source code; tidak auditable.
* **Option B: Tabel Jadwal Database (`teacher_duty_schedules`)**  
  *Deskripsi:* Admin/TU melakukan CRUD (Tambah/Edit/Hapus/Ganti Guru/Ganti Tanggal) pada data jadwal; minimal data `schedule_date` + `teacher_id`. Status Guru Piket = jadwal + tanggal berjalan. Server mengecek jadwal pada **setiap** request scanner (ADDENDUM §12–§14); halaman scanner yang sudah terbuka tidak mempertahankan akses; histori scan tetap (operator historis immutable — ADDENDUM §15–§16); setiap perubahan jadwal diaudit di `audit_logs` (ADDENDUM §17); jadwal masa lalu dengan record absensi wajib audit dan histori tidak berubah (ADDENDUM §18).  
  *Pros:* Perubahan jadwal langsung memengaruhi hak akses scanner tanpa perubahan kode; server menjadi sumber kebenaran; transparan dan auditable.  
  *Cons:* Memerlukan CRUD + audit events tambahan; memerlukan endpoint cek jadwal per request.
* **Dependencies:** Terkait dengan ADR 18 (scanner mode) dan ADR 20 (kamera), serta kolom operator historis `student_school_attendances.check_in_by_teacher_id` / `.check_out_by_teacher_id`.
* **Recommended Direction:** **Option B (Berbasis Data)**.
* **Reason for Recommendation:** ADDENDUM §8–§18 bersifat WAJIB; rule of simplicity (ADDENDUM §24): cukup Laravel + Blade + JS kamera + Database + Barcode — tanpa WebSocket/Redis/Queue/Microservice/GPS/RFID/NFC.
* **Human Decision Required:** Konfirmasi Admin/TU sebagai tunggal pengelola jadwal piket.
* **Final Decision:** `[RESOLVED — ADDENDUM §8–§18]` Tabel `teacher_duty_schedules`; validasi server-side setiap request; perubahan jadwal berefek segera untuk akses berikutnya; histori absensi immutable; audit wajib; `teacher_attendances` = HAPUS TOTAL.

---

### ADR 20 — Kebijakan Permissions-Policy Kamera Browser (Scanner Barcode)  *(`[FINAL — KEPUTUSAN B2]` — Disahkan 2026-09-27)*

* **Status:** `FINAL — DISAHKAN 2026-09-27 (Keputusan B2)`
* **Discovery Type:** CONFLICT FLAG / SECURITY WEB
* **Source:** `04-security-operations.md` §5.3, `05-consolidated-prd.md` §22 — vs `ADDENDUM` §1–§7 & §24
* **Exact Source Context:**  
  > *"Permissions-Policy: camera=(), microphone=(), geolocation=()"* (`04-security-operations.md`) — vs ADDENDUM §1: *"[ BUKA SCANNER ]"* (scanner barcode berbasis kamera browser HP).
* **Problem:**  
  Policy keamanan lama memblokir kamera browser pada level HTTP header global (`Permissions-Policy: camera=()`). Fitur scanner barcode (ADDENDUM §1–§7, §24: Laravel + Blade + JavaScript kamera/browser) mensyaratkan akses `getUserMedia({ video: true })` pada rute scanner. Per ADDENDUM §25, konflik dengan dokumen lama **tidak boleh di-resolve diam-diam** — flag `[DECISION REQUIRED]` saat penemuan. **Konflik ini kini CLOSED: disahkan sebagai Keputusan B2 (2026-09-27).**
* **Option A: Menjaga `camera=()` Global (Blokir Total)**  
  *Pros:* Postur keamanan asal tidak berubah.  
  *Cons:* Scanner barcode kamera HP **gagal total** — violasi ADDENDUM §1.
* **Option B: `camera=(self)` pada Rute Scanner Saja**  
  *Deskripsi:* Header global tetap restriktif; rute scanner (`/guru/school-attendance-scanner`) disisipi header `Permissions-Policy: camera=(self)` sehingga kamera hanya aktif pada halaman scanner Guru Piket.  
  *Pros:* Fitur scanner berfungsi; keamanan dibatasi sesuai prinsip least privilege; page non-scanner tetap blokir kamera.  
  *Cons:* Memerlukan penyesuaian nginx/Apache per rute; konfirmasi keamanan IT sekolah.
* **Security Impact:** Halaman lain (dokumen, dashboard) tetap tanpa kamera; hanya rute scanner yang diperluas izinnya. Akses kamera tetap dikontrol berlapis: permission browser (user grant eksplisit) + otorisasi server-side Guru Piket (`teacher_duty_schedules`).
* **Recommended Direction:** **Option B (`camera=(self)`)** — diadopsi sebagai **Keputusan B2 FINAL** dan diterapkan konsisten di `04` §5.3/§19/§21 & `05` §22/§35.
* **Reason for Recommendation:** ADDENDUM §25 mengharuskan konflik ini diangkat ke permukaan (selesai — tidak lagi diam-diam); Opsi B menjaga least privilege.
* **Human Decision Required:** ~~Konfirmasi keamanan sekolah~~ **CLOSED — Keputusan B2 disahkan 2026-09-27.**
* **Final Decision:** `[FINAL — KEPUTUSAN B2]` `Permissions-Policy: camera=(self), microphone=(), geolocation=()`. DILARANG `camera=()` (scanner wajib berjalan). DILARANG whitelist device/origin yang kompleks.

---

## 5. CROSS-DECISION IMPACT ANALYSIS

Keputusan arsitektur tidak berdiri sendiri, melainkan memiliki keterikatan sistemik:

```text
                               ┌─────────────────────────┐
                               │  ADR 06: Storage Model  │
                               │ (Workspace vs Standard) │
                               └────────────┬────────────┘
                                            │
                                            ▼
┌─────────────────────────┐    ┌─────────────────────────┐    ┌─────────────────────────┐
│  ADR 07: Max File Size  │◄───┤  ADR 08: File Retention │───►│  ADR 12: Target RPO/RTO │
│ (10 MB vs 25 MB vs 50MB)│    │ (Infinite vs Purge Old) │    │   (24 Jam vs 1 Jam)     │
└────────────┬────────────┘    └─────────────────────────┘    └────────────┬────────────┘
             │                                                             │
             ▼                                                             ▼
┌─────────────────────────┐                                   ┌─────────────────────────┐
│   ADR 13: ClamAV Scan   │                                   │  ADR 16: Backup Retensi │
│ (App Only vs Local Clam)│                                   │   (Permanen vs 5 Thn)   │
└─────────────────────────┘                                   └─────────────────────────┘

─────────────────────────────────────────────────────────────────────────────────────────

┌─────────────────────────┐    ┌─────────────────────────┐    ┌─────────────────────────┐
│  ADR 02: Jam Toleransi  │───►│   ADR 01: Status ALPA   │◄───┤  ADR 03: Subnet/IP Net  │
│    (07:00 vs 07:15)     │    │  (Auto Cron vs Manual)  │    │   (Bebas vs Whitelist)  │
└─────────────────────────┘    └─────────────────────────┘    └─────────────────────────┘

─────────────────────────────────────────────────────────────────────────────────────────

┌─────────────────────────┐    ┌─────────────────────────┐    ┌─────────────────────────┐
│  ADR 04: Peran Ganda    │───►│  ADR 09: Scope Supervisi│───►│  ADR 17: Log Supervisor │
│  (1 Akun vs 2 Akun)     │    │ (Satu Sekolah vs Rumpun)│    │   (Boleh vs Dilarang)   │
└─────────────────────────┘    └─────────────────────────┘    └─────────────────────────┘
```

### Rincian Interaksi Antar Keputusan:
1. **Cluster Presensi Guru (ADR 01, 02, 03):** *(`[OBSOLETE]` — Fitur `teacher_attendances` dihapus total oleh ADDENDUM §20 & §26.)*  
   Interaksi antar ADR ini tidak lagi relevan karena Absensi Mandiri Guru tidak ada dalam arsitektur. Historis record scan tidak pernah berubah akibat perubahan jadwal (ADDENDUM §15–§16).
2. **Cluster Cloud Storage & Infrastruktur (ADR 06, 07, 08, 12, 13):**  
   Jika sekolah memilih akun institusi standar non-Workspace (ADR 06 Opsi B) dengan kuota 15 GB gratis, maka mengizinkan upload 50 MB (ADR 07 Opsi C) dan retensi selamanya (ADR 08 Opsi A) akan membuat kuota Google Drive habis dalam beberapa bulan. Sebaliknya, jika Google Workspace aktif, retensi tak terbatas sangat aman diterapkan.
3. **Cluster Otorisasi & Tata Kelola Peran (ADR 04, 09, 17):**  
   Jika Supervisor dibatasi per rumpun mata pelajaran (ADR 09 Opsi B) dan diberikan akun terpisah dari tugas mengajar (ADR 04 Opsi B), maka matriks otorisasi Laravel Policy menjadi terstruktur secara rapi per konteks sesi tanpa tumpang tindih hak akses data.
4. **Cluster Scanner & Jadwal Piket (ADR 18, 19, 20):**  
   Mode scanner eksplisit (ADR 18 + Keputusan A1) hanya bermakna jika jadwal piket berbasis database (ADR 19) mengecek hak akses pada setiap request scanner; kebijakan kamera browser (ADR 20 — Keputusan B2: `camera=(self)`) telah disahkan sehingga tombol `[BUKA SCANNER]` dapat mengakses kamera HP Guru Piket, tetap dikontrol permission browser + otorisasi server-side.

---

## 6. IMPLEMENTATION IMPACT ON DEVELOPMENT

Dampak teknis yang akan terjadi pada fase implementasi koding setelah masing-masing kelompok keputusan disahkan oleh pimpinan sekolah:

```text
+---------------------+---------------------------------------------------------+
| Kelompok Keputusan  | Dampak Langsung Terhadap Implementasi Kode & Sistem     |
+---------------------+---------------------------------------------------------+
| ADR 14, 18, 19, 20  | - Menentukan mode scanner eksplisit MASUK/PULANG.       |
| (Operasional        | - Menentukan middleware CheckDutySchedule per request. |
| Absensi & Scanner)  | - Menentukan CRUD jadwal piket + audit_logs perubahan. |
+---------------------+---------------------------------------------------------+
| ADR 04, 09, 15, 17  | - Menentukan struktur tabel users (role enum vs pivot). |
| (Arsitektur Model   | - Menentukan cakupan query pada DocumentPolicy.         |
| & Otorisasi RBAC)   | - Menentukan binding parameter rute (ID vs UUID).       |
+---------------------+---------------------------------------------------------+
| ADR 06, 07, 08, 13  | - Menentukan konfigurasi .env koneksi Google Drive.     |
| (Cloud Storage      | - Menentukan aturan validasi FormRequest (max:10240).   |
| & Keamanan Berkas)  | - Menentukan arsitektur server (apakah install ClamAV). |
+---------------------+---------------------------------------------------------+
| ADR 10, 11, 12, 16  | - Menentukan ada/tidaknya konfigurasi SMTP Mail Server. |
| (Keamanan & BCP)    | - Menentukan waktu session.lifetime pada config.        |
|                     | - Menentukan skrip otomatisasi pencadangan database.    |
+---------------------+---------------------------------------------------------+
```

---

## 7. MATRIKS KEPUTUSAN FINAL (FINAL DECISION MATRIX)

Tabel berikut menjadi lembar kerja pengesahan keputusan formal bagi pemangku kepentingan:

| ID ADR | Topik Keputusan Arsitektur & Kebijakan | Rekomendasi Analis | Pilihan A | Pilihan B | Pilihan C | Status Akhir |
| :--- | :--- | :---: | :--- | :--- | :--- | :---: |
| **ADR 01** | Penandaan Status ALPA Guru (fitur dihapus) | **Opsi B** | Scheduler Cron Otomatis | Entri Manual Staf TU | Hybrid Alert & Lock | `[OBSOLETE]` |
| **ADR 02** | Batas Toleransi Jam Masuk Guru (fitur dihapus) | **Opsi C** | 07:00:00 WIB Tepat | 07:15:00 WIB | Dinamis via Settings | `[OBSOLETE]` |
| **ADR 03** | Batasan Jaringan IP Presensi (fitur dihapus) | **Opsi A** | Bebas Jaringan (Log IP) | Whitelist IP Sekolah | - | `[OBSOLETE]` |
| **ADR 04** | Akun Peran Ganda (Guru & Supervisor)| **Opsi B** | 1 Akun (Role Switcher) | 2 Akun Terpisah | - | `[FINAL]` |
| **ADR 05** | Otorisasi Batal Dokumen DISETUJUI | **Opsi B** | Tidak Bisa Dibatalkan | Batal via Pimpinan/Admin | - | `[FINAL]` |
| **ADR 06** | Model Akun Google Drive Sekolah | **Opsi A/B** | Google Workspace Shared | Akun Gmail Standar | - | `[FINAL]` |
| **ADR 07** | Batas Maksimal Ukuran Unggah | **Opsi A** | 10 MB per berkas | 25 MB per berkas | 50 MB per berkas | `[FINAL]` |
| **ADR 08** | Retensi Berkas Fisik Versi Lama | **Opsi A** | Disimpan Selamanya | Hapus Pasca Disetujui | - | `[FINAL]` |
| **ADR 09** | Ruang Lingkup Supervisi Dokumen | **Opsi A** | Terbuka Satu Sekolah | Berbasis Rumpun Mapel | - | `[FINAL]` |
| **ADR 10** | Mekanisme Reset Password | **Opsi A** | Terpusat via Staf TU | Mandiri via Email | Hybrid Email & TU | `[FINAL]` |
| **ADR 11** | Batas Inactivity Timeout Sesi | **Opsi B** | 60 Menit (1 Jam) | 120 Menit (2 Jam) | 480 Menit (8 Jam) | `[FINAL]` |
| **ADR 12** | Target Bencana RPO & RTO | **Opsi A** | RPO 24 Jam & RTO 4 Jam | RPO 1 Jam & RTO 1 Jam | - | `[FINAL]` |
| **ADR 13** | Integrasi Antivirus Lokal ClamAV | **Opsi A** | Validasi App Server-Side| Tambah Daemon ClamAV | - | `[FINAL]` |
| **ADR 14** | Auto-Lock Sesi Absensi Siswa | **Opsi C** | Selesai Jam Pelajaran | Pukul 15:30 WIB | Pukul 23:59:59 EOD | `[FINAL]` |
| **ADR 15** | Format Identifier Publik (PK) | **Opsi A** | Murni BIGINT Identity | Dual (BIGINT + UUID) | - | `[FINAL]` |
| **ADR 16** | Retensi Cadangan Akhir Semester | **Opsi A** | Disimpan Selamanya | Siklus 5 Tahun | - | `[FINAL]` |
| **ADR 17** | Akses Audit Log untuk Supervisor | **Opsi B** | Khusus Admin TU Saja | Read-Only Supervisor | - | `[FINAL]` |
| **ADR 18** | Mode Scanner MASUK/PULANG | **Opsi B** | Auto-Detect (DILARANG) | Mode Eksplisit | - | `[FINAL — ADDENDUM]` |
| **ADR 19** | Jadwal Guru Piket | **Opsi B** | Hardcoded (DILARANG) | Jadwal Database (Duty Schedule) | - | `[FINAL — ADDENDUM]` |
| **ADR 20** | Permissions-Policy Kamera Scanner | **Opsi B** | `camera=()` Blokir Total | `camera=(self)` Rute Scanner | - | `[FINAL — B2]` |

> [!IMPORTANT]
> **CATATAN RATIFIKASI (C1, 2026-09-27) — HASIL YANG BERLAKU:** Huruf/angka opsi pada tabel historis di atas dapat berbeda pelabelan dengan `05-consolidated-prd.md` §35. Yang berlaku adalah hasil final yang terdokumentasi di dokumen 01–05: **ADR 12 = RPO 24 Jam & RTO 8 Jam** (`04` §12, `05` §25.1); **ADR 14 = Manual Close oleh Guru, tanpa auto-lock scheduler pada MVP** (`01` §12.4, `05` §35); **ADR 17 = halaman audit log umum eksklusif Admin/Staff TU** (`01` §5.4, `05` §35); **ADR 06 = Standard Google Drive Account; ADR 07 = 10 MB; ADR 11 = 120 menit; ADR 13 = tanpa ClamAV; ADR 15 = BIGINT UNSIGNED AUTO_INCREMENT** — seluruhnya sesuai dasar implementasi 01–05.

---

## 8. AUDIT KELENGKAPAN DISCOVERY (DECISION DISCOVERY AUDIT)

```text
==================================================
DECISION DISCOVERY & RESOLUTION AUDIT REPORT
==================================================

SOURCE FILES ACCESSIBILITY:
01 Product Requirements        : PASS (Tersedia, Terbaca Utuh)
02 Architecture & Database     : PASS (Tersedia, Terbaca Utuh)
03 Google Drive & Documents    : PASS (Tersedia, Terbaca Utuh)
04 Security & Operations       : PASS (Tersedia, Terbaca Utuh)
05 Consolidated Blueprint      : PASS (Tersedia, Terbaca Utuh)

LITERAL DECISION SEARCH        : PASS
SEMANTIC DECISION DISCOVERY    : PASS

KNOWN DECISIONS FROM 05        : 14 Keputusan
ADDITIONAL DECISIONS DISCOVERED: 3 Keputusan (ADR 15, ADR 16, ADR 17)
ADDENDUM DECISIONS (2026-09-25) : 3 Keputusan (ADR 18, ADR 19, ADR 20)
RATIFICATION (2026-09-27)      : Keputusan A1 (PULANG tanpa MASUK = DITOLAK), B2 (camera=(self)), C1 (ADR 04-17 = FINAL)
ALREADY RESOLVED AS NON-OPEN   : 0 Keputusan
OBSOLETE (FEATURE REMOVED)     : 3 Keputusan (ADR 01, ADR 02, ADR 03)
TOTAL DECISIONS DISCOVERED     : 20 Keputusan
FINAL OPEN DECISIONS IN 06     : 0 Keputusan
RESOLVED/FINAL                 : 17 Keputusan (ADR 04-20; A1 & B2 memperkuat ADR 18 & 20)

DECISION TRACEABILITY          : PASS (100% Memiliki Bukti Dokumen Sumber)
DECISION LOSS CHECK            : PASS (Tidak Ada Keputusan yang Hilang)
UNSUPPORTED DECISION CHECK     : PASS (Tidak Ada Fitur Baru yang Dikarang)
CONFLICT RESOLUTION CHECK      : PASS (ADR 20 diselesaikan lewat Keputusan B2 — camera=(self), disahkan 2026-09-27)
ADDENDUM CONSISTENCY CHECK     : PASS (Sesuai ADDENDUM 2026-09-25 + Keputusan A1/B2/C1 2026-09-27)
TEACHER ATTENDANCE REMOVAL     : PASS (teacher_attendances = HAPUS TOTAL)
CROSS-DECISION IMPACT CHECK    : PASS (Seluruh Keterikatan Terpetakan)

FINAL STATUS:
APPROVED FOR DEVELOPMENT (0 Open Decisions — 17 FINAL, 3 OBSOLETE; A1/B2/C1 disahkan 2026-09-27)
```

*(Catatan: Seluruh keputusan telah disahkan formal oleh pemilik proyek/pimpinan sekolah pada 2026-09-27 (A1, B2, C1) dan status ADR di dokumen ini telah disinkronkan. Dokumen 01/02/04/05/06 berstatus APPROVED FOR DEVELOPMENT. Tahap implementasi koding BELUM dimulai dan menunggu perintah resmi).*
