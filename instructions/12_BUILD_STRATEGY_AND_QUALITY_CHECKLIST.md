# NADI — STRATEGI PEMBANGUNAN 9 FASE, VERIFIKASI & QUALITY ASSURANCE
**File:** `instructions/12_BUILD_STRATEGY_AND_QUALITY_CHECKLIST.md`  
**Sumber:** `MASTER_BUILD_SPECIFICATION.md` (Bagian 66, 69, 70, 71, 72, 73)

---

## 1. STRATEGI PEMBANGUNAN BERTAHAP (9-PHASE INCREMENTAL BUILD)
Dilarang keras melakukan dumping kode raksasa yang tidak terstruktur (*unstructured giant code dump*).
Pembangunan aplikasi NADI wajib mengikuti 9 fase terukur:

```
FASE 1: Project Setup, Autentikasi, Database SQLite, RBAC (Roles & Permissions), Layout Tailwind UI
   ↓
FASE 2: Modul Nasabah & Profil Pekerjaan (CRUD, Validasi, Pencarian & Filter)
   ↓
FASE 3: Modul Pinjaman, Kalkulator Bunga (Flat & Reducing), State Machine, Generate Jadwal Angsuran
   ↓
FASE 4: Modul Pembayaran Kasir, Alokasi Bertingkat (Denda->Bunga->Pokok), Pembalikan (Reversal)
   ↓
FASE 5: Modul Penagihan (LC Dashboard, Aktivitas Penagihan, Janji Bayar)
   ↓
FASE 6: Modul Agunan/Jaminan (Penerimaan, Tanda Terima Cetak, Lokasi Penyimpanan/Custody)
   ↓
FASE 7: Modul Verifikasi Identitas & Alur Pengambilan Jaminan (8 Syarat Server, Checklist UI, Berita Acara)
   ↓
FASE 8: Modul Audit Log (Diff Viewer JSON), 10 Laporan Finansial, Format Dokumen Cetak Standar
   ↓
FASE 9: Pengujian Otomatis (Feature Tests), Security Review, Final UX Polish & Verifikasi E2E
```

---

## 2. DISIPLIN VERIFIKASI SETELAH SETIAP FASE (AFTER EACH PHASE)
Setelah menyelesaikan setiap fase di atas, Agent **WAJIB** mengeksekusi 9 langkah verifikasi sebelum melangkah ke fase berikutnya:
1. **Jalankan Migrasi**: Pastikan skema database termigrasi bersih tanpa syntax error SQLite (`php artisan migrate`).
2. **Jalankan Seeders**: Pastikan dataset seeder berjalan lancar (`php artisan db:seed`).
3. **Jalankan Automated Tests**: Pastikan tidak ada pengujian yang gagal (`php artisan test`).
4. **Periksa Error Log**: Buka `storage/logs/laravel.log` untuk memastikan tidak ada unhandled exceptions.
5. **Perbaiki Segera (*Fix Immediately*)**: Selesaikan semua bug sebelum berpindah fase. Dilarang menimbun error.
6. **Verifikasi Relasi Database**: Pastikan relasi Eloquent berjalan dua arah tanpa *lazy loading violation*.
7. **Verifikasi Otorisasi Peran**: Pastikan middleware, policy, dan gate mengunci akses yang dilarang.
8. **Verifikasi Alur UI**: Pastikan navigasi, form submit, toast notifikasi, dan modal berfungsi normal.
9. **Lanjutkan Hanya Bila Fase Bekerja 100%**: Berpindah ke fase selanjutnya hanya jika fase saat ini lulus seluruh kriteria.

---

## 3. CHECKLIST KUALITAS AKHIR (FINAL QUALITY CHECK - 18 POIN)
Sebelum prototipe dinyatakan selesai, verifikasi 18 poin mutlak berikut:
- [ ] 1. Aplikasi boot tanpa error.
- [ ] 2. Login dan otentikasi bekerja sempurna.
- [ ] 3. Seluruh 7 peran (*roles*) dapat login dan memiliki akses sesuai spesifikasi.
- [ ] 4. Seluruh migrasi database bekerja mulus di driver SQLite.
- [ ] 5. Dataset demo seeder berhasil mengisi 20 nasabah, 15 pinjaman aktif, 5 lunas, dll.
- [ ] 6. CRUD nasabah & riwayat pekerjaan bekerja dengan validasi ketat.
- [ ] 7. Kalkulasi bunga FLAT dan REDUCING_BALANCE menghasilkan angka yang presisi.
- [ ] 8. Pembentukan jadwal angsuran (*installment schedule*) otomatis terbentuk saat pencairan.
- [ ] 9. Alokasi pembayaran berurutan secara ketat: Denda $\to$ Bunga $\to$ Pokok.
- [ ] 10. Pembalikan pembayaran (*reversal*) memulihkan saldo dan tercatat di audit log.
- [ ] 11. Modul penagihan LC mencatat riwayat kontak dan janji bayar debitur.
- [ ] 12. Penerimaan jaminan mencatat lokasi penyimpanan dan mencetak tanda terima.
- [ ] 13. Verifikasi identitas pemohon berjalan dengan status `VERIFIED`, `FAILED`, `REQUIRES_REVIEW`.
- [ ] 14. Proteksi pengambilan jaminan secara server-side memblokir rilis jika saldo belum 0 atau salah satu dari 8 syarat gagal.
- [ ] 15. Jejak audit (*audit logs*) mencatat seluruh mutasi dan diff JSON Before/After dapat diinspeksi.
- [ ] 16. Seluruh 10 modul laporan bekerja dengan filter tanggal.
- [ ] 17. Seluruh 6 dokumen cetak standar NADI tampil dengan format kop dan area tanda tangan yang rapi.
- [ ] 18. Akses tidak terotorisasi diblokir secara server-side dan seluruh unit/feature test lulus 100%.

---

## 4. BATASAN PROTOTIPE (PROTOTYPE LIMITATIONS & DISCLAIMER)
Sistem ini adalah prototipe fungsional berstandar tinggi.
**DILARANG MENGKLAIM SECARA TIDAK BENAR HAL-HAL BERIKUT:**
- Kepatuhan regulasi otoritas jasa keuangan riil (*regulatory compliance*).
- Keabsahan legal hukum perdata/perbankan formal (*legal validity*).
- Verifikasi identitas terhubung ke API resmi instansi kependudukan pemerintah.
- Sertifikasi keamanan produksi tingkat industri (*production security certification*).
- Kepatuhan akuntansi perbankan formal (*formal banking accounting compliance*).
- Izin usaha pembiayaan/simpan pinjam resmi (*lending license*).
- Kepatuhan perlindungan data pribadi tingkat perbankan (*guaranteed data protection compliance*).

Pada file `README.md`, wajib dicantumkan bab khusus mengenai batasan ini serta area yang membutuhkan tinjauan hukum, keamanan, dan kepatuhan perbankan sebelum dapat di-deploy ke lingkungan komersial nyata.

---

## 5. FORMAT LAPORAN AKHIR TEKNIKAL (FINAL TECHNICAL SUMMARY)
Saat pekerjaan selesai, sajikan ringkasan komprehensif yang memuat 8 bagian teknis:
1. **Fitur yang Diimplementasikan (*What was implemented*)**
2. **Skema Database (*Database schema & tables summary*)**
3. **Daftar Service Utama (*Main services in Service Layer*)**
4. **Alur Kerja Utama (*Main business workflows executed*)**
5. **Daftar Akun Demo (*Demo accounts & credentials*)**
6. **Status Pengujian (*Test execution status & passing assertions*)**
7. **Batasan Sistem yang Diketahui (*Known limitations*)**
8. **Petunjuk Menjalankan Aplikasi (*How to run the application locally*)**

*Dilarang hanya mengatakan "aplikasi telah selesai" tanpa memaparkan bukti teknis yang nyata.*
