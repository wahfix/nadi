# NADI — ANTARMUKA PENGGUNA (UI/UX), NAVIGASI & DASBOR SPESIFIK ROLE
**File:** `instructions/08_UI_UX_DASHBOARDS_AND_NAVIGATION.md`  
**Sumber:** `MASTER_BUILD_SPECIFICATION.md` (Bagian 4, 5, 32, 33, 34, 35, 36, 37, 51, 61, 62, 63, 64)

---

## 1. 13 MENU NAVIGASI SIDEBAR UTAMA
Navigasi bilah samping (*sidebar*) memiliki 13 menu baku berbahasa Indonesia dan visibilitasnya wajib menghormati izin peran (*permissions*):
1. **Dashboard** (Dasbor ringkasan sesuai peran)
2. **Nasabah** (Daftar & pembuatan profil debitur)
3. **Pinjaman** (Daftar pengajuan, review, & kontrak pinjaman)
4. **Angsuran** (Daftar jadwal angsuran, jatuh tempo & tunggakan)
5. **Pembayaran** (Pencatatan kasir, kuitansi & riwayat setoran)
6. **Penagihan** (Monitoring debitur tertunggak, kontak & janji bayar)
7. **Jaminan** (Penerimaan, penyimpanan & monitoring fisik agunan)
8. **Verifikasi Identitas** (Antrean verifikasi identitas pemohon)
9. **Pengambilan Jaminan** (Alur 8-syarat pelepasan agunan)
10. **Pengguna** (Manajemen akun staf, peran & izin akses)
11. **Laporan** (10 modul laporan operasional & finansial)
12. **Audit Log** (Jejak audit komprehensif & JSON diff viewer)
13. **Pengaturan** (Konfigurasi sistem umum)

---

## 2. ADAPTASI DASBOR BERDASARKAN ROLE (ROLE-SPECIFIC DASHBOARDS)
Setiap pengguna yang login melihat widget dasbor yang disesuaikan secara presisi dengan tanggung jawab jabatannya (tidak menampilkan kontrol finansial yang tidak relevan):

- **Admin Dashboard**:
  - *Portfolio Overview*: Total Nasabah, Pinjaman Aktif, Outstanding Pokok, Outstanding Bunga, Total Portofolio Pinjaman.
  - *Collection Overview*: Tagihan Jatuh Tempo Hari Ini, Tagihan Terlambat/Menunggak, Jatuh Tempo Minggu Ini, Komitmen Janji Bayar.
  - *Collateral Overview*: Total Agunan, Agunan Dalam Penyimpanan, Agunan Siap Diambil, Agunan Sudah Diserahkan.
  - *Recent Activity*: Log transaksi pembayaran terbaru, persetujuan pinjaman, penerimaan jaminan, verifikasi identitas, dan serah terima jaminan.
- **LO (Loan Officer) Dashboard**:
  - Statistik nasabah binaan sendiri.
  - Status pengajuan pinjaman (`DRAFT`, `SUBMITTED`, `UNDER_REVIEW`, `APPROVED`).
  - Tombol cepat pendaftaran nasabah & pengajuan pinjaman baru.
- **LC (Loan Collector) Dashboard**:
  - Total nasabah dalam penanganan (*assigned borrowers*).
  - Daftar angsuran jatuh tempo hari ini (*due today*) & jatuh tempo minggu ini (*due this week*).
  - Daftar angsuran menunggak (*overdue*).
  - Total sisa tagihan berjalan (*total outstanding*).
  - Pemantauan janji bayar (*promise-to-pay*).
- **Cashier Dashboard**:
  - Ringkasan penerimaan kas hari ini.
  - Daftar pembayaran terbaru.
  - Pintasan input pembayaran & cetak kuitansi.
- **Collateral Officer Dashboard**:
  - Agunan dalam masa simpan (*in custody*).
  - Agunan yang siap diserahkan (*ready for release* - pinjaman lunas).
  - Riwayat serah terima jaminan terakhir.
- **Identity Verifier Dashboard**:
  - Antrean permohonan verifikasi identitas pengambilan jaminan.
  - Riwayat verifikasi sukses & gagal.
- **Auditor Dashboard**:
  - Grafik tren aktivitas sistem.
  - Ringkasan mutasi audit log terbaru (diff per jam).
  - Akses cepat ke 10 laporan operasional.

---

## 3. HALAMAN RINCIAN NASABAH (CUSTOMER DETAIL PAGE)
Halaman detail nasabah (`/customers/{id}`) wajib memiliki 7 bagian/tab terstruktur:
1. **Ringkasan (*Overview*)**: Identitas pribadi, NIK (disamarkan jika perlu), kontak, profil pekerjaan, dan status keanggotaan.
2. **Pinjaman (*Loans*)**: Tabel riwayat seluruh pinjaman yang pernah dan sedang berjalan.
3. **Pembayaran (*Payments*)**: Riwayat seluruh pembayaran yang pernah dilakukan nasabah.
4. **Jaminan (*Collaterals*)**: Seluruh agunan milik nasabah beserta status penyimpanannya.
5. **Penagihan (*Collection*)**: Catatan interaksi penagihan dan riwayat janji bayar.
6. **Verifikasi Identitas (*Verification*)**: Riwayat pemeriksaan identitas fisik nasabah.
7. **Audit Log (*Audit*)**: Rekaman perubahan data profil nasabah.

---

## 4. HALAMAN RINCIAN PINJAMAN (LOAN DETAIL PAGE)
Halaman detail pinjaman (`/loans/{id}`) memuat data header kontrak (Pokok, Bunga, Metode, Tenor, Angsuran, Sisa Saldo, Status, Petugas Pembuat, Tanggal Persetujuan, Tanggal Pencairan) dan 6 tab konten:
1. **Tab Ringkasan**: Rincian finansial pinjaman dan progres pelunasan.
2. **Tab Angsuran**: Tabel jadwal angsuran lengkap dengan kolom jatuh tempo, pokok, bunga, denda, sisa, dan badge status.
3. **Tab Pembayaran**: Riwayat pembayaran yang dialokasikan pada pinjaman ini.
4. **Tab Jaminan**: Daftar agunan yang mengikat pinjaman ini.
5. **Tab Penagihan**: Riwayat kunjungan/kontak penagihan terkait keterlambatan pinjaman ini.
6. **Tab Riwayat**: Kronologi transisi status pinjaman (`loan_status_histories`).

---

## 5. PENCARIAN GLOBAL & FILTER DATA (SEARCH & FILTERS)
1. **Pencarian Global (*Global Search Bar*)**:
   - Mampu mencari secara cerdas berdasarkan: **Kode Nasabah, Nama Nasabah, Nomor Telepon, Nomor KTP (NIK), Nomor Kontrak Pinjaman, Kode Jaminan, Nomor Pembayaran**.
   - Hasil pencarian difilter sesuai otorisasi peran yang login.
2. **Filter Tabel**:
   - **Tabel Pinjaman**: Filter status pinjaman, rentang tanggal, nasabah, status keterlambatan (*overdue*).
   - **Tabel Pembayaran**: Filter rentang tanggal, metode pembayaran, kasir/petugas, nomor pinjaman.
   - **Tabel Jaminan**: Filter status penyimpanan (*custody status*), jenis agunan, lokasi penyimpanan.
   - **Tabel Nasabah**: Filter status nasabah, perusahaan tempat kerja, status pekerjaan.
3. **Paginasi Wajib (*Pagination*)**:
   - Seluruh tabel yang berpotensi memiliki data besar WAJIB menggunakan paginasi (contoh: 15 / 25 baris per halaman).
   - Dilarang memuat data tanpa batas (*unlimited rows*) ke dalam satu halaman.

---

## 6. MODAL KONFIRMASI EKSPLISIT (CONFIRMATION MODALS)
Setiap tindakan operasional berisiko tinggi WAJIB menampilkan modal konfirmasi:
- Persetujuan Pinjaman (*Approval*)
- Penolakan Pinjaman (*Rejection*)
- Pencairan Pinjaman (*Disbursement*)
- Pencatatan Pembayaran (*Payment*)
- Pembalikan Pembayaran (*Reversal*)
- Hasil Verifikasi Identitas (*Identity Verification*)
- Pengambilan Jaminan (*Collateral Release*)

### Format Modal Konfirmasi Pengambilan Jaminan:
Wajib menampilkan ringkasan verifikasi:
```
- Nama Nasabah: [Nama]
- Nomor Pinjaman: [No Pinjaman]
- Sisa Tagihan Finansial: Rp 0 (Lunas)
- Kode Agunan: [Kode Agunan]
- Deskripsi Agunan: [Deskripsi]
- Status Agunan: Siap Diserahkan
- Hasil Verifikasi: Terverifikasi (VERIFIED)
- Petugas Penyerah: [Nama Operator]
- Nama Penerima: [Nama Penerima]

Pertanyaan: "Konfirmasi pengambilan jaminan?"
[ Batal ]  [ Ya, Serahkan Jaminan ]
```

---

## 7. EMPTY STATES, LOADING STATES & RESPONSIF
- **Empty States**: Setiap tabel tanpa data harus menampilkan pesan informatif dalam Bahasa Indonesia dan tombol ajakan aksi (contoh: *«Belum ada jaminan.»* dengan tombol *«Tambah Jaminan»*).
- **Loading & Error States**:
  - Tombol aksi wajib menonaktifkan diri (*disabled*) saat proses request berlangsung untuk mencegah *double submission*.
  - Menampilkan notifikasi sukses (*flash toast*) berwarna hijau atau notifikasi gagal berwarna merah.
- **Responsif UI**:
  - Desktop: Sidebar permanen di sisi kiri + konten utama di sisi kanan.
  - Mobile: Sidebar dapat dibuka-tutup (*collapsible*).
  - Tabel: Mendukung *horizontal scrolling* yang rapi pada layar sempit tanpa merusak tata letak.
  - Formulir: Beralih menjadi satu kolom (*single column*) pada layar smartphone.
