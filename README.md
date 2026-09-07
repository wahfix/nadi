# NADI — Loan Management System

> **Status: Prototipe fungsional end-to-end (BUKAN produksi).**
> NADI dirancang sebagai sistem internal manajemen nasabah, pinjaman, pembayaran, penagihan, dan jaminan.
> Seluruh skenario dikerjakan terhadap **SQLite** dengan **data sintetis (demo) saja**.

---

## Daftar Isi

1. [Ringkasan Aplikasi](#1-ringkasan-aplikasi)
2. [Stack Teknologi](#2-stack-teknologi)
3. [Struktur Proyek](#3-struktur-proyek)
4. [Instalasi & Konfigurasi](#4-instalasi--konfigurasi)
5. [Akun Demo](#5-akun-demo)
6. [Arsitektur & Service Layer](#6-arsitektur--service-layer)
7. [Basis Data](#7-basis-data)
8. [Model Bisnis Nomor & Format](#8-model-bisnis-nomor--format)
9. [Kalkulasi Bunga](#9-kalkulasi-bunga)
10. [State Machine Pinjaman](#10-state-machine-pinjaman)
11. [Angsuran & Deteksi Tunggakan](#11-angsuran--deteksi-tunggakan)
12. [Pembayaran, Alokasi & Reversal](#12-pembayaran-alokasi--reversal)
13. [Modul Penagihan / Loan Collector](#13-modul-penagihan--loan-collector)
14. [RBAC — Peran & Izin](#14-rbac--peran--izin)
15. [Audit Log](#15-audit-log)
16. [Dashboard & Aturan UI/UX](#16-dashboard--aturan-ux)
17. [Keamanan](#17-keamanan)
18. [Laporan & Dokumen Cetak](#18-laporan--dokumen-cetak)
19. [Pengujian](#19-pengujian)
20. [Status Roadmap 9 Fase](#20-status-roadmap-9-fase)
21. [Keterbatasan Prototipe](#21-keterbatasan-prototipe)
22. [Laporan Akhir Teknikal](#22-laporan-akhir-teknikal)
23. [Konvensi Git & Kontribusi](#23-konvensi-git--kontribusi)
24. [Lisensi](#24-lisensi)

---

## 1. Ringkasan Aplikasi

NADI mengelola:

- **Nasabah** dan **data pekerjaan (employment)**
- **Pengajuan pinjaman** → **kontrak pinjaman** (pokok, bunga, tenor, metode bunga)
- **Jadwal angsuran** otomatis setelah pencairan
- **Pembayaran** dengan urutan alokasi wajib **Denda → Bunga → Pokok**
- **Reversal/pembalikan** pembayaran (imutabilitas finansial)
- **Aktivitas penagihan** dan **jangka bayar** oleh **Loan Collector (LC)**
- **Jaminan (collateral)**, **verifikasi identitas**, dan **serah terima/pengambilan jaminan**
- **Users**, **roles**, **permissions**, dan **audit log** yang lengkap
- **Dashboard**, **laporan**, dan **dokumen cetak**

Prioritas tertinggi (tidak bisa ditawar):

```
DATA INTEGRITY > FINANCIAL CORRECTNESS > SECURITY > AUDITABILITY > UX > VISUAL POLISH
```

### Prinsip mutlak yang ditegakkan sistem

| # | Invarian | Implementasi |
|---|----------|--------------|
| 1 | Uang disimpan **INTEGER Rupiah** (bukan float) | Seluruh kolom `*amount`, `*_component`, `*_paid`, `outstanding_*` bertipe integer |
| 2 | Bunga disimpan presisi (**basis points**) | `interest_rate_bps` integer; 1% = 100 bps |
| 3 | Mutasi finansial dibungkus `DB::transaction()` | Approval, disbursement, payment, reversal, collateral, verifikasi, release |
| 4 | Nomor bisnis **sequential** (bukan auto-increment mentah) | `SequentialNumberService` |
| 5 | **Imutabilitas** historis transaksi | Nilai kontraktual dikunci saat aktivasi; pembayaran tidak bisa diedit/dihapus |
| 6 | Koreksi hanya lewat **reversal** | Tabel `payment_reversals` + jejak audit |
| 7 | Logika finansial **hanya di Service Layer** | Tidak ada rumus bunga di controller/Blade |
| 8 | Validasi **server-side** untuk semua input | Form Request + policy + middleware RBAC |
| 9 | **Teks UI 100% Bahasa Indonesia** | Kode tetap English (tabel/kolom/variabel) |
| 10 | Pelepasan jaminan melewati **8 syarat** server-side | Diblokir bila satu saja gagal |

---

## 2. Stack Teknologi

| Lapisan | Teknologi |
|---------|-----------|
| Bahasa | PHP `^8.3` |
| Framework | Laravel Framework `^13.17` |
| Database | SQLite (`database/database.sqlite`) — **tanpa** sintaks MySQL/PostgreSQL spesifik |
| Otentikasi | Laravel Fortify `^1.37` (+ passkeys) |
| Frontend | Blade, Tailwind CSS, Alpine.js, Vite |
| Komponen UI | Livewire `^4.1` + Flux `^2.13` |
| QA / CI | Pest `^5.1`, Laravel Pint `^1.27`, Larastan / PHPStan, Laravel Sail |

Ekstensi PHP yang dibutuhkan: `pdo_sqlite`, `bcmath` (dipakai kalkulasi annuity REDUCING_BALANCE), DLL standar Laravel.

Skrip composer tersedia:

```bash
composer ci:check   # config:clear → pint --test → phpstan analyse → pest
composer lint       # pint otomatis memperbaiki style
```

---

## 3. Struktur Proyek

```
app/
├── Http/
│   ├── Controllers/        # Controller tipis (validasi + delegasi ke service)
│   ├── Middleware/         # CheckPermission, CheckRole
│   └── Requests/           # Form Request validasi server-side (9 request)
├── Models/                 # 16 model Eloquent
├── Policies/               # CustomerPolicy, LoanPolicy, PaymentPolicy,
│                           # CollectionActivityPolicy, UserPolicy
├── Services/               # 12 service business/domain (lihat Bagian 6)
└── Helpers/helpers.php     # format_rupiah(), format_date(), format_date_indonesian(), …
database/
├── migrations/             # 15 file migrasi (25 tabel, lihat Bagian 7)
└── seeders/                # RoleSeeder, PermissionSeeder, UserSeeder,
                            # DemoDataSeeder, LoanSeeder
resources/views/
├── dashboard.blade.php     # dasbor umum + menu cepat berbasis permission
└── modules/                # customers, loans, installments, payments,
                            # collections, collaterals, verifications,
                            # releases, reports, audit-log, users
routes/
├── web.php                 # route modul operasional (permission-gated)
└── settings.php            # profile, appearance, security (Fortify/Livewire)
tests/Feature/              # 9 file test (Pest)
bootstrap/app.php           # registrasi alias middleware 'permission', 'role'
```

---

## 4. Instalasi & Konfigurasi

### Prasyarat

- PHP `>= 8.3` (dengan `pdo_sqlite`, `bcmath`)
- Composer
- Node.js + npm
- PHP CLI (untuk `php artisan serve` / `php artisan dev`)

### Langkah instalasi

```bash
# 1. Install dependencies
composer install
npm install

# 2. Siapkan konfigurasi .env
cp .env.example .env
php artisan key:generate

# 3. Arahkan ke SQLite
#    Di .env, pastikan:
#    DB_CONNECTION=sqlite

# 4. Buat file database SQLite
touch database/database.sqlite

# 5. Jalankan migrasi + seeder
php artisan migrate --seed

# 6. Build aset frontend
npm run build

# 7. Jalankan aplikasi
php artisan dev          # (atau: php artisan serve)
```

Setelah selesai, buka `http://localhost:8000` dan login dengan salah satu akun demo.
Untuk pengembangan aset frontend gunakan `npm run dev` bersamaan dengan `php artisan serve`.

> Lupa password? Semua akun demo memakai password **`password`** (hanya untuk pengembangan, lihat Bagian 5).

---

## 5. Akun Demo

Seeder membuat **7 akun**, satu untuk setiap peran. Kata sandi: **`password`**

| Peran | Email | Keterangan |
|-------|-------|------------|
| ADMIN | `admin@example.test` | Akses penuh, persetujuan & pencairan pinjaman, kelola pengguna |
| LO | `lo@example.test` | Membuat nasabah & mengajukan pinjaman |
| LC | `lc@example.test` | Penagihan, dasbor tunggakan, janji bayar |
| CASHIER | `cashier@example.test` | Mencatat pembayaran & mencetak kuitansi |
| COLLATERAL OFFICER | `collateral@example.test` | Terima & kelola penyimpanan jaminan |
| IDENTITY VERIFIER | `verifier@example.test` | Verifikasi identitas |
| AUDITOR | `auditor@example.test` | Akses baca-saja + laporan + audit log |

Seeds juga menyediakan **±20 nasabah sintetis**, pinjaman di berbagai status state machine (`DRAFT` s.d. `COMPLETED`), **instalments**, **beberapa pembayaran**, **aktivitas penagihan**, dan **audit log** — semuanya data fiktif.

---

## 6. Arsitektur & Service Layer

Logika bisnis **wajib** berada di service, controller tipis hanya: validasi input → panggil service → return response. Berikut 12 service yang sudah ada:

| Service | Tanggung Jawab |
|---------|----------------|
| `LoanCalculationService` | Rumus FLAT & REDUCING_BALANCE (aritmatika integer + bcmath) |
| `InterestCalculationService` | Fasad resmi kalkulasi bunga pinjaman |
| `InstallmentScheduleService` | Generate jadwal angsuran, jatuh tempo, refresh status OVERDUE |
| `LoanStatusService` | State machine 11 status + transisi legal |
| `LoanService` | Lifecycle pinjaman (draft, submit, review, approve, disburse, complete, syncOverdue) |
| `CustomerService` | CRUD nasabah + kode `CUS-YYYY-XXXXXX` |
| `SequentialNumberService` | Nomor bisnis `CUS/NADI-LOAN/PAY/COL/REL` |
| `PaymentAllocationService` | Alokasi pembayaran (Denda → Bunga → Pokok) + pencatatan |
| `PaymentReversalService` | Pembalikan simetris + restore saldo |
| `CollectionService` | Dasbor LC, aktivitas penagihan, Promise-to-Pay |
| `UserService` | Kelola user + penentuan peran |
| `AuditLogService` | 18+ event audit dengan JSON diff before/after |

Policies & akses: `CustomerPolicy`, `LoanPolicy`, `PaymentPolicy`, `CollectionActivityPolicy`, `UserPolicy` + middleware `permission`/`role`.

---

## 7. Basis Data

**26 tabel** hasil migrasi: 13 tabel bisnis + 5 tabel akses/otentikasi + 8 tabel infrastruktur Laravel.

### Tabel Bisnis & RBAC

| Tabel | Isi |
|-------|-----|
| `customers` | Profil nasabah (kode `CUS-*`, NIK, kontak, status ACTIVE/INACTIVE/BLOCKED) |
| `employments` | Pekerjaan nasabah (perusahaan, jabatan, penghasilan, status) |
| `loans` | Pinjaman + nilai kontraktual (pokok, bunga, tenor, total, outstanding, status, penanda approval/disbursement) |
| `loan_status_histories` | Riwayat transisi status (from → to, petugas, alasan) |
| `installments` | Jadwal angsuran (principal/interest/penalty due & paid, remaining, status) |
| `payments` | Pembayaran + alokasi riil (principal/interest/penalty_component) |
| `payment_reversals` | Pembalikan pembayaran (alasan, petugas, waktu) |
| `collection_activities` | Aktivitas penagihan LC + Promise-to-Pay |
| `collaterals` | Jaminan (kode `COL-*`, tipe, nilai, lokasi, custody_status) |
| `identity_verifications` | Verifikasi identitas (metode, hasil, verifier) |
| `collateral_releases` | Serah terima jaminan (nomor `REL-*`, penerima, saksi) |
| `audit_logs` | Jejak audit (action, entity, JSON old/new, ip, user_agent) |
| `document_references` | Referensi dokumen terkait |
| `roles`, `permissions`, `role_user`, `permission_role` | RBAC |

### Relasi utama (Eloquent)

```
Customer 1─n Employment
Customer 1─n Loan             Loan n─1 Customer
Loan 1─n Installment          Installment n─1 Loan
Loan 1─n Payment              Payment n─1 Loan / Customer / Installment
Loan 1─n CollectionActivity   CollectionActivity n─1 Loan / Customer / Collector(User)
Loan 1─n Collateral           Collateral n─1 Loan / Customer
Customer 1─n IdentityVerification (opsional terkait Loan)
Collateral 1─n CollateralRelease  (Loan & Customer terkait)
AuditLog n─1 User
Payment 1─1 PaymentReversal (relasi opsional)
```

### Konvensi data

- **Uang**: selalu `INTEGER` (integer Rupiah). Contoh `Rp 1.500.000` → `1500000`. `float/double/decimal` **dilarang**.
- **Bunga**: `interest_rate_bps` integer (1% = 100 bps).
- **Index**: dibuat pada kolom pencarian utama (kode bisnis, NIK, telepon, `status`, `due_date`, `loan_id`, `customer_id`, `created_at`, dll.).
- **Unique**: kode bisnis (customer_code, loan_number, payment_number, collateral_code, release_number).

---

## 8. Model Bisnis Nomor & Format

### Nomor bisnis sequential (per tahun)

```
Nasabah:    CUS-YYYY-XXXXXX        →  CUS-2026-000001
Pinjaman:   NADI-LOAN-YYYY-XXXXXX  →  NADI-LOAN-2026-000001
Pembayaran: PAY-YYYY-XXXXXX        →  PAY-2026-000001
Jaminan:    COL-YYYY-XXXXXX        →  COL-2026-000001
Pelepasan:  REL-YYYY-XXXXXX        →  REL-2026-000001
```

Dibuat oleh `SequentialNumberService` — tidak hanya mengandalkan auto-increment ID database.

### Format tampilan

- **Uang**: `Rp 1.500.000` (titik ribuan) — helper `format_rupiah()`.
- **Tanggal**: `DD/MM/YYYY` (`format_date()`) atau `DD Bulan YYYY` Indonesia (`format_date_indonesian()`, contoh `10 September 2026`).

---

## 9. Kalkulasi Bunga

### FLAT

```
total_interest = principal × (interest_rate_bps ÷ 10000) × tenor
total_payable  = principal + total_interest
installment    = total_payable ÷ tenor
```

Contoh (pokok Rp 10.000.000, 2%/bulan, 10 bulan):

- Bunga = 10.000.000 × 2% × 10 = **Rp 2.000.000**
- Total = **Rp 12.000.000**
- Angsuran = **Rp 1.200.000**

### REDUCING_BALANCE (annuitas)

```
A = P × r × (1+r)^n / ((1+r)^n − 1)
```

- `A` = angsuran tetap, `P` = pokok, `r` = bunga periodik, `n` = jumlah periode.
- Annuitas dihitung menggunakan **bcmath** (tanpa binary floating point).
- Jadwal amortisasi dihitung dengan aritmatika **integer eksak** (pembulatan setengah ke atas per periode); **angsuran terakhir menyerap selisih pembulatan** sehingga total tercatat presisi.
- Jika `r = 0`, angsuran = `principal ÷ n`.

Semua nilai dikembalikan sebagai integer (Rupiah utuh). Tanggal jatuh tempo dihitung dari `first_due_date` mengikuti frekuensi (`MONTHLY`/`WEEKLY`) dikali tenor.

---

## 10. State Machine Pinjaman

11 status legal — **tidak ada perubahan status arbitrer**:

```
DRAFT → SUBMITTED → UNDER_REVIEW → APPROVED → READY_FOR_DISBURSEMENT → ACTIVE
                                   ↘ REJECTED (terminal)
ACTIVE → OVERDUE → ACTIVE / COMPLETED / DEFAULTED
ACTIVE → COMPLETED
COMPLETED → ACTIVE   (khusus: pembayaran pelunasan dibalikkan / reversal)
DRAFT / SUBMITTED / READY_FOR_DISBURSEMENT → CANCELLED
```

Transisi legal persis (di `LoanStatusService::TRANSITIONS`):

| Dari | Ke |
|------|----|
| `DRAFT` | `SUBMITTED`, `CANCELLED` |
| `SUBMITTED` | `UNDER_REVIEW`, `CANCELLED` |
| `UNDER_REVIEW` | `APPROVED`, `REJECTED` |
| `APPROVED` | `READY_FOR_DISBURSEMENT` |
| `READY_FOR_DISBURSEMENT` | `ACTIVE`, `CANCELLED` |
| `ACTIVE` | `OVERDUE`, `COMPLETED`, `DEFAULTED` |
| `OVERDUE` | `ACTIVE`, `COMPLETED`, `DEFAULTED` |
| `COMPLETED` | `ACTIVE` (reversal pelunasan) |
| `REJECTED` / `DEFAULTED` / `CANCELLED` | terminal |

Setiap transisi tercatat di `loan_status_histories` + audit `LOAN_STATUS_CHANGED`. Pencairan (`DISBURSED`) akan otomatis **membuat jadwal angsuran** dan mengubah status `READY_FOR_DISBURSEMENT → ACTIVE`.

---

## 11. Angsuran & Deteksi Tunggakan

`installments` dibuat otomatis saat pencairan dengan pembagian pokok/bunga per periode (sesuai metode bunga), status awal `PENDING`.

Status angsuran:

```
PENDING → PARTIALLY_PAID → PAID
        → OVERDUE (jatuh tempo lewat & belum lunas)
        → WAIVED
```

`LoanService::syncOverdue()` menjalankan `refreshOverdueStatuses()`: angsuran `PENDING`/`PARTIALLY_PAID` yang `due_date`-nya sudah lewat di-set `OVERDUE`; jika pinjaman `ACTIVE` memiliki angsuran OVERDUE, pinjaman dipromosikan ke status `OVERDUE` (dengan audit).

> Catatan: akrual otomatis **denda (penalty)** belum diimplementasikan (lihat Keterbatasan, Bagian 21). Struktur data & alokasi denda sudah didukung penuh.

---

## 12. Pembayaran, Alokasi & Reversal

### Pencatatan pembayaran

- Nomor `PAY-YYYY-XXXXXX`.
- Divalidasi: nominal integer ≥ Rp 1, tanggal tidak boleh masa depan, metode wajib salah satu `CASH` / `BANK_TRANSFER` / `QRIS` / `OTHER`.
- Setiap pembayaran menyimpan **alokasi riil** di dalam record — historis tidak pernah dihitung ulang dari kondisi terkini.

### Urutan alokasi (WAJIB)

```
1. Denda (penalty)    → dialokasikan PERTAMA
2. Bunga (interest)   → dialokasikan KEDUA
3. Pokok (principal)  → dialokasikan TERAKHIR
```

Contoh: pembayaran Rp 1.100.000 dengan denda Rp 0, bunga Rp 100.000, pokok Rp 1.000.000
→ `penalty_component = 0`, `interest_component = 100000`, `principal_component = 1000000`.

### Imutabilitas

- Pembayaran yang tersimpan **tidak bisa di-edit/di-delete** (tidak ada route edit/delete).
- Satu-satunya koreksi: **reversal** via `PaymentReversalService`.
- Reversal: validasi (pembayaran aktif), restore `outstanding_*` pinjaman & status angsuran secara simetris, cantumkan `reason` (min 5 karakter), catat `payment_reversals` + audit `PAYMENT_REVERSED`.
- Jika pembayaran yang dibalikkan adalah pelunasan (pinjaman `COMPLETED`), pinjaman dibuka kembali ke `ACTIVE` via `LoanService::reopenCompletedLoan()`.

### Pendeteksian otomatis

- Setelah pembayaran penuh memenuhi seluruh sisa tagihan, pinjaman otomatis menjadi `COMPLETED` (`completed_at` diisi).
- Overpayment **ditolak server-side**.

---

## 13. Modul Penagihan / Loan Collector

**LC = Loan Collector** (jabatan internal NADI).

### Dasbor Penagihan (LC Dashboard)

- **Total Nasabah Binaan** (nasabah pinjaman aktif/menunggak yang ditangani LC)
- **Jatuh Tempo Hari Ini**
- **Menunggak** (overdue installments)
- **Jatuh Tempo 7 Hari**
- **Total Sisa Tagihan**
- **Janji Bayar Aktif** (Promise-to-Pay)

### Aktivitas penagihan (`collection_activities`)

- Metode kontak: `PHONE`, `WHATSAPP`, `IN_PERSON`, `OTHER`
- Hasil: `PAID`, `PROMISE_TO_PAY`, `NO_RESPONSE`, `CONTACT_FAILED`, `DISPUTED`, `OTHER`
- Data janji bayar (tanggal + nominal) hanya diperbolehkan saat hasil `PROMISE_TO_PAY`; divalidasi server-side, minimal Rp 1.
- Aktivitas **imutabel** (hanya pencatatan, tanpa edit/hapus).
- Setiap aktivitas dicatat `collector_id` + audit `COLLECTION_CREATED`.
- Hanya pinjaman `ACTIVE`/`OVERDUE` yang memiliki sisa tagihan yang dapat dicatat penagihannya.

### Batasan kewenangan LC (diblokir server-side)

LC **dilarang**: mengubah pokok pinjaman, mengubah suku bunga, menyetujui pinjaman, membalikkan pembayaran, melepas jaminan.

---

## 14. RBAC — Peran & Izin

7 peran: `ADMIN`, `LO`, `LC`, `CASHIER`, `COLLATERAL_OFFICER`, `IDENTITY_VERIFIER`, `AUDITOR`.

Daftar izin (permission) terdaftar:

```
customers.view, customers.create, customers.edit, customers.delete
loans.view, loans.create, loans.edit, loans.review, loans.approve, loans.disburse
installments.view
payments.view, payments.create, payments.reverse, payments.receipt
collections.view, collections.create
collaterals.view, collaterals.receive, collaterals.update_custody, collaterals.release
verifications.view, verifications.create, verifications.review
releases.view, releases.execute
audit_logs.view
reports.view
users.view, users.manage
settings.view, settings.manage
```

### Matriks akses per peran (non-admin)

| Peran | Dapat | Tidak dapat |
|-------|-------|-------------|
| **LO** | customers.view/create/edit; loans.view/create/edit; installments.view | menyetujui/mencairkan/membalikkan/melepas jaminan |
| **LC** | customers.view; loans.view; installments.view; payments.view; collections.view/create | approval, reversal, release, mengubah terms |
| **CASHIER** | installments.view; payments.view/create/receipt | approval, mengubah terms pinjaman, release |
| **COLLATERAL_OFFICER** | collaterals.view/receive/update_custody/release; releases.view/execute | melewati aturan kelayakan finansial |
| **IDENTITY_VERIFIER** | verifications.view/create/review | melepas jaminan secara independen |
| **AUDITOR** | baca-saja customers/loans/payments/collections/collaterals/verifications/releases/audit_logs/reports | memodifikasi data |
| **ADMIN** | semua izin | — |

Enforcement: middleware `permission:...` di route + policy (`Gate::authorize`) di controller + pemeriksaan ulang di service. Menyembunyikan tombol saja **tidak cukup**; request langsung pun tetap divalidasi.

---

## 15. Audit Log

Tabel `audit_logs` menyimpan: `user_id`, `action`, `entity_type`, `entity_id`, `old_values` (JSON), `new_values` (JSON), `ip_address`, `user_agent`, `created_at`.

**18 event** yang sudah didukung konstannya (`AuditLogService`):

```
CUSTOMER_CREATED      CUSTOMER_UPDATED      LOAN_CREATED
LOAN_SUBMITTED        LOAN_APPROVED         LOAN_REJECTED
LOAN_DISBURSED        LOAN_STATUS_CHANGED   PAYMENT_CREATED
PAYMENT_REVERSED      COLLECTION_CREATED    COLLATERAL_RECEIVED
IDENTITY_VERIFIED     IDENTITY_FAILED       RELEASE_CREATED
COLLATERAL_RELEASED   USER_CREATED          PERMISSION_CHANGED
```

Log tidak dapat dihapus melalui UI normal. (Halaman Audit UI final direncanakan di Fase 8 — `audit_logs` sudah tercatat sejak Fase 1.)

---

## 16. Dashboard & Aturan UX

- **Teks UI**: 100% Bahasa Indonesia.
- **Uang**: `Rp X.XXX.XXX`; **tanggal**: `DD/MM/YYYY` atau `DD Bulan YYYY`.
- **Aksi destruktif/mutasi**: modal konfirmasi wajib.
- **Empty states**: pesan informatif (bukan halaman kosong).
- **Paginasi**: 15 item/halaman default; tabel besar selalu di-paginasi.
- **Notifikasi**: flash success/error.
- **Responsif**: sidebar desktop, navigasi dapat dilipat di mobile, tabel scroll horizontal.
- Dasbor umum menyediakan menu cepat yang difilter oleh izin peran; dasbor spesifik peran (portofolio, penagihan ala admin, kasir, agunan) direncanakan di Fase 7.

---

## 17. Keamanan

- CSRF, session security, password di-hash (tidak pernah plaintext).
- Otentikasi + verifikasi email (`auth`, `verified`) + rate limiting login Fortify.
- RBAC di route (middleware), policy (Gate), dan service.
- Validasi **server-side** via Form Request untuk semua input.
- Peran diuji: LC tidak bisa approval, Cashier tidak bisa release, Auditor baca-saja, dsb (lihat Bagian 19).
- Dokumen pribadi direncanakan disimpan di `storage/app/private/` dengan unduhan berotorisasi; **bukan** di public direktori.
- **Hanya data sintetis** — dilarang data pribadi nyata/NIK riil.

---

## 18. Laporan & Dokumen Cetak

### Laporan (10 modul — migrasi lengkap, UI di Fase 8)

```
1. Daftar nasabah            6. Pembayaran
2. Daftar pinjaman           7. Jaminan
3. Outstanding pinjaman      8. Pengambilan jaminan
4. Jatuh tempo               9. Aktivitas LC
5. Tunggakan                10. Audit log
```

### Dokumen cetak (6 — tujuan print-friendly)

```
1. Ringkasan pinjaman        4. Kuitansi penerimaan jaminan
2. Jadwal angsuran           5. Hasil verifikasi identitas
3. Kuitansi pembayaran       6. Berita acara serah terima jaminan
```

> **Implementasi saat ini**: kuitansi pembayaran (payment receipt) sudah berfungsi penuh (standalone printable, header NADI, nomor dokumen, tanda tangan). Modul laporan bersama dokumen lain direncanakan di Fase 8.

---

## 19. Pengujian

Framework: **Pest 5**. Selain unit/feature, suite mencakup pengujian keamanan RBAC.

```bash
php artisan test               # jalankan seluruh suite
php artisan test --filter CollectionTest
composer ci:check              # pint + phpstan + pest (pipeline CI)
```

File test:

```
tests/Feature/
├── Auth/                      # otentikasi (Fortify)
├── Settings/                  # pengaturan profil/appearance/security
├── CustomerTest.php           # CRUD nasabah + validasi + kode CUS
├── LoanTest.php               # lifecycle pinjaman, kalkulasi, state machine
├── InstallmentTest.php        # jadwal angsuran & deteksi keterlambatan
├── PaymentTest.php            # alokasi, overpayment, reversal, imutabilitas
├── CollectionTest.php         # aktivitas LC, Promise-to-Pay, RBAC
├── CollateralTest.php         # penerimaan jaminan, verifikasi, pelepasan
├── IdentityVerificationTest.php # status VERIFIED/FAILED/REQUIRES_REVIEW
├── SecurityAuthorizationTest.php # RBAC ketat: LC/Cashier/Auditor/unauth = 403
├── RbacAccessTest.php         # akses modul mengikuti izin peran
├── ReportsAndAuditLogTest.php # 10 laporan + 6 dokumen cetak + diff JSON audit
├── DashboardTest.php
├── DashboardWidgetTest.php
└── EndToEndTest.php           # 26 langkah skenario E2E kritis lengkap
```

**Status saat ini**: `168 tests / 862 assertions PASS`. Verifikasi Fase 9:
`php artisan migrate:fresh --seed` bersih, Pint clean, PHPStan level clean (0 error).

### Skenario keamanan yang sudah dites

- LC tidak dapat menyetujui pinjaman / reversal pembayaran.
- Cashier tidak dapat melepas jaminan / menyetujui pinjaman.
- Auditor baca-saja (semua aksi mutasi = 403).
- Pengguna tidak berizin diblokir di level route **dan** policy.
- Aksi finansial yang tidak sah ditolak di level service (mis. overpayment, penagihan pinjaman non-aktif, promise-to-pay tanpa hasil PTP).

---

## 20. Status Roadmap 9 Fase

Pembangunan dilakukan **incremental** per fase (bukan dump kode raksasa).

| Fase | Modul | Status |
|------|-------|--------|
| 1 | Foundation: auth, RBAC, schema, layout | ✅ Selesai |
| 2 | Customer Management (+ employment) | ✅ Selesai |
| 3 | Loan Engine & Financials (bunga, state machine, jadwal) | ✅ Selesai (PR #1, #2, #8) |
| 4 | Payment, Allocation & Reversal + kuitansi | ✅ Selesai (PR #3) |
| 5 | Collection / LC Module + dasbor LC | ✅ Selesai (PR #4) |
| 6 | Collateral: penerimaan & penyimpanan | ✅ Selesai (PR #7) |
| 7 | Identity Verification & Collateral Release (8 syarat) | ✅ Selesai (PR #7) |
| 8 | Audit UI, Reports & Printable documents | ✅ Selesai (PR #10) |
| 9 | Testing, Security Review & UX polish | ✅ Selesai (PR #11) |

**Seluruh 9 fase selesai.** Setiap fase diverifikasi: `php artisan migrate:fresh --seed` + `php artisan test` sebelum dilanjutkan.

---

## 21. Keterbatasan Prototipe

NADI adalah **prototipe fungsional end-to-end**, bukan produk produksi. Hal yang **tidak** boleh diklaim:

- Kepatuhan/regulasi (OJK, dsb.), keabsahan hukum, lisensi pinjaman.
- Verifikasi identitas pemerintah resmi (verifikasi di sini hanyalah *workflow record*).
- Sertifikasi keamanan produksi / kepatuhan akuntansi / jaminan perlindungan data.

Batasan fungsional yang jujur untuk dicatat:

| Area | Status di prototipe ini |
|------|-------------------------|
| **Denda/penalty akrual otomatis** | Belum diimplementasikan — struktur data & alokasi denda tersedia, tapi penambahan denda otomatis saat terlambat belum ada |
| **Global search & filter lintas entitas** | Belum dibangun (pencarian & filter per-modul tersedia) |
| **Upload dokumen fisik** | Belum diaktifkan; desain menyimpan di private storage (`storage/app/private/`) |
| **Hardening produksi** | Rate limiting login dasar (Fortify); review keamanan/legal menyeluruh masih diperlukan |

Modul yang **sudah** dibangun penuh dan teruji (bukan lagi batasan): penerimaan & pelepasan jaminan dengan validasi 8 syarat server-side, verifikasi identitas (3 status), dasbor spesifik per-role, audit log dengan diff JSON, 10 laporan berfilter tanggal, 6 dokumen cetak, serta skenario E2E 26 langkah.

Sebelum digunakan di lingkungan nyata, aplikasi memerlukan review profesional (hukum, akuntansi, keamanan), data riil yang sah, dan hardening tambahan.

---

## 22. Laporan Akhir Teknikal

### 22.1 Fitur yang Diimplementasikan

Prototipe fungsional **NADI — Loan Management System** selesai end-to-end (9 fase):

- **Auth & RBAC**: 7 peran (ADMIN, LO, LC, CASHIER, COLLATERAL_OFFICER, VERIFIER, AUDITOR), akses berbasis izin di level route **dan** policy.
- **Nasabah & Pekerjaan**: CRUD dengan validasi ketat, nomor `CUS-YYYY-XXXXXX`, profil pekerjaan, pencarian & filter.
- **Pinjaman**: kalkulasi bunga **FLAT** & **REDUCING_BALANCE** berbasis integer, state machine 11 status, jadwal angsuran otomatis saat pencairan (`NADI-LOAN-YYYY-XXXXXX`).
- **Pembayaran & Reversal**: alokasi bertingkat **Denda → Bunga → Pokok**, imutabilitas pembayaran, koreksi via reversal dengan audit penuh (`PAY-YYYY-XXXXXX`).
- **Penagihan LC**: dasbor tunggakan, aktivitas penagihan, janji bayar (Promise-to-Pay).
- **Jaminan**: penerimaan ber-12-langkah (`COL-YYYY-XXXXXX`), penyimpanan/custody, tanda terima cetak.
- **Verifikasi & Pelepasan**: verifikasi `VERIFIED`/`FAILED`/`REQUIRES_REVIEW`, pengambilan jaminan dengan **8 syarat server-side**, berita acara serah terima (`REL-YYYY-XXXXXX`).
- **Audit & Laporan**: 18+ event audit dengan diff JSON before/after, 10 laporan berfilter tanggal, 6 dokumen cetak standar.
- **UI**: 100% Bahasa Indonesia, dasbor spesifik per peran, modal konfirmasi, empty state informatif, paginasi 15/bagian.

### 22.2 Skema Database

**18 tabel** pada SQLite (`database/database.sqlite`), mata uang tersimpan sebagai **INTEGER Rupiah**:

`users, roles, permissions, role_user, permission_role, customers, employments, loans, loan_status_histories, installments, payments, payment_reversals, collection_activities, collaterals, identity_verifications, collateral_releases, audit_logs, document_references`

### 22.3 Daftar Service Utama (Service Layer)

`CustomerService`, `LoanService`, `LoanStatusService`, `LoanCalculationService`, `InterestCalculationService`, `InstallmentScheduleService`, `PaymentAllocationService`, `PaymentReversalService`, `CollateralService`, `CollateralReleaseService`, `CollectionService`, `AuditLogService`, `SequentialNumberService`, `UserService` — 14 service; seluruh mutasi finansial dibungkus `DB::transaction()`.

### 22.4 Alur Kerja Utama

1. LO mendaftarkan nasabah → mengajukan pinjaman (DRAFT → SUBMITTED).
2. Admin meriview → menyetujui/tolak → mencairkan → jadwal angsuran terbentuk.
3. Petugas jaminan menerima agunan → masuk penyimpanan.
4. Kasir menerima setoran → alokasi Denda→Bunga→Pokok → reversal oleh admin bila keliru.
5. Pelunasan → pinjaman COMPLETED → jaminan eligible.
6. Verifier memverifikasi identitas → petugas jaminan mengecek 8 syarat → serah terima + berita acara.
7. Auditor menginspeksi jejak audit & laporan.

### 22.5 Daftar Akun Demo

Kata sandi semua akun: **`password`**

| Peran | Email |
|-------|-------|
| ADMIN | `admin@example.test` |
| LO | `lo@example.test` |
| LC | `lc@example.test` |
| CASHIER | `cashier@example.test` |
| COLLATERAL OFFICER | `collateral@example.test` |
| IDENTITY VERIFIER | `verifier@example.test` |
| AUDITOR | `auditor@example.test` |

### 22.6 Status Pengujian

- **168 tests / 862 assertions — 100% lulus** (Pest 5).
- Skenario **E2E 26 langkah** (`EndToEndTest`) berjalan penuh tanpa error: nasabah → pinjaman → pencairan → angsuran → jaminan → pelunasan → verifikasi → pelepasan → berita acara → audit kontinu.
- Keamanan RBAC: LC tidak menyetujui pinjaman, Cashier tidak melepas jaminan, Auditor hanya baca-saja, pengguna tidak terautentikasi diblokir (garis besar: HTTP 403/redirect).
- `php artisan migrate:fresh --seed` bersih; Pint clean; PHPStan 0 error.

### 22.7 Batasan Sistem yang Diketahui

Lihat [Bab 21](#21-keterbatasan-prototipe). Ringkas: belum ada akrual denda otomatis, pencarian lintas entitas, upload dokumen aktif, dan hardening produksi. Bukan aplikasi berlisensi / patuh regulasi OJK; verifikasi identitas hanya *workflow record* (tidak terhubung ke instansi).

### 22.8 Petunjuk Menjalankan Aplikasi

```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan serve          # buka http://localhost:8000
```

Verifikasi: `php artisan test` · CI: `composer ci:check`.

---

## 23. Konvensi Git & Kontribusi

Sesuai dokumen direktif proyek (`AGENTS.md`):

- **`main` = branch stabil** — perubahan kode tidak boleh langsung di `main`.
- Setiap fase/modul dikerjakan di branch terpisah:
  - `fase/<nama-fase>` (contoh `fase/6-collateral-release`)
  - branch non-fase (contoh `docs/readme`, `fix/...`)
- Alur: `checkout main` → `git pull` → `checkout -b <branch>` → kerja → commit → push → **PR ke main** → review → merge.
- Format commit: `<Tipe>: <deskripsi>` dengan tipe `Fase X`, `Fix`, `Add`, `Update`, `Refactor`, `Test`, `Docs`, `Chore` (deskripsi 1 baris, tanpa titik akhir).

```bash
git checkout main && git pull origin main
git checkout -b fase/6-collateral-release
# ... kerja ...
git add -A && git commit -m "Fase 6: penerimaan dan penyimpanan jaminan"
git push origin fase/6-collateral-release
# buka PR ke main → review → merge
```

---

## 24. Lisensi

`MIT` — proyek ini dibangun di atas starter kit Laravel resmi (`laravel/livewire-starter-kit`).

---

> **Prototipe NADI**: database, aturan bisnis, izin akses, dan UI menegakkan alur kerja yang sama.
> *Data integrity first — kalkulasi keliru = kegagalan, tanpa audit = kegagalan, pelepasan jaminan tanpa validasi = kegagalan.*