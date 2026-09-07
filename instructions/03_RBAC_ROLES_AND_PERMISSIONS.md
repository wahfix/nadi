# NADI — ROLE-BASED ACCESS CONTROL (RBAC), KEAMANAN & OTORISASI
**File:** `instructions/03_RBAC_ROLES_AND_PERMISSIONS.md`  
**Sumber:** `MASTER_BUILD_SPECIFICATION.md` (Bagian 6, 7, 40, 48, 52)

---

## 1. DAFTAR 7 ROLE UTAMA & BATASAN KEWENANGAN
Sistem NADI memiliki 7 peran (*roles*) dengan batasan kewenangan yang sangat tegas di level server:

### 1. `ADMIN` (Administrator)
- **Kewenangan**: Akses penuh (*Full Access*) ke seluruh modul sistem (Nasabah, Pinjaman, Pembayaran, Penagihan, Jaminan, Verifikasi, Pengguna, Pengaturan, Audit Log, Laporan).
- Dapat melakukan persetujuan/penolakan pinjaman dan pencairan pinjaman.

### 2. `LO` (Loan Officer)
- **Kewenangan**:
  - Membuat data nasabah baru (*create customer*).
  - Mengubah data nasabah (*edit customer*).
  - Membuat pengajuan pinjaman (*create loan application*).
  - Melihat data nasabah binaan (*view assigned customers*).
  - Melihat data pinjaman (*view loans*).
- **Larangan Keras**:
  - Dilarang melakukan approval pinjaman.
  - Dilarang mencairkan pinjaman.
  - Dilarang melepas/menyerahkan jaminan secara sepihak (*cannot independently release collateral*).

### 3. `LC` (Loan Collector)
- **CATATAN PENTING**:
  - `LC` adalah singkatan dari **Loan Collector**. Ini adalah sebutan jabatan operasional internal yang disengaja.
- **Kewenangan**:
  - Melihat daftar debitur yang ditugaskan (*assigned borrowers*).
  - Melihat jadwal angsuran yang jatuh tempo hari ini / minggu ini (*due installments*).
  - Melihat angsuran yang menunggak (*overdue installments*).
  - Mencatat aktivitas penagihan (*record collection activity*).
  - Mencatat janji bayar debitur (*record promise-to-pay*).
  - Melihat status pembayaran nasabah (*view customer payment status*).
- **Larangan Keras**:
  - Dilarang mengubah pokok pinjaman (*modify loan principal*).
  - Dilarang mengubah suku bunga (*change interest*).
  - Dilarang menyetujui pinjaman (*approve loans*).
  - Dilarang membalikkan transaksi pembayaran (*reverse payments*).
  - Dilarang melepas jaminan (*release collateral*).

### 4. `CASHIER` (Kasir)
- **Kewenangan**:
  - Menerima dan mencatat transaksi pembayaran angsuran (*record payments*).
  - Melihat daftar dan rincian transaksi pembayaran (*view payments*).
  - Mencetak kuitansi / tanda terima pembayaran (*generate payment receipts*).
- **Larangan Keras**:
  - Dilarang menyetujui pinjaman (*approve loans*).
  - Dilarang mengubah syarat / ketentuan pinjaman (*change loan terms*).
  - Dilarang melepas jaminan (*release collateral*).

### 5. `COLLATERAL_OFFICER` (Petugas Agunan / Jaminan)
- **Kewenangan**:
  - Menerima agunan/jaminan fisik dari nasabah (*receive collateral*).
  - Memperbarui status dan lokasi penyimpanan jaminan (*update collateral custody*).
  - Mempersiapkan jaminan untuk pengambilan (*prepare collateral for release*).
  - Melakukan eksekusi serah terima fisik jaminan yang telah terotorisasi (*perform authorized collateral handover*).
- **Larangan Keras**:
  - Dilarang melewati/membypass aturan kelayakan finansial (*cannot bypass financial eligibility rules*). Jaminan tidak dapat diserahkan jika kewajiban finansial nasabah belum lunas.

### 6. `IDENTITY_VERIFIER` (Petugas Verifikasi Identitas)
- **Kewenangan**:
  - Melakukan verifikasi identitas nasabah pemohon pengambilan jaminan (*verify identity*).
  - Menolak verifikasi identitas yang tidak cocok (*reject verification*).
  - Memeriksa riwayat verifikasi identitas (*review verification history*).
- **Larangan Keras**:
  - Dilarang melepas/menyerahkan jaminan secara sepihak (*cannot independently release collateral*). Verifikasi hanyalah satu prasyarat sebelum petugas agunan melakukan serah terima fisik.

### 7. `AUDITOR` (Auditor)
- **Kewenangan**:
  - Akses *Read-Only* ke seluruh data sistem (Nasabah, Pinjaman, Pembayaran, Agunan, Verifikasi Identitas, Penagihan, Audit Log, dan Laporan).
- **Larangan Keras**:
  - Dilarang menambah, mengubah, atau menghapus data apa pun di dalam sistem (*cannot modify records*).

---

## 2. PRINSIP OTORISASI SERVER-SIDE (AUTHORIZATION ENFORCEMENT)
- **Aturan Mutlak**: DILARANG hanya mengandalkan penyembunyian tombol di antarmuka (UI).
- Sistem wajib memvalidasi izin di sisi server menggunakan **Laravel Policies, Gates, dan Middleware**.
- *Contoh Kasus*: Jika seorang pengguna jahat (*malicious user*) mengirim HTTP request langsung secara manual:
  ```http
  POST /collaterals/{id}/release
  ```
  Server WAJIB memeriksa:
  1. Izin peran pengguna (*role permission*).
  2. Status pinjaman terkait (*loan state*).
  3. Status fisik agunan (*collateral state*).
  4. Hasil verifikasi identitas pemohon (*identity verification = VERIFIED*).
  5. Kelayakan finansial (*financial eligibility: outstanding = 0*).
  Jika salah satu gagal, server **wajib melempar HTTP 403 Forbidden** atau pesan error bisnis yang jelas dan memblokir operasi secara mutlak.

---

## 3. AKUN PENGGUNA DEMO SEEDER (DEMO USERS)
Database seeder wajib membuat 7 akun pengguna sintetis berikut:

| No | Peran / Jabatan | Email Demo | Password Dev | Deskripsi Hak Akses Utama |
|:---|:---|:---|:---|:---|
| 1 | `ADMIN` | `admin@example.test` | `password` | Akses penuh seluruh modul & persetujuan |
| 2 | `LO` | `lo@example.test` | `password` | Input nasabah & draft pengajuan pinjaman |
| 3 | `LC` | `lc@example.test` | `password` | Penagihan, janji bayar, cek tunggakan |
| 4 | `CASHIER` | `cashier@example.test` | `password` | Input pembayaran angsuran & cetak kuitansi |
| 5 | `COLLATERAL_OFFICER`| `collateral@example.test`| `password` | Penerimaan agunan, custody, serah terima |
| 6 | `IDENTITY_VERIFIER` | `verifier@example.test` | `password` | Verifikasi identitas pemohon jaminan |
| 7 | `AUDITOR` | `auditor@example.test` | `password` | Read-only audit & laporan sistem |

*Catatan Keamanan*: Password disimpan dalam database menggunakan `Hash::make()` (bcrypt hashing). Dilarang keras menyimpan password dalam format plaintext.

---

## 4. PERSYARATAN KEAMANAN APLIKASI (SECURITY REQUIREMENTS)
Sistem wajib mengimplementasikan:
1. **Proteksi CSRF**: Pada seluruh form dan request mutasi (`@csrf`).
2. **Otentikasi & Sesi**: Session security dengan proteksi regenerasi session ID saat login.
3. **Password Hashing**: Menggunakan algoritma hash resmi Laravel.
4. **Rate Limiting**: Diterapkan pada endpoint login untuk mencegah *brute-force attacks*.
5. **Private Document Storage**: Seluruh berkas identitas atau dokumen agunan disimpan di direktori privat (`storage/app/private`), bukan di `public/`.
6. **Authorized Download**: Setiap akses dokumen wajib melalui controller terproteksi otorisasi, tidak boleh membuka path filesystem secara mentah.
7. **Pencatatan Audit Trail**: Setiap perubahan data penting otomatis tercatat di `audit_logs`.
8. **Proteksi Rahasia**: Dilarang hardcode kredensial produksi atau API keys ke dalam repositori.
