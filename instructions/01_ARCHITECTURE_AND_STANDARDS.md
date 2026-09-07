# NADI — ARSITEKTUR, STANDAR KODE & PRINSIP FINANSIAL
**File:** `instructions/01_ARCHITECTURE_AND_STANDARDS.md`  
**Sumber:** `MASTER_BUILD_SPECIFICATION.md` (Bagian 13, 41, 42, 43, 44, 53, 54, 55, 56, 65)

---

## 1. PEMISAHAN LOGIKA BISNIS (SERVICE LAYER ARCHITECTURE)
Controller di dalam NADI hanya bertindak sebagai **orkestrator tipis** (*Thin Controller*), bukan tempat menimbun logika bisnis.
Seluruh kalkulasi finansial, transisi status, alokasi pembayaran, dan verifikasi kelayakan wajib didelegasikan ke kelas Service khusus.

### 8 Service Utama yang Wajib Dibuat:
1. **`LoanCalculationService`**: Menghitung bunga FLAT dan REDUCING_BALANCE (anuitas bulanan), total kewajiban, dan angsuran per periode.
2. **`InstallmentScheduleService`**: Membentuk dan menyimpan jadwal angsuran (`installments`) secara otomatis saat pinjaman dicairkan (*disbursed*), serta mendeteksi status jatuh tempo dan tunggakan.
3. **`PaymentAllocationService`**: Mengalokasikan dana pembayaran secara ketat dengan urutan: **1. Denda (Penalty) $\to$ 2. Bunga (Interest) $\to$ 3. Pokok (Principal)**.
4. **`LoanStatusService`**: Mesin pengelola status pinjaman (*State Machine*) yang memvalidasi dan mencatat setiap transisi status pinjaman.
5. **`CollateralEligibilityService`**: Memverifikasi secara komprehensif apakah jaminan memenuhi syarat untuk dapat diambil (termasuk saldo kewajiban = 0).
6. **`CollateralReleaseService`**: Mengeksekusi transaksi serah terima jaminan setelah 8 kondisi server-side terpenuhi secara mutlak.
7. **`IdentityVerificationService`**: Mengelola dan mencatat alur verifikasi identitas fisik/manual nasabah pemohon pengambilan jaminan.
8. **`AuditLogService`**: Mencatat setiap aksi mutasi penting secara otomatis ke tabel `audit_logs` dengan payload JSON (`old_values` & `new_values`).

---

## 2. TRANSAKSI DATABASE (DATABASE TRANSACTIONS)
Setiap mutasi kritis pada data finansial dan status aset **WAJIB** dibungkus dalam blok transaksi database:
```php
use Illuminate\Support\Facades\DB;

DB::transaction(function () {
    // Eksekusi mutasi
});
```

### Operasi yang Wajib Menggunakan `DB::transaction()`:
1. **Persetujuan Pinjaman** (*Loan Approval*)
2. **Pencairan Pinjaman** (*Loan Disbursement* & pembentukan jadwal angsuran)
3. **Pencatatan Pembayaran** (*Payment Creation* & update sisa tagihan angsuran/pinjaman)
4. **Pembalikan Pembayaran** (*Payment Reversal* & pemulihan saldo kewajiban)
5. **Penerimaan Jaminan** (*Collateral Receiving*)
6. **Verifikasi Identitas** (*Identity Verification*)
7. **Pelepasan / Serah Terima Jaminan** (*Collateral Release*)

*Aturan Rollback*: Jika salah satu tahapan dalam transaksi mengalami error atau validasi gagal, seluruh perubahan database wajib di-rollback secara otomatis.

---

## 3. KOMPATIBILITAS SQLITE (SQLITE COMPATIBILITY)
Aplikasi NADI sepenuhnya berjalan di atas **SQLite** (`database/database.sqlite`).
- **Larangan Keras**: Dilarang menggunakan sintaks SQL spesifik MySQL (seperti `ENUM`, `ON DUPLICATE KEY UPDATE`, fungsi tanggal MySQL) atau PostgreSQL (seperti `RETURNING`, `JSONB` native operators) yang tidak kompatibel dengan SQLite.
- **Standarisasi**: Utamakan penggunaan Laravel Eloquent ORM dan Fluent Query Builder.
- **Tipe Data SQLite**:
  - Kolom status disimpan sebagai `VARCHAR` / `TEXT` dengan validasi di level PHP (Enum/Form Request/Validation Rule).
  - Kolom JSON disimpan sebagai tipe `TEXT` / `JSON` yang didukung Laravel.
  - Foreign key constraints wajib diaktifkan (`PRAGMA foreign_keys = ON`).

---

## 4. ATURAN PENYIMPANAN NILAI FINANSIAL (MONEY STORAGE)
- **DILARANG menggunakan floating point (`float`, `double`)** untuk menyimpan nominal uang.
- Seluruh nilai nominal mata uang Rupiah disimpan sebagai **INTEGER (Rupiah utuh)** yang merepresentasikan unit mata uang terkecil.
  - Contoh: `Rp 1.500.000` disimpan sebagai integer `1500000`.
  - Jangan simpan sebagai desimal `1500000.00`.
- **Penyimpanan Suku Bunga (Interest Rate)**:
  - Simpan suku bunga secara presisi untuk kalkulasi, misalnya menggunakan basis points (`interest_rate_basis_points`, di mana 2% = 200 bps) atau representasi desimal terstandarisasi (`decimal(8,4)`).
  - Dilarang menggunakan binary floating point untuk kalkulasi suku bunga.
  - Persentase yang ditampilkan ke pengguna harus diformat secara benar (contoh: `2% per bulan`).

---

## 5. FORMAT PENOMORAN BISNIS TERSTANDARISASI (SEQUENTIAL BUSINESS IDENTIFIERS)
Dilarang mengandalkan auto-increment ID database sebagai nomor referensi bisnis kepada pengguna. Gunakan format sequential bisnis yang unik:
- **Nasabah**: `CUS-YYYY-XXXXXX` (contoh: `CUS-2026-000001`)
- **Pinjaman**: `NADI-LOAN-YYYY-XXXXXX` (contoh: `NADI-LOAN-2026-000001`)
- **Pembayaran**: `PAY-YYYY-XXXXXX` (contoh: `PAY-2026-000001`)
- **Jaminan**: `COL-YYYY-XXXXXX` (contoh: `COL-2026-000001`)
- **Pengambilan Jaminan**: `REL-YYYY-XXXXXX` (contoh: `REL-2026-000001`)

---

## 6. FORMAT TAMPILAN UANG & TANGGAL (FORMATTING STANDARDS)
1. **Format Uang (Rupiah)**:
   - Tampilkan nominal uang dengan standar format Indonesia: `Rp 1.500.000`.
   - Di database tersimpan: `1500000`.
   - Buat helper/formatter/Blade component yang reusable untuk konversi tampilan.
2. **Format Tanggal**:
   - Tampilkan tanggal dengan format ramah Indonesia: `10 September 2026` atau `10/09/2026`.
   - Database tetap menggunakan format standar ISO `Y-m-d` atau `Y-m-d H:i:s`.

---

## 7. PRINSIP IMUTABILITAS FINANSIAL (FINANCIAL IMMUTABILITY)
**Dilarang menghitung ulang riwayat transaksi masa lalu berdasarkan nilai variabel terkini yang telah berubah.**
1. Saat pinjaman disetujui/diaktifkan: **Kunci nilai kontraktual pinjaman** ke dalam baris record pinjaman.
2. Saat jadwal angsuran di-generate: **Kunci nilai pokok dan bunga yang jatuh tempo** (`principal_due`, `interest_due`) di setiap baris angsuran.
3. Saat pembayaran terjadi: **Kunci alokasi riil** (`principal_component`, `interest_component`, `penalty_component`) langsung di baris transaksi pembayaran (`payments`).
4. Data historis harus tetap jelas, valid, dan dapat diaudit meskipun di kemudian hari konfigurasi suku bunga atau data sistem berubah.

---

## 8. PRINSIP LARANGAN MUTASI TERSEBUNYI (NO SILENT MUTATION)
Sistem dilarang keras mengubah secara diam-diam (*silent mutation*):
- Pokok pinjaman (*principal*)
- Bunga (*interest*)
- Nominal angsuran (*installment amount*)
- Rekaman pembayaran (*payment*)
- Status jaminan (*collateral status*)
- Rekaman pengeluaran jaminan (*release record*)

Setiap perubahan pada entitas tersebut **wajib** melalui:
1. Aksi eksplisit (*explicit action*).
2. Otorisasi yang sah (*authorized user & policy check*).
3. Pencatatan audit log (*audit record*).

---

## 9. STANDAR KUALITAS KODE (CODE QUALITY GUIDELINES)
- **Gunakan**:
  - Konvensi resmi Laravel.
  - Relasi Eloquent yang eksplisit.
  - Form Requests untuk validasi input.
  - Laravel Policies untuk otorisasi otentik.
  - Service Layer untuk pemisahan logika.
  - Reusable Blade components untuk UI yang konsisten.
  - Named routes di seluruh routing.
  - Migrations, Factories, dan Seeders.
  - Automated feature tests.
- **Hindari / Dilarang**:
  - Controller raksasa (*giant controllers*).
  - Duplikasi logika (*code duplication*).
  - Raw SQL inline jika Eloquent / Query Builder sudah mencukupi.
  - Menaruh rumus finansial di dalam file Blade view.
  - Hardcoded ID pengguna atau peran di dalam kode logic.
  - Hardcoded string status yang tersebar tanpa konstanta/enum.
  - Gunakan PHP Enums, Constants, atau Value Objects untuk status dan tipe data.
