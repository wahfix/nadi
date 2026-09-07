# NADI — JEJAK AUDIT (AUDIT LOG), LAPORAN & DOKUMEN CETAK
**File:** `instructions/09_AUDIT_LOG_AND_REPORTING.md`  
**Sumber:** `MASTER_BUILD_SPECIFICATION.md` (Bagian 31, 38, 39, 60)

---

## 1. SISTEM JEJAK AUDIT (AUDIT LOG SYSTEM)
Setiap mutasi penting pada sistem wajib dicatat secara otomatis melalui `AuditLogService` ke dalam tabel `audit_logs`:
- `user_id`: ID staf yang melakukan aksi (atau `null` bila aksi sistem terjadwal).
- `action`: Nama aksi sistem (lihat 18 event minimal di bawah).
- `entity_type`: Nama model terkait (misal: `App\Models\Loan`).
- `entity_id`: ID primary key record yang dimutasi.
- `old_values`: Nilai sebelum mutasi dalam format JSON string.
- `new_values`: Nilai setelah mutasi dalam format JSON string.
- `ip_address`: Alamat IP pengguna.
- `user_agent`: Informasi browser / peramban klien.
- `created_at`: Waktu pencatatan.

### 18 Aksi Minimal yang Wajib Dicatat:
```
 1. CUSTOMER_CREATED          10. PAYMENT_REVERSED
 2. CUSTOMER_UPDATED          11. COLLECTION_CREATED
 3. LOAN_CREATED              12. COLLATERAL_RECEIVED
 4. LOAN_SUBMITTED            13. IDENTITY_VERIFIED
 5. LOAN_APPROVED             14. IDENTITY_FAILED
 6. LOAN_REJECTED             15. RELEASE_CREATED
 7. LOAN_DISBURSED            16. COLLATERAL_RELEASED
 8. LOAN_STATUS_CHANGED       17. USER_CREATED
 9. PAYMENT_CREATED           18. PERMISSION_CHANGED
```

### Larangan Keras:
- **DILARANG** menyediakan fitur penghapusan data audit log melalui antarmuka normal UI aplikasi. Log audit bersifat permanen dan tidak boleh dimanipulasi.

---

## 2. TAMPILAN HALAMAN AUDIT LOG & JSON DIFF VIEWER
Halaman Audit Log (`/audit-logs`) menyajikan tabel kronologis dengan kolom:
- **Waktu**: Tanggal dan jam aksi (format WIB: `dd/mm/YYYY HH:ii:ss`).
- **Pengguna**: Nama dan email staf pelaksana.
- **Aksi**: Badge tipe aksi (contoh: `LOAN_APPROVED`, `PAYMENT_CREATED`).
- **Modul**: Nama entitas yang berubah (Nasabah, Pinjaman, Pembayaran, dll.).
- **Record**: ID atau Nomor Bisnis entitas (misal: `NADI-LOAN-2026-000001`).
- **Perubahan**: Tombol untuk melihat detail perbandingan data.

### Fitur Inspeksi Modal JSON (Before & After Diff):
Ketika baris audit log diklik, sistem membuka modal inspeksi yang menampilkan:
- **Before (Sebelum)**: Tampilan format JSON dari nilai lama (`old_values`).
- **After (Sesudah)**: Tampilan format JSON dari nilai baru (`new_values`).
Hal ini memungkinkan auditor untuk menelusuri secara presisi apa saja kolom dan saldo yang mengalami perubahan.

---

## 3. 10 MODUL LAPORAN RESMI (STANDARD REPORTS)
Modul Laporan (`/reports`) menyediakan 10 jenis laporan operasional dan finansial:
1. **Daftar Nasabah**: Laporan seluruh debitur terdaftar berdasarkan periode registrasi dan status keaktifan.
2. **Daftar Pinjaman**: Laporan seluruh kontrak pinjaman berdasarkan status, metode bunga, dan tenor.
3. **Outstanding Pinjaman**: Laporan sisa pokok pinjaman, sisa bunga, dan total portofolio berjalan.
4. **Jatuh Tempo**: Laporan angsuran yang jatuh tempo dalam rentang tanggal tertentu.
5. **Tunggakan**: Laporan debitur yang mengalami keterlambatan pembayaran (*overdue installments*) dan durasi hari keterlambatan (*DPD - Days Past Due*).
6. **Pembayaran**: Laporan rekapitulasi penerimaan kas/transfer, perincian alokasi pokok, bunga, denda, serta pembalikan (*reversals*).
7. **Jaminan**: Laporan inventaris fisik agunan, lokasi penyimpanan (*storage custody*), dan taksasi nilai.
8. **Pengambilan Jaminan**: Laporan serah terima agunan yang telah selesai diserahkan kepada debitur.
9. **Aktivitas LC**: Laporan kinerja Loan Collector, rincian kunjungan/telepon penagihan, dan realisasi janji bayar.
10. **Audit Log**: Rekapitulasi jejak mutasi sistem dan aktivitas pengguna.

*Fitur Wajib Laporan*:
- Filter rentang tanggal awal dan tanggal akhir.
- Tampilan ramah cetak (*Print-friendly view*) menggunakan CSS `@media print`.

---

## 4. 6 DOKUMEN CETAK STANDAR NADI (PRINTABLE DOCUMENTS)
Sistem NADI wajib menyediakan halaman khusus cetak (*printable view*) untuk 6 dokumen operasional:
1. **Ringkasan Pinjaman (*Loan Summary*)**: Lembar kontrak ringkas informasi pinjaman nasabah.
2. **Jadwal Angsuran (*Installment Schedule*)**: Tabel jadwal pembayaran angsuran dari periode 1 hingga selesai.
3. **Kuitansi Pembayaran (*Payment Receipt*)**: Tanda terima pembayaran angsuran dari kasir.
4. **Surat Tanda Terima Jaminan (*Collateral Receipt*)**: Bukti resmi penyerahan fisik agunan oleh nasabah saat pengajuan pinjaman.
5. **Hasil Verifikasi Identitas (*Identity Verification Result*)**: Formulir berita acara pemeriksaan fisik KTP/identitas nasabah.
6. **Berita Acara Pengambilan Jaminan (*Collateral Release Receipt*)**: Dokumen hukum serah terima pengembalian agunan fisik kepada nasabah.

### Standar Tata Letak Dokumen Cetak NADI:
Setiap dokumen cetak wajib memuat elemen berikut:
```
========================================================================
                              NADI
                     Loan Management System
------------------------------------------------------------------------
Judul Dokumen  : [Nama Dokumen, misal: BUKTI PEMBAYARAN ANGSURAN]
Nomor Dokumen  : [Nomor Transaksi, misal: PAY-2026-000001]
Tanggal Cetak  : [Format Tanggal Indonesia, misal: 10 September 2026]
------------------------------------------------------------------------
Informasi Nasabah:
- Nama Lengkap : [Nama Nasabah]
- Kode Nasabah : [CUS-2026-000001]
- Kontak / HP  : [Nomor HP]
------------------------------------------------------------------------
Rincian Transaksi / Finansial / Agunan Terkait:
[Tabel rincian nominal pembayaran, jadwal angsuran, atau spesifikasi agunan]
------------------------------------------------------------------------
Petugas Berwenang: [Nama dan Jabatan Petugas NADI]

        Tanda Tangan Nasabah / Penerima        Petugas Berwenang NADI


        (______________________________)      (______________________________)
========================================================================
```
