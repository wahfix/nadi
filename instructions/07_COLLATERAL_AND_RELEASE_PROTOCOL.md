# NADI — MANAJEMEN AGUNAN, VERIFIKASI IDENTITAS & PROTOKOL PENGAMBILAN JAMINAN
**File:** `instructions/07_COLLATERAL_AND_RELEASE_PROTOCOL.md`  
**Sumber:** `MASTER_BUILD_SPECIFICATION.md` (Bagian 24, 25, 26, 27, 28, 29, 30, 59)

---

## 1. JENIS & STATUS PENYIMPANAN AGUNAN (COLLATERAL CUSTODY)
- **Format Kode Jaminan**: `COL-YYYY-XXXXXX` (contoh: `COL-2026-000001`).
- **Jenis Agunan yang Didukung (`collateral_type`)**:
  - `DOCUMENT`: Dokumen berharga (Sertifikat Tanah, BPKB, Ijazah, dll.)
  - `VEHICLE`: Kendaraan bermotor (Motor, Mobil, dll.)
  - `ELECTRONIC`: Perangkat elektronik (Laptop, Handphone, dll.)
  - `OTHER`: Aset fisik lainnya dengan deskripsi bebas.
  - *Catatan Kepatuhan*: Jangan berasumsi bahwa suatu aset otomatis sah secara hukum perdata sebagai jaminan fidusia/gadai. Sistem mencatat informasi sesuai input operasional.
- **Status Agunan (`custody_status`)**:
  - `PENDING`: Terdaftar dalam pengajuan namun belum diserahkan ke kantor.
  - `RECEIVED`: Telah diserahkan nasabah dan diterima petugas.
  - `IN_CUSTODY`: Tersimpan aman di brankas/gudang penyimpanan.
  - `READY_FOR_RELEASE`: Telah disiapkan untuk diserahkan kembali kepada nasabah.
  - `RELEASED`: Telah diserahterimakan kepada pihak yang berhak.
  - `DISPUTED`: Sedang dalam sengketa atau kendala kepemilikan.

---

## 2. 12 LANGKAH OPERASIONAL PENERIMAAN JAMINAN (COLLATERAL RECEIVING)
Ketika petugas jaminan (`COLLATERAL_OFFICER` atau `ADMIN`) menerima fisik agunan:
1. **Pilih Pinjaman**: Memilih nomor pinjaman terkait (`loans`).
2. **Konfirmasi Nasabah**: Memverifikasi kesesuaian data nasabah pemilik jaminan.
3. **Pilih Jenis Agunan**: Memilih tipe (`DOCUMENT`, `VEHICLE`, dll.).
4. **Input Nomor Identifikasi**: Memasukkan nomor BPKB, nomor sertifikat, nomor polisi, atau serial number.
5. **Input Deskripsi**: Rincian fisik aset (merek, tipe, tahun, warna).
6. **Input Nilai Taksasi**: Nilai estimasi agunan dalam integer Rupiah (`estimated_value`).
7. **Catat Kondisi Fisik**: Deskripsi kondisi fisik aset saat diterima.
8. **Catat Lokasi Penyimpanan**: Nomor rak/brankas/gudang penempatan aset (`storage_location`).
9. **Catat Petugas Penerima**: Menyimpan ID petugas yang menerima fisik (`received_by`).
10. **Generate Nomor Jaminan**: Sistem men-generate sequential identifier `COL-YYYY-XXXXXX`.
11. **Simpan dalam `DB::transaction()`**: Data tersimpan dengan status `RECEIVED` atau `IN_CUSTODY`.
12. **Catat Audit Log & Cetak Tanda Terima**: Menerbitkan Surat Tanda Terima Titipan Jaminan (*Printable Collateral Receipt*) dan merekam log `COLLATERAL_RECEIVED`.

---

## 3. VERIFIKASI IDENTITAS PEMOHON PENGAMBILAN (IDENTITY VERIFICATION)
Sebelum jaminan diserahkan, identitas fisik pemohon wajib diverifikasi oleh `IDENTITY_VERIFIER` atau `ADMIN` dan dicatat ke tabel `identity_verifications`.
- **Metode Verifikasi (`verification_method`)**:
  - `GOVERNMENT_ID`: Pemeriksaan fisik KTP-el / Paspor / SIM.
  - `ACCOUNT_MATCH`: Pencocokan data buku rekening bank atas nama nasabah.
  - `MANUAL_CHECK`: Pemeriksaan manual berkas fisik dan pencocokan tanda tangan.
  - `OTHER`: Metode verifikasi internal lainnya.
- **Hasil Verifikasi (`result`)**:
  - `VERIFIED`: Identitas terbukti cocok dan sah 100%.
  - `FAILED`: Identitas tidak cocok atau diragukan keasliannya.
  - `REQUIRES_REVIEW`: Memerlukan pemeriksaan lebih lanjut oleh pimpinan/supervisor.
- *Catatan Penting*: Untuk prototipe, verifikasi identitas adalah rekaman alur kerja operasional (*workflow record*). Jangan mengklaim atau berpura-pura bahwa verifikasi manual ini setara dengan API resmi kependudukan pemerintah.

---

## 4. 8 ATURAN MUTLAK PENGAMBILAN JAMINAN (SERVER-SIDE ENFORCEMENT)
Validasi di level server **WAJIB** memastikan **8 KONDISI MUTLAK** berikut terpenuhi sebelum jaminan dapat diubah statusnya menjadi `RELEASED`:

1. **Jaminan Terdaftar**: Agunan fisik terdaftar di database (`collateral exists`).
2. **Asosiasi Sah**: Agunan terasosiasi secara sah dengan pinjaman yang bersangkutan (`collateral belongs to loan`).
3. **Pinjaman Memenuhi Syarat**: Pinjaman berstatus eligible untuk release (`loan is eligible`).
4. **Kewajiban Finansial Lunas**: Seluruh kewajiban finansial telah lunas (`outstanding_principal = 0`, `outstanding_interest = 0`, `outstanding_penalty = 0`). Total tagihan = **Rp 0**.
5. **Status Fisik Agunan**: Agunan berstatus `READY_FOR_RELEASE` di dalam gudang/penyimpanan.
6. **Identitas Terverifikasi**: Terdapat rekaman verifikasi identitas pemohon dengan status `VERIFIED`.
7. **Petugas Berwenang**: Eksekusi dilakukan oleh pengguna berhak (`COLLATERAL_OFFICER` atau `ADMIN`).
8. **Transaksi Valid**: Transaksi pengeluaran lengkap dengan nama penerima, hubungan kekerabatan, dan saksi internal.

### Konsekuensi Pelanggaran:
Jika salah satu dari ke-8 kondisi tersebut gagal:
**BLOKIR OPERASI DI LEVEL SERVER DENGAN PESAN ERROR BAHASA INDONESIA YANG TEGAS.**
DILARANG menyediakan jalan pintas (*unrestricted override*).

---

## 5. 11 TAHAPAN ALUR PENGAMBILAN JAMINAN (RELEASE WORKFLOW)
Operator dipandu melalui antarmuka 11 tahapan sistematis:
- **LANGKAH 1**: Cari data nasabah dan nomor kontrak pinjaman.
- **LANGKAH 2**: Pilih agunan yang akan diambil.
- **LANGKAH 3**: Sistem menampilkan ringkasan data:
  - Nama Nasabah
  - Nomor Pinjaman
  - Saldo Tunggakan / Kewajiban Berjalan
  - Kode Jaminan
  - Deskripsi Agunan
  - Status Penyimpanan Agunan
- **LANGKAH 4**: Jalankan proses verifikasi identitas pemohon.
- **LANGKAH 5**: Tampilkan hasil verifikasi identitas (`VERIFIED` / `FAILED`).
- **LANGKAH 6**: Jika hasil `VERIFIED` dan kewajiban lunas, tampilkan tombol konfirmasi penyerahan.
- **LANGKAH 7**: Operator mengonfirmasi serah terima melalui modal konfirmasi eksplisit.
- **LANGKAH 8**: Sistem membuat baris baru di tabel `collateral_releases` dengan nomor sequential `REL-YYYY-XXXXXX`.
- **LANGKAH 9**: Sistem mengubah `custody_status` pada jaminan menjadi **`RELEASED`**, mengisi `released_by` dan `released_at`.
- **LANGKAH 10**: Sistem menerbitkan Berita Acara Serah Terima Jaminan (*Collateral Release Receipt*) yang siap dicetak.
- **LANGKAH 11**: Sistem membuat catatan jejak audit (`COLLATERAL_RELEASED`).
- *Seluruh langkah kritis (Langkah 8 s/d 11) wajib berjalan di dalam `DB::transaction()`*.

---

## 6. USER EXPERIENCE & VISUAL CHECKLIST
Pada antarmuka pengeluaran jaminan, tampilkan daftar periksa (*checklist*) visual yang jelas kepada operator:

```
[✓] Kontrak pinjaman terdaftar sah
[✓] Identitas nasabah sesuai
[✓] Seluruh kewajiban finansial lunas (Sisa: Rp 0)
[✓] Jaminan fisik terdaftar
[✓] Status jaminan: Siap Diserahkan
[✓] Identitas penerima terverifikasi (VERIFIED)
[✓] Petugas berwenang terotorisasi
--------------------------------------------------
STATUS: JAMINAN SIAP DISERAHKAN
[ Tombol: Konfirmasi Penyerahan Jaminan ]
```

Bila salah satu syarat belum terpenuhi:
```
[✗] Kewajiban finansial belum lunas (Sisa Tagihan: Rp 1.250.000)
--------------------------------------------------
STATUS: JAMINAN TIDAK DAPAT DISERAHKAN
(Tombol penyerahan dinonaktifkan secara permanen)
```
