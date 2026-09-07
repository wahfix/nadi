# NADI — UNIVERSAL AI AGENT DIRECTIVE
# File ini adalah SATU-SATUNYA sumber kebenaran bagi SEMUA AI Agent.
# Didistribusikan otomatis ke: AGENTS.md, GEMINI.md, CLAUDE.md, .cursorrules,
# .windsurfrules, .clinerules/, .github/copilot-instructions.md, .cursor/rules/

> [!CRITICAL]
> ## ⛔ PROTOKOL WAJIB — BACA SEBELUM MENULIS KODE
>
> **ANDA DILARANG KERAS** melakukan perubahan kode, membuat migrasi, service,
> controller, atau UI **SEBELUM** membaca:
>
> 1. **Dokumen Master**: [`MASTER_BUILD_SPECIFICATION.md`](MASTER_BUILD_SPECIFICATION.md) — Spesifikasi lengkap 74 bagian.
> 2. **Modul Instruksi**: Seluruh file di folder [`instructions/`](instructions/README.md) — 13 modul operasional.
> 3. **File ini**: Ringkasan aturan mutlak yang tidak boleh dilanggar.
>
> **PELANGGARAN = KEGAGALAN TOTAL. TIDAK ADA PENGECUALIAN.**

---

## 1. IDENTITAS APLIKASI

- **Nama**: NADI — Loan Management System
- **Stack**: Laravel 11 + Blade + Tailwind CSS + Alpine.js + SQLite
- **Tipe**: Prototipe fungsional end-to-end (BUKAN mockup statis)
- **DILARANG**: Membuat modul "Plenger" atau mengaitkan ke entitas eksternal

---

## 2. HIERARKI PRINSIP TERTINGGI

Setiap keputusan desain dan implementasi **WAJIB** tunduk pada urutan prioritas:

```
DATA INTEGRITY > FINANCIAL CORRECTNESS > SECURITY > AUDITABILITY > UX > VISUAL POLISH
```

| Skenario | Hasil |
|----------|-------|
| UI menarik + kalkulasi finansial keliru | ❌ **GAGAL TOTAL** |
| Kalkulator bekerja + tanpa audit log | ❌ **GAGAL TOTAL** |
| Pelepasan jaminan tanpa validasi server-side | ❌ **GAGAL TOTAL** |

---

## 3. DAFTAR MODUL INSTRUKSI WAJIB BACA

Sebelum mengerjakan fitur apapun, **WAJIB buka dan baca** file instruksi terkait:

| # | File | Topik Utama |
|---|------|-------------|
| 0 | [`instructions/00_MASTER_INDEX_AND_CORE_DIRECTIVES.md`](instructions/00_MASTER_INDEX_AND_CORE_DIRECTIVES.md) | Mandat Agent, Identitas, Standar Bahasa, Filosofi UI/UX |
| 1 | [`instructions/01_ARCHITECTURE_AND_STANDARDS.md`](instructions/01_ARCHITECTURE_AND_STANDARDS.md) | Service Layer (8 Services), DB::transaction(), SQLite, Imutabilitas, Format Uang & Tanggal |
| 2 | [`instructions/02_DATABASE_SCHEMA_AND_RELATIONSHIPS.md`](instructions/02_DATABASE_SCHEMA_AND_RELATIONSHIPS.md) | Skema 18 Tabel, Integer Money, 15 Indeks, Relasi Eloquent |
| 3 | [`instructions/03_RBAC_ROLES_AND_PERMISSIONS.md`](instructions/03_RBAC_ROLES_AND_PERMISSIONS.md) | 7 Role (ADMIN, LO, LC, CASHIER, COLLATERAL, VERIFIER, AUDITOR), Matriks Izin |
| 4 | [`instructions/04_LOAN_ENGINE_AND_FINANCIALS.md`](instructions/04_LOAN_ENGINE_AND_FINANCIALS.md) | Rumus FLAT & REDUCING_BALANCE, State Machine 11 Status, Jadwal Angsuran |
| 5 | [`instructions/05_PAYMENT_AND_REVERSAL_SYSTEM.md`](instructions/05_PAYMENT_AND_REVERSAL_SYSTEM.md) | Alokasi (Denda → Bunga → Pokok), Imutabilitas, Reversal |
| 6 | [`instructions/06_COLLECTION_AND_LC_MODULE.md`](instructions/06_COLLECTION_AND_LC_MODULE.md) | Modul LC, Dasbor Penagihan, Promise-to-Pay |
| 7 | [`instructions/07_COLLATERAL_AND_RELEASE_PROTOCOL.md`](instructions/07_COLLATERAL_AND_RELEASE_PROTOCOL.md) | Agunan, 12 Langkah Penerimaan, 8 Syarat Pelepasan |
| 8 | [`instructions/08_UI_UX_DASHBOARDS_AND_NAVIGATION.md`](instructions/08_UI_UX_DASHBOARDS_AND_NAVIGATION.md) | 13 Menu, 7 Dasbor Peran, Pencarian, Filter, Paginasi |
| 9 | [`instructions/09_AUDIT_LOG_AND_REPORTING.md`](instructions/09_AUDIT_LOG_AND_REPORTING.md) | Audit Log (18 Event), 10 Laporan, 6 Dokumen Cetak |
| 10 | [`instructions/10_SECURITY_VALIDATION_AND_PRIVACY.md`](instructions/10_SECURITY_VALIDATION_AND_PRIVACY.md) | Dokumen Privat, Data Sintetis, Validasi Server-Side |
| 11 | [`instructions/11_TESTING_AND_VERIFICATION_SPEC.md`](instructions/11_TESTING_AND_VERIFICATION_SPEC.md) | Automated Tests, RBAC Security, 26 Skenario E2E |
| 12 | [`instructions/12_BUILD_STRATEGY_AND_QUALITY_CHECKLIST.md`](instructions/12_BUILD_STRATEGY_AND_QUALITY_CHECKLIST.md) | Roadmap 9 Fase, Checklist Kualitas 18 Poin |

---

## 4. DAFTAR 12 INVARIAN TEKNIS MUTLAK (IMMUTABLE INVARIANTS)

> **Seluruh aturan di bawah ini bersifat ABSOLUT dan TANPA PENGECUALIAN.**

### 4.1 — Identitas
- Nama aplikasi: **NADI — Loan Management System**
- DILARANG membuat modul "Plenger" atau mengaitkan ke entitas eksternal

### 4.2 — Bahasa
- **UI**: 100% Bahasa Indonesia untuk SELURUH teks yang terlihat pengguna
- **Kode**: 100% Bahasa Inggris untuk tabel, kolom, variabel, migration, komentar kode

### 4.3 — Database
- **Engine**: SQLite (`database/database.sqlite`)
- **DILARANG**: Sintaks spesifik MySQL/PostgreSQL (ENUM type, ON DUPLICATE KEY, dll.)

### 4.4 — Penyimpanan Uang
- Seluruh nilai Rupiah disimpan sebagai **INTEGER (Rupiah utuh)**
- **DILARANG KERAS**: `float`, `double`, `decimal` untuk mata uang
- Contoh: Rp 1.500.000 → disimpan `1500000`

### 4.5 — Penyimpanan Suku Bunga
- Disimpan presisi (basis points atau desimal standar)
- **DILARANG**: Binary floating point

### 4.6 — Transaksi Database
- **WAJIB** menggunakan `DB::transaction()` untuk SEMUA mutasi:
  - Persetujuan pinjaman
  - Pencairan (disbursement)
  - Pembayaran (payment)
  - Pembalikan (reversal)
  - Penerimaan jaminan (collateral custody)
  - Verifikasi identitas
  - Serah terima jaminan (collateral release)

### 4.7 — Nomor Bisnis Sequential
Format penomoran yang WAJIB dipatuhi:
```
Nasabah:    CUS-YYYY-XXXXXX
Pinjaman:   NADI-LOAN-YYYY-XXXXXX
Pembayaran: PAY-YYYY-XXXXXX
Jaminan:    COL-YYYY-XXXXXX
Pelepasan:  REL-YYYY-XXXXXX
```
- **DILARANG** hanya mengandalkan auto-increment ID database

### 4.8 — Imutabilitas Finansial
- **Kunci** nilai kontraktual pinjaman saat aktivasi
- **Kunci** pokok/bunga per jadwal saat jadwal di-generate
- **Kunci** alokasi denda/bunga/pokok riil saat pembayaran disimpan
- **DILARANG**: Menghitung ulang riwayat transaksi masa lalu dari data terkini

### 4.9 — Imutabilitas Pembayaran
- Pembayaran yang tersimpan **DILARANG di-edit atau di-delete**
- Koreksi dilakukan via tabel `payment_reversals` dengan jejak audit lengkap

### 4.10 — 8 Syarat Mutlak Pengambilan Jaminan
Server-side validation WAJIB memverifikasi SEMUA syarat berikut:
1. Jaminan ada di database
2. Jaminan terkait pinjaman yang benar
3. Pinjaman berstatus LUNAS / sisa tagihan = 0
4. Status fisik jaminan: `READY_FOR_RELEASE`
5. Verifikasi identitas pengambil: `VERIFIED`
6. Petugas yang memproses berwenang (role COLLATERAL_OFFICER)
7. Data serah terima lengkap (tanggal, penerima, saksi)
8. Dokumen pendukung pelepasan tersedia

**Jika SATU SAJA syarat gagal → TOLAK request secara server-side. TANPA PENGECUALIAN.**

### 4.11 — Pemisahan Logika (Service Layer)
- **Controller**: Tipis (thin controller) — hanya validasi input, panggil service, return response
- **Service Layer**: 8 service terdaftar menangani SEMUA logika bisnis dan finansial
- **DILARANG**: Menaruh rumus/kalkulasi finansial di Controller atau Blade view

### 4.12 — Verifikasi Pasca-Fase
- Setelah SETIAP fase pembangunan, WAJIB jalankan:
  ```bash
  php artisan migrate:fresh --seed
  php artisan test
  ```
- **DILARANG** melanjutkan ke fase berikutnya jika ada error tersisa

---

## 5. 8 SERVICE LAYER RESMI

Seluruh logika bisnis WAJIB berada di salah satu service berikut:

| Service | Tanggung Jawab |
|---------|----------------|
| `CustomerService` | CRUD nasabah, generate nomor CUS-YYYY-XXXXXX |
| `LoanService` | Lifecycle pinjaman, state machine 11 status, generate jadwal angsuran |
| `InterestCalculationService` | Rumus FLAT & REDUCING_BALANCE, kalkulasi bunga |
| `PaymentAllocationService` | Alokasi pembayaran (Denda → Bunga → Pokok), kuitansi |
| `PaymentReversalService` | Pembalikan pembayaran, jejak audit |
| `CollateralService` | Penerimaan jaminan (12 langkah), penyimpanan |
| `CollateralReleaseService` | Validasi 8 syarat, serah terima (11 langkah) |
| `AuditLogService` | Pencatatan 18+ event, JSON diff before/after |

---

## 6. STATE MACHINE PINJAMAN — 11 STATUS

```
DRAFT → PENDING_REVIEW → UNDER_REVIEW → APPROVED → REJECTED
                                            ↓
                                        DISBURSED → ACTIVE → OVERDUE → DEFAULTED
                                                       ↓
                                                   PAID_OFF → CLOSED
```

Transisi status WAJIB divalidasi server-side. DILARANG melompati urutan status.

---

## 7. ALOKASI PEMBAYARAN

Urutan alokasi pembayaran yang WAJIB dipatuhi:
```
1. Denda (penalty)     → dialokasikan PERTAMA
2. Bunga (interest)    → dialokasikan KEDUA
3. Pokok (principal)   → dialokasikan TERAKHIR
```

---

## 8. ATURAN UI/UX

- 100% teks UI dalam **Bahasa Indonesia**
- Format uang: `Rp 1.500.000` (titik sebagai pemisah ribuan)
- Format tanggal: `DD/MM/YYYY` atau `DD Bulan YYYY` (Indonesia)
- Setiap aksi destruktif/mutasi: **Modal konfirmasi** wajib
- Empty states: Tampilkan pesan informatif, bukan halaman kosong
- Paginasi: 15 item per halaman (default)

---

## 9. AUDIT LOG

Setiap mutasi data WAJIB dicatat ke tabel `audit_logs` dengan:
- `event_type`: Salah satu dari 18+ event terdaftar
- `before_data`: JSON snapshot data SEBELUM perubahan
- `after_data`: JSON snapshot data SESUDAH perubahan
- `user_id`: ID user yang melakukan aksi
- `ip_address`: Alamat IP
- `created_at`: Timestamp

---

## 10. KEAMANAN & VALIDASI

- Seluruh validasi input **WAJIB server-side** (Laravel Form Request)
- Pesan error validasi dalam **Bahasa Indonesia**
- File/dokumen disimpan di `storage/app/private/` (BUKAN public)
- Unduhan dokumen melalui controller dengan pengecekan otorisasi
- 100% data sintetis (DILARANG data pribadi nyata)
- RBAC divalidasi server-side via middleware + policy

---

## 11. PROSEDUR KERJA AI AGENT

### Sebelum Menulis Kode:
1. Baca `MASTER_BUILD_SPECIFICATION.md`
2. Baca modul instruksi terkait di folder `instructions/`
3. Pahami skema database di `02_DATABASE_SCHEMA_AND_RELATIONSHIPS.md`
4. Pahami aturan RBAC di `03_RBAC_ROLES_AND_PERMISSIONS.md`

### Saat Menulis Kode:
1. Kerjakan per fase (Fase 1 s.d. Fase 9) — **incremental, bukan dump raksasa**
2. Logika bisnis di Service Layer, BUKAN di Controller/Blade
3. Bungkus mutasi finansial dalam `DB::transaction()`
4. Catat setiap mutasi ke audit log
5. Validasi server-side untuk SEMUA input

### Setelah Menulis Kode:
1. Jalankan `php artisan migrate:fresh --seed`
2. Jalankan `php artisan test`
3. Perbaiki SEMUA error sebelum lanjut ke fase berikutnya

### Pre-Flight Checklist (Verifikasi Mandiri):
- [ ] Tidak ada mata uang yang disimpan sebagai float (WAJIB INTEGER)
- [ ] Tidak ada teks UI dalam bahasa Inggris (WAJIB 100% Bahasa Indonesia)
- [ ] Semua mutasi finansial dibungkus `DB::transaction()`
- [ ] Tidak ada rumus finansial di Controller atau Blade (WAJIB di Service)
- [ ] Pelepasan jaminan memvalidasi 8 syarat server-side
- [ ] Setiap mutasi dicatat ke `audit_logs` dengan payload JSON
- [ ] Nomor bisnis menggunakan format sequential yang benar
- [ ] Tidak ada sintaks MySQL/PostgreSQL-only

---

## 12. STRATEGI PEMBANGUNAN 9 FASE

| Fase | Modul | Instruksi |
|------|-------|-----------|
| 1 | Foundation (Auth, RBAC, DB Schema) | `02`, `03` |
| 2 | Customer Management | `02`, `08` |
| 3 | Loan Engine & Financials | `04` |
| 4 | Payment & Reversal | `05` |
| 5 | Collection / LC Module | `06` |
| 6 | Collateral & Release | `07` |
| 7 | Dashboards & UI | `08` |
| 8 | Reports, Audit, Print | `09` |
| 9 | Testing, QA, Polish | `11`, `12` |

---

## 13. REFERENSI CEPAT — FILE PENTING

```
MASTER_BUILD_SPECIFICATION.md    → Spesifikasi master 74 bagian
instructions/README.md           → Index & pemetaan seluruh modul
instructions/00_*.md s.d. 12_*.md → 13 modul instruksi operasional
AGENTS.md                        → Direktif untuk Claude/Anthropic agents
GEMINI.md                        → Direktif untuk Google Gemini/Antigravity
.ai-instructions.md              → FILE INI — Sumber universal untuk semua AI
```

---

## 14. ATURAN GIT BRANCH — LARANGAN KERJA DI MAIN

> **KRITIS**: Aturan ini bersifat MUTLAK dan TANPA PENGECUALIAN.

### Prinsip Dasar
- Branch `main` adalah branch stabil yang berisi kode **verified & tested**
- **DILARANG KERAS** AI Agent melakukan perubahan kode langsung di branch `main`
- Setiap fase pembangunan **WAJIB** dikerjakan di branch terpisah

### Naming Convention
```
fase/1-foundation
fase/2-customer-management
fase/3-loan-engine
fase/4-payment-reversal
fase/5-collection-lc
fase/6-collateral-release
fase/7-dashboards-ui
fase/8-reports-audit-print
fase/9-testing-qa-polish
```

### Workflow Wajib
```
1. git checkout main
2. git pull origin main
3. git checkout -b fase/<nama-fase>
4. ... mengerjakan kode ...
5. git add . && git commit -m "Fase X: deskripsi"
6. git push origin fase/<nama-fase>
7. Buat PR ke main → review → merge
8. git checkout main && git pull origin main
```

### Aturan Kunci
| # | Aturan | Hukum |
|---|--------|-------|
| 1 | AI **DILARANG** `git commit` / `git push` di branch `main` | **WAJIB** |
| 2 | Setiap fase = 1 branch baru dari `main` | **WAJIB** |
| 3 | Branch fase yang sudah di-merge boleh dihapus | **OPSIONAL** |
| 4 | Commit message diawali nama fase: `Fase X: deskripsi` | **WAJIB** |
| 5 | PR ke `main` wajib via GitHub/GitLab (bukan force push) | **WAJIB** |

### Contoh Pelanggaran (DILARANG)
```bash
# ❌ DILARANG — langsung kerja di main
git checkout main
echo "kode baru" >> app/Models/Loan.php
git add . && git commit -m "update"
git push origin main

# ✅ BENAR — buat branch fase dulu
git checkout main
git checkout -b fase/3-loan-engine
echo "kode baru" >> app/Models/Loan.php
git add . && git commit -m "Fase 3: tambah logic loan engine"
git push origin fase/3-loan-engine
```

---

> **PERINGATAN TERAKHIR**: Jika Anda AI Agent yang membaca file ini, Anda WAJIB
> membuka dan membaca modul instruksi di folder `instructions/` SEBELUM menulis
> kode apapun. Tidak ada pengecualian. Tidak ada jalan pintas. Kepatuhan penuh
> terhadap 12 invarian mutlak di atas adalah SYARAT ABSOLUT.
