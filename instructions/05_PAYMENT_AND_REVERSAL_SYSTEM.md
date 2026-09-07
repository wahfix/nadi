# NADI — SISTEM PEMBAYARAN, ALOKASI & PEMBALIKAN (REVERSAL)
**File:** `instructions/05_PAYMENT_AND_REVERSAL_SYSTEM.md`  
**Sumber:** `MASTER_BUILD_SPECIFICATION.md` (Bagian 20, 21, 22)

---

## 1. PENCATATAN TRANSAKSI PEMBAYARAN (PAYMENT RECORDING)
Pembayaran dilakukan oleh peran `CASHIER` atau `ADMIN` melalui formulir penerimaan kas.
- **Format Nomor Pembayaran**: `PAY-YYYY-XXXXXX` (contoh: `PAY-2026-000001`).
- **Metode Pembayaran Resmi**:
  - `CASH` (Tunai)
  - `BANK_TRANSFER` (Transfer Bank)
  - `QRIS` (QRIS)
  - `OTHER` (Lainnya)
- **Data yang Disimpan di Tabel `payments`**:
  - `loan_id`, `customer_id`, `installment_id` (jika spesifik ke angsuran tertentu)
  - `payment_date`
  - `amount` (nominal total yang dibayarkan)
  - `principal_component` (alokasi riil ke pokok)
  - `interest_component` (alokasi riil ke bunga)
  - `penalty_component` (alokasi riil ke denda)
  - `payment_method`
  - `reference_number` (nomor referensi bukti transfer atau kuitansi eksternal)
  - `received_by` (ID kasir/pengguna yang mencatat transaksi)
  - `notes`

---

## 2. ATURAN HIERARKI ALOKASI PEMBAYARAN (PAYMENT ALLOCATION)
Seluruh kalkulasi alokasi dana **WAJIB** dieksekusi oleh `PaymentAllocationService`.

### Urutan Alokasi Wajib (Strict Hierarchy):
$$\mathbf{1.\ Denda\ (Penalty)} \longrightarrow \mathbf{2.\ Bunga\ (Interest)} \longrightarrow \mathbf{3.\ Pokok\ (Principal)}$$

### Contoh Kasus Resmi:
- Tagihan Angsuran:
  - Denda: **Rp 0**
  - Bunga: **Rp 100.000**
  - Pokok: **Rp 1.000.000**
  - Total Tagihan: **Rp 1.100.000**
- Pembayaran Masuk: **Rp 1.100.000**
- Hasil Alokasi Riil:
  - `penalty_component` = **Rp 0**
  - `interest_component` = **Rp 100.000**
  - `principal_component` = **Rp 1.000.000**

### Kasus Pembayaran Parsial (*Partial Payment*):
Jika nasabah hanya membayar **Rp 70.000** dari tagihan denda Rp 20.000, bunga Rp 100.000, dan pokok Rp 1.000.000:
1. Denda terbayar lunas: **Rp 20.000** (sisa dana: Rp 50.000)
2. Bunga terbayar sebagian: **Rp 50.000** (sisa tagihan bunga: Rp 50.000)
3. Pokok terbayar: **Rp 0** (sisa tagihan pokok tetap Rp 1.000.000)
4. Status angsuran berubah menjadi **`PARTIALLY_PAID`**.

### Prinsip Penyimpanan Hasil Alokasi:
- Simpan komponen alokasi riil (`penalty_component`, `interest_component`, `principal_component`) secara permanen di baris transaksi pembayaran (`payments`).
- **DILARANG KERAS** menghitung ulang atau menebak alokasi historis di masa depan berdasarkan kondisi pinjaman terkini.

---

## 3. PRINSIP IMUTABILITAS PEMBAYARAN (PAYMENT IMMUTABILITY)
- Transaksi pembayaran yang telah sukses tersimpan di database **DILARANG DIUBAH (EDIT) ATAU DIHAPUS (DELETE)** secara langsung melalui UI atau API.
- Menghapus atau mengubah baris pembayaran secara diam-diam akan merusak integritas buku besar dan jejak audit.

---

## 4. PROSEDUR PEMBALIKAN TRANSAKSI (PAYMENT REVERSAL)
Jika kasir melakukan kesalahan input (misal nominal salah atau salah memilih nomor pinjaman):
1. Pengguna dengan kewenangan berhak (Admin) membuat transaksi pembalikan (**`payment_reversals`**).
2. Data yang dicatat pada tabel `payment_reversals`:
   - `id`
   - `payment_id` (referensi ke pembayaran yang dibalikkan)
   - `reason` (alasan pembatalan secara rinci)
   - `reversed_by` (ID pengguna yang mengeksekusi pembalikan)
   - `reversed_at` (waktu eksekusi pembalikan)
   - `created_at`
3. **Dampak Pembalikan pada Saldo Finansial**:
   - Seluruh mutasi dibungkus dalam `DB::transaction()`.
   - Mengembalikan saldo `outstanding_principal`, `outstanding_interest`, dan `outstanding_penalty` pada pinjaman sejumlah komponen yang sebelumnya telah dipotong.
   - Mengembalikan status angsuran terkait (`PARTIALLY_PAID` atau `PENDING` atau `OVERDUE`).
   - Baris pembayaran asli (`payments`) tetap berada di database sebagai riwayat permanen.
4. **Pencatatan Audit**: Pembalikan wajib menghasilkan log audit bertipe `PAYMENT_REVERSED` beserta rincian JSON saldo sebelum dan sesudah.
