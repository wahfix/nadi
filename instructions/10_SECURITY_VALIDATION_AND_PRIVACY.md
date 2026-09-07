# NADI — KEAMANAN, VALIDASI, PRIVASI & DATA SINTETIS
**File:** `instructions/10_SECURITY_VALIDATION_AND_PRIVACY.md`  
**Sumber:** `MASTER_BUILD_SPECIFICATION.md` (Bagian 45, 46, 47, 49, 50, 52)

---

## 1. KEAMANAN DOKUMEN & PENYIMPANAN PRIVAT (FILE SECURITY)
Jika sistem mengunggah dokumen agunan, KTP sintetis, atau kuitansi:
- **DILARANG** menyimpannya di folder publik (`public/` atau `storage/app/public`).
- Seluruh file wajib disimpan di direktori privat yang terisolasi (`storage/app/private/documents`).
- **Otorisasi Unduhan**: Setiap akses atau unduhan file wajib melalui route controller terproteksi (contoh: `GET /documents/{id}/download`) yang memeriksa izin pengguna melalui Laravel Policy.
- **DILARANG** mengekspos path filesystem mentah ke browser pengguna.
- Validasi tipe MIME file (hanya izinkan ekstensi yang aman seperti PDF, JPG, PNG).
- Batasi ukuran file maksimum (maksimal 2MB - 5MB).
- Generate nama file acak yang aman (*hashed safe filenames*) di sisi server untuk mencegah *path traversal*.
- Gunakan hanya dokumen demo sintetis.

---

## 2. PRIVASI DATA & ATURAN DATA SINTETIS (SYNTHETIC DATA RULES)
Prototipe NADI **WAJIB 100% MENGGUNAKAN DATA SINTETIS**:
- **DILARANG** menggunakan NIK (Nomor Induk Kependudukan) asli, nomor rekening asli, atau nomor telepon orang nyata.
- Gunakan format NIK sintetis acak 16 digit (contoh: `3201010000000001`).
- Jangan mencantumkan informasi nasabah nyata di dalam seeders atau file testing.
- **Penyamaran Data Sensitif (*Data Masking*)**:
  - Pada tampilan publik/daftar tabel umum, samarkan bagian tengah NIK jika diperlukan (contoh: `320101******0001`).
  - Jangan pernah mengekspos NIK di URL parameter (gunakan ID atau `customer_code`).

---

## 3. SPESIFIKASI DATA DEMO SEEDER
Database seeder wajib menghasilkan dataset demo yang realistis dan saling terhubung:
- **20 Nasabah Sintetis** (`customers` & `employments`).
- **15 Pinjaman Aktif** (`loans` dengan status `ACTIVE` dan `OVERDUE`).
- **5 Pinjaman Lunas** (`loans` dengan status `COMPLETED`).
- Ratusan baris jadwal angsuran (`installments`), termasuk beberapa angsuran yang sengaja diset **`OVERDUE`** (jatuh tempo masa lalu dengan sisa tagihan > 0).
- Puluhan transaksi pembayaran kasir (`payments`), lengkap dengan alokasi denda, bunga, dan pokok.
- Riwayat aktivitas penagihan (`collection_activities`) dengan berbagai metode dan janji bayar.
- Puluhan catatan agunan (`collaterals`) dalam status `RECEIVED`, `IN_CUSTODY`, `READY_FOR_RELEASE`, dan `RELEASED`.
- Rekaman verifikasi identitas (`identity_verifications`) dengan hasil `VERIFIED` dan `FAILED`.
- Transaksi serah terima jaminan (`collateral_releases`).
- Jejak audit (`audit_logs`) dari pembuatan data awal.
- **7 Akun Pengguna Demo**:
  - `admin@example.test`
  - `lo@example.test`
  - `lc@example.test`
  - `cashier@example.test`
  - `collateral@example.test`
  - `verifier@example.test`
  - `auditor@example.test`
  - Password seragam: `password`

---

## 4. ATURAN VALIDASI SERVER-SIDE (SERVER-SIDE VALIDATION)
**DILARANG PERCAYA PADA INPUT FRONTEND.** Seluruh validasi wajib ditegakkan di Form Request / Controller:
1. **Pokok Pinjaman (`principal_amount`)**:
   - `required|integer|min:1`
2. **Suku Bunga (`interest_rate`)**:
   - `required|numeric|min:0`
3. **Tenor (`tenor`)**:
   - `required|integer|min:1`
4. **Nomor Telepon (`phone`)**:
   - `required|string|regex:/^[0-9+\-\s]{8,20}$/`
5. **Nasabah (`customer_id`)**:
   - `required|exists:customers,id`
6. **Pinjaman (`loan_id`)**:
   - `required|exists:loans,id`
7. **Jaminan (`collateral_id`)**:
   - `required|exists:collaterals,id`
   - *Validasi Relasi*: Jaminan WAJIB terbukti berelasi dengan `loan_id` yang diajukan.

---

## 5. STANDAR PESAN ERROR RESMI (INDONESIAN ERROR MESSAGES)
Gunakan Bahasa Indonesia yang baku, sopan, dan menjelaskan akar masalah secara eksplisit:

- **Pencegahan Pelepasan Jaminan Saat Belum Lunas**:
  > *«Jaminan belum dapat diserahkan karena pinjaman masih memiliki kewajiban sebesar Rp 1.250.000.»*
- **Kegagalan Verifikasi Identitas**:
  > *«Verifikasi identitas gagal. Data identitas tidak sesuai dengan data nasabah.»*
- **Penolakan Otorisasi / Izin Akses**:
  > *«Anda tidak memiliki izin untuk melakukan tindakan ini.»*
- **Status Jaminan Belum Siap**:
  > *«Jaminan tidak dapat diserahkan karena status jaminan belum Siap Diserahkan (READY_FOR_RELEASE).»*
- **Pembalikan Pembayaran Sudah Pernah Dilakukan**:
  > *«Transaksi pembayaran ini telah dibatalkan sebelumnya dan tidak dapat dibalikkan kembali.»*
