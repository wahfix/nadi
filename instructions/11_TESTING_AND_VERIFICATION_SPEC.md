# NADI — SPESIFIKASI PENGUJIAN OTOMATIS & SKENARIO END-TO-END
**File:** `instructions/11_TESTING_AND_VERIFICATION_SPEC.md`  
**Sumber:** `MASTER_BUILD_SPECIFICATION.md` (Bagian 57, 58)

---

## 1. SUITE PENGUJIAN OTOMATIS MINIMAL (AUTOMATED TESTS)
Setiap modul wajib dilengkapi automated feature tests menggunakan PHPUnit/Pest di dalam folder `tests/Feature/`:

### 1. Modul Nasabah (`CustomerTest`)
- Pengujian pembuatan nasabah baru (*create customer*).
- Pengujian pembaruan profil nasabah (*update customer*).
- Pengujian validasi input nasabah (format telepon, kelengkapan kolom wajib, keunikan kode nasabah).

### 2. Modul Pinjaman (`LoanTest`)
- Pengujian pembuatan pengajuan pinjaman (*create loan draft*).
- Pengujian akurasi rumus kalkulasi bunga FLAT dan REDUCING_BALANCE.
- Pengujian persetujuan pinjaman oleh Admin (*loan approval*).
- Pengujian penolakan pinjaman oleh Admin (*loan rejection*).
- Pengujian aktivasi / pencairan pinjaman (*loan disbursement*).
- Pengujian status pelunasan pinjaman (*loan completion* saat sisa tagihan = 0).

### 3. Modul Angsuran (`InstallmentTest`)
- Pengujian otomatisasi pembentukan jadwal angsuran saat pinjaman dicairkan (*schedule generation*).
- Pengujian deteksi angsuran jatuh tempo dan keterlambatan (*overdue detection*).

### 4. Modul Pembayaran (`PaymentTest`)
- Pengujian alokasi pembayaran bertingkat: **Denda $\to$ Bunga $\to$ Pokok**.
- Pengujian pembayaran sebagian (*partial payment*).
- Pengujian pembayaran lunas angsuran (*full payment*).
- Pengujian pembalikan transaksi pembayaran (*payment reversal*) dan pemulihan saldo pinjaman.

### 5. Modul Penagihan (`CollectionTest`)
- Pengujian pencatatan aktivitas penagihan (*collection activity logging*).
- Pengujian pencatatan janji bayar (*promise-to-pay*).
- Pengujian batasan otorisasi peran LC.

### 6. Modul Jaminan (`CollateralTest`)
- Pengujian penerimaan jaminan fisik (*collateral receiving*).
- Pengujian verifikasi kelayakan pengambilan jaminan (*collateral eligibility check*).
- Pengujian eksekusi serah terima jaminan (*collateral release*).

### 7. Modul Verifikasi Identitas (`IdentityVerificationTest`)
- Pengujian pencatatan verifikasi berhasil (`VERIFIED`).
- Pengujian pencatatan verifikasi gagal (`FAILED`).
- Pengujian status peninjauan ulang (`REQUIRES_REVIEW`).

### 8. Modul Pengujian Keamanan & RBAC (`SecurityAuthorizationTest`)
Pengujian ketat untuk memastikan proteksi server-side:
- Memverifikasi bahwa **LC TIDAK DAPAT menyetujui pinjaman** (HTTP 403).
- Memverifikasi bahwa **Cashier TIDAK DAPAT melepas jaminan** (HTTP 403).
- Memverifikasi bahwa **Auditor TIDAK DAPAT memodifikasi atau menambah data** (HTTP 403).
- Memverifikasi bahwa **Pengguna tidak terotorisasi TIDAK DAPAT melepas jaminan** (HTTP 403).

---

## 2. 26 LANGKAH SKENARIO PENGUJIAN END-TO-END MUTLAK (CRITICAL E2E TEST)
Skenario komprehensif berikut **WAJIB DAPAT DIJALANKAN DARI AWAL HINGGA SELESAI TANPA ERROR**:

1. **Login sebagai Admin**: Masuk dengan akun `admin@example.test`.
2. **Buat Nasabah Sintetis**: Mendaftarkan profil nasabah baru beserta data pekerjaan.
3. **Login sebagai LO (Loan Officer)**: Masuk dengan akun `lo@example.test`.
4. **Buat Pinjaman**: Mengisi formulir pengajuan pinjaman (pokok, bunga, tenor, frekuensi).
5. **Submit Pinjaman**: Mengubah status pinjaman dari `DRAFT` menjadi `SUBMITTED`.
6. **Admin Mereview**: Admin membuka halaman detail pengajuan pinjaman.
7. **Admin Menyetujui Pinjaman**: Pinjaman disetujui, status menjadi `APPROVED`.
8. **Pinjaman Siap Dicairkan**: Status pinjaman beralih ke `READY_FOR_DISBURSEMENT`.
9. **Admin Mencairkan Pinjaman**: Admin mengeksekusi pencairan dana (*disburse*). Status pinjaman menjadi `ACTIVE`.
10. **Sistem Menghasilkan Jadwal Angsuran**: Secara otomatis baris `installments` terisi lengkap.
11. **Collateral Officer Menerima Agunan**: Masuk sebagai `collateral@example.test`, mencatat penerimaan fisik jaminan (status `IN_CUSTODY`) dan menerbitkan tanda terima.
12. **LC Melihat Angsuran Jatuh Tempo**: Masuk sebagai `lc@example.test`, melihat tagihan pada dasbor penagihan.
13. **Cashier Mencatat Pembayaran**: Masuk sebagai `cashier@example.test`, mencatat setoran pembayaran angsuran dari nasabah.
14. **Sistem Mengalokasikan Pembayaran**: `PaymentAllocationService` memotong denda, bunga, lalu pokok.
15. **Status Angsuran Terupdate**: Baris angsuran berubah menjadi `PAID`.
16. **Lanjutkan Pembayaran Hingga Kewajiban Lunas**: Melakukan pembayaran hingga seluruh angsuran lunas (`outstanding_total = 0`, pinjaman menjadi `COMPLETED`).
17. **Jaminan Menjadi Memenuhi Syarat (*Eligible*)**: Sistem mendeteksi pinjaman telah berstatus lunas sempurna.
18. **Identity Verifier Memverifikasi Nasabah**: Masuk sebagai `verifier@example.test`, melakukan pencocokan data fisik nasabah dan menetapkan status `VERIFIED`.
19. **Collateral Officer Membuka Alur Pengambilan Jaminan**: Masuk ke menu Pengambilan Jaminan.
20. **Sistem Memeriksa Kelayakan (*Eligibility Check*)**: Sistem memvalidasi 8 kondisi server-side (seluruh checklist hijau `[✓]`).
21. **Sistem Mengonfirmasi Identitas Terverifikasi**: Menemukan rekaman verifikasi aktif.
22. **Operator Mengonfirmasi Serah Terima**: Menekan tombol konfirmasi pada modal serah terima.
23. **Transaksi Pelepasan Dibuat**: Sistem mencatat nomor pelepasan `REL-YYYY-XXXXXX` di tabel `collateral_releases`.
24. **Status Agunan Berubah Menjadi `RELEASED`**: Kolom `custody_status` menjadi `RELEASED`, `released_by` dan `released_at` terisi.
25. **Cetak Berita Acara Penyerahan**: Sistem menerbitkan bukti serah terima jaminan yang dapat dicetak.
26. **Jejak Audit Lengkap**: Auditor memeriksa tabel `audit_logs` dan memverifikasi seluruh riwayat event dari langkah 1 sampai 25 tercatat secara rinci dan tidak terputus.
