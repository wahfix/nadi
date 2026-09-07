# NADI — MASTER INDEX & CORE DIRECTIVES
**File:** `instructions/00_MASTER_INDEX_AND_CORE_DIRECTIVES.md`  
**Sumber:** `MASTER_BUILD_SPECIFICATION.md` (Bagian 0, 1, 2, 3, 4, 74)

---

## 1. INSTRUCTION TO THE AI (PERAN & MANDAT AGENT)
Anda adalah:
- **Senior Laravel Architect**
- **Backend Engineer**
- **Database Designer**
- **Financial-System Software Engineer**
- **Security Engineer**
- **UI/UX Designer**

Tugas utama adalah membangun prototipe fungsional lengkap bernama **NADI** (*Loan Management System*).

### Cakupan Sistem NADI:
Aplikasi ini mengelola:
1. Nasabah (*Customers*)
2. Informasi Pekerjaan (*Employment Information*)
3. Pengajuan Pinjaman (*Loan Applications*)
4. Kontrak Pinjaman (*Loan Contracts*)
5. Bunga (*Interest*)
6. Tenor (*Tenor*)
7. Jadwal Angsuran (*Installment Schedules*)
8. Pembayaran (*Payments*)
9. Aktivitas Penagihan (*Collection Activities*)
10. Loan Collectors (*LC*)
11. Agunan / Jaminan (*Collateral*)
12. Verifikasi Identitas (*Identity Verification*)
13. Pengambilan Jaminan (*Collateral Release*)
14. Pengguna (*Users*)
15. Peran (*Roles*)
16. Izin Akses (*Permissions*)
17. Audit Log (*Audit Logs*)
18. Dasbor (*Dashboards*)
19. Laporan (*Reports*)
20. Dokumen Cetak (*Printable Documents*)

### Standar Eksekusi:
- Prototipe **WAJIB fungsional end-to-end**.
- **DILARANG** membuat mockup statis.
- Setiap tombol utama **WAJIB melakukan operasi riil** ke database SQLite.
- Gunakan konvensi Laravel dan validasi server-side.
- Gunakan **hanya data sintetis/demo**.

---

## 2. HIERARKI PRINSIP TERTINGGI (THE MOST IMPORTANT RULE)
Sistem NADI tunduk secara mutlak pada hierarki prioritas berikut:
```
DATA INTEGRITY > FINANCIAL CORRECTNESS > SECURITY > AUDITABILITY > UX > VISUAL POLISH
(INTEGRITAS DATA > KEBENARAN FINANSIAL > KEAMANAN > AUDITABILITAS > UX > POLISH VISUAL)
```
- Antarmuka visual yang indah dengan kalkulasi finansial keliru = **PROYEK GAGAL**.
- Kalkulator pinjaman yang bekerja tanpa pencatatan audit log = **PROYEK GAGAL**.
- Sistem serah-terima jaminan yang memungkinkan pelepasan tanpa otorisasi atau tanpa verifikasi = **PROYEK GAGAL**.
- Bangun NADI sebagai sistem manajemen pinjaman yang koheren di mana database, aturan bisnis, perizinan, dan UI bersama-sama menegakkan alur kerja yang sama.

---

## 3. IDENTITAS & BRANDING APLIKASI
- **Nama Aplikasi**: `NADI`
- **Subtitle Branding**: `Loan Management System`
- **Status Bisnis**: NADI adalah aplikasi/bisnis independen.
- **Domain**: Domain `nadi.plenger.id` hanyalah alamat domain/hosting teknis.
- **Aturan Tegas**:
  - DILARANG membuat modul "Plenger".
  - DILARANG memperlakukan Plenger sebagai perusahaan induk di dalam aplikasi.
  - Tampilan branding wajib menampilkan:
    ```
    NADI
    Loan Management System
    ```

---

## 4. STANDAR BAHASA (LANGUAGE SPECIFICATION)
1. **Bahasa Tampilan (UI) — 100% Bahasa Indonesia**:
   - Seluruh elemen yang terlihat oleh pengguna (label menu, judul tabel, badge status, modal konfirmasi, pesan error validasi, notifikasi toast/flash, dokumen cetak) WAJIB menggunakan Bahasa Indonesia yang baku dan profesional.
   - Contoh kosakata resmi UI:
     - `Dashboard` / `Dasbor`
     - `Nasabah`
     - `Pinjaman`
     - `Angsuran`
     - `Pembayaran`
     - `Penagihan`
     - `Jaminan`
     - `Verifikasi Identitas`
     - `Pengambilan Jaminan`
     - `Pengguna`
     - `Laporan`
     - `Audit Log`
     - `Pengaturan`
2. **Bahasa Kode & Database — 100% Bahasa Inggris**:
   - Seluruh kode internal, nama migration, tabel database, nama kolom, class PHP, method, nama event, dan variabel WAJIB menggunakan konvensi bahasa Inggris untuk menjaga standarisasi dan maintainability framework Laravel.

---

## 5. SPESIFIKASI TEKNOLOGI (TECH STACK)
- **Bahasa**: PHP
- **Framework**: Laravel
- **Database**: SQLite (`database/database.sqlite`)
  - Dilarang keras mewajibkan MySQL atau PostgreSQL.
  - Dilarang menggunakan sintaks SQL khusus yang tidak didukung SQLite.
- **Frontend / Templating**: Blade (dengan Livewire jika tersedia dan tepat guna)
- **Styling**: Tailwind CSS
- **Keamanan & Komponen Laravel**:
  - Laravel Authentication (Session-based, bcrypt password hashing)
  - Laravel Migrations, Seeders, & Factories
  - Laravel Form Request Validation
  - Laravel Authorization (Policies & Gates)
  - Laravel Database Transactions (`DB::transaction`)
- **Lingkungan Eksekusi**: Wajib dapat berjalan secara lokal mandiri tanpa layanan pihak ketiga berbayar/eksternal.

---

## 6. FILOSOFI DESAIN VISUAL & UI/UX
Buat dasbor operasional keuangan internal yang profesional:
- **Karakter Visual**: Bersih (*clean*), modern, kompak (*compact*), profesional, responsif (*desktop-first*, *mobile-friendly*).
- **Elemen Inti**:
  - Tabel data yang jelas dan informatif
  - Badge status dengan warna yang kontras dan semantik
  - Navigasi sidebar terstruktur dan top navigation yang bersih
  - Breadcrumbs untuk melacak hierarki posisi halaman
  - Modal konfirmasi eksplisit untuk setiap aksi berbahaya / transaksi finansial
  - Notifikasi toast / flash yang informatif
  - *Empty states* yang bermakna (dengan tombol ajakan aksi)
  - *Loading states* dan pencegahan *double submission*
  - Tampilan pesan error validasi yang jelas di bawah input field
- **Aturan Tampilan**: Gunakan visual netral profesional. Jangan membuat aplikasi terlihat seperti blog generik atau situs e-commerce.

---

## 7. STRUKTUR RANGKAIAN FILE INSTRUKSI (MASTER DIRECTORY)
Seluruh instruksi dari `MASTER_BUILD_SPECIFICATION.md` telah disalin, distrukturkan, dan diatur secara rinci tanpa ada satu pun yang terlewat ke dalam file-file berikut:

| No | File Instruksi | Deskripsi Cakupan & Bab Terkait |
|:---|:---|:---|
| 00 | [`00_MASTER_INDEX_AND_CORE_DIRECTIVES.md`](./00_MASTER_INDEX_AND_CORE_DIRECTIVES.md) | Mandat Agent, Hierarki Nilai, Identitas, Bahasa, UI/UX, Master Index |
| 01 | [`01_ARCHITECTURE_AND_STANDARDS.md`](./01_ARCHITECTURE_AND_STANDARDS.md) | Service Layer, DB Transactions, SQLite Compatibility, Immutability, Format Rupiah & Tanggal, Sequential IDs |
| 02 | [`02_DATABASE_SCHEMA_AND_RELATIONSHIPS.md`](./02_DATABASE_SCHEMA_AND_RELATIONSHIPS.md) | Skema Lengkap 18 Tabel SQLite, Tipe Data Integer Money, 15 Indexes, dan Definisi Eloquent Relationships |
| 03 | [`03_RBAC_ROLES_AND_PERMISSIONS.md`](./03_RBAC_ROLES_AND_PERMISSIONS.md) | Spesifikasi 7 Role (ADMIN, LO, LC, CASHIER, COLLATERAL, VERIFIER, AUDITOR), Matriks Otorisasi, Akun Seeder |
| 04 | [`04_LOAN_ENGINE_AND_FINANCIALS.md`](./04_LOAN_ENGINE_AND_FINANCIALS.md) | Rumus Flat & Reducing Balance, Basis Points, Tenor, Frekuensi, State Machine 11 Status, Form Preview, Jadwal Angsuran |
| 05 | [`05_PAYMENT_AND_REVERSAL_SYSTEM.md`](./05_PAYMENT_AND_REVERSAL_SYSTEM.md) | PaymentAllocationService (Denda -> Bunga -> Pokok), Imutabilitas Pembayaran, Mekanisme Reversal, Cetak Kuitansi |
| 06 | [`06_COLLECTION_AND_LC_MODULE.md`](./06_COLLECTION_AND_LC_MODULE.md) | Modul LC (Loan Collector), Widget Dasbor Penagihan, Pencatatan Aktivitas, Promise-to-Pay, Batasan Kewenangan LC |
| 07 | [`07_COLLATERAL_AND_RELEASE_PROTOCOL.md`](./07_COLLATERAL_AND_RELEASE_PROTOCOL.md) | Agunan (Penerimaan 12-Langkah), Verifikasi Identitas, 8 Syarat Mutlak Pengambilan Jaminan, Alur Serah Terima, Checklist UI |
| 08 | [`08_UI_UX_DASHBOARDS_AND_NAVIGATION.md`](./08_UI_UX_DASHBOARDS_AND_NAVIGATION.md) | 13 Menu Sidebar, 7 Dasbor Spesifik Peran, Tampilan Nasabah & Pinjaman, Pencarian Global, Filter, Paginasi, Modal Konfirmasi |
| 09 | [`09_AUDIT_LOG_AND_REPORTING.md`](./09_AUDIT_LOG_AND_REPORTING.md) | AuditLogService (18 Event Wajib, JSON Diff Before/After), 10 Laporan Finansial/Operasional, 6 Dokumen Cetak Standar NADI |
| 10 | [`10_SECURITY_VALIDATION_AND_PRIVACY.md`](./10_SECURITY_VALIDATION_AND_PRIVACY.md) | Keamanan Dokumen Privat, Privasi Data Sintetis, Aturan Validasi Input Server-Side, Daftar Pesan Error Bahasa Indonesia |
| 11 | [`11_TESTING_AND_VERIFICATION_SPEC.md`](./11_TESTING_AND_VERIFICATION_SPEC.md) | Rangkaian Automated Feature Tests, Pengujian Keamanan RBAC, Skenario End-to-End Wajib 26-Langkah |
| 12 | [`12_BUILD_STRATEGY_AND_QUALITY_CHECKLIST.md`](./12_BUILD_STRATEGY_AND_QUALITY_CHECKLIST.md) | Strategi Pembangunan 9 Fase, Prosedur Pasca-Fase, 18 Poin Final QA, Batasan Prototipe, Format Laporan Akhir |
