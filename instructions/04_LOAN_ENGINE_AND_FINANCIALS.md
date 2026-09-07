# NADI — KALKULATOR BUNGA, MESIN PINJAMAN & JADWAL ANGSURAN
**File:** `instructions/04_LOAN_ENGINE_AND_FINANCIALS.md`  
**Sumber:** `MASTER_BUILD_SPECIFICATION.md` (Bagian 11, 12, 13, 14, 15, 16, 17, 18, 19)

---

## 1. METODE KALKULASI BUNGA (INTEREST METHODS)
Sistem NADI wajib mendukung 2 metode perhitungan bunga finansial melalui service khusus: `LoanCalculationService`.
**DILARANG menempatkan rumus finansial di file Blade views.**

### 1. Metode Bunga Tetap (FLAT)
- **Rumus Dasar**:
  $$\text{total\_interest} = \text{principal} \times \text{interest\_rate} \times \text{tenor}$$
  $$\text{total\_payable} = \text{principal} + \text{total\_interest}$$
  $$\text{installment\_amount} = \frac{\text{total\_payable}}{\text{tenor}}$$
- **Contoh Kasus Resmi**:
  - Pokok Pinjaman (*Principal*): **Rp 10.000.000**
  - Suku Bunga (*Rate*): **2% per bulan** (0.02)
  - Tenor (*Tenor*): **10 bulan**
  - Total Bunga: $10.000.000 \times 2\% \times 10 = \mathbf{Rp\ 2.000.000}$
  - Total Tagihan (*Total Payable*): $10.000.000 + 2.000.000 = \mathbf{Rp\ 12.000.000}$
  - Angsuran per Bulan (*Installment*): $\frac{12.000.000}{10} = \mathbf{Rp\ 1.200.000}$

### 2. Metode Bunga Efektif Menurun (REDUCING_BALANCE)
- Implementasikan perhitungan anuitas standar dengan bunga periodik bulanan untuk angsuran bulanan.
- **Rumus Anuitas**:
  $$A = \frac{P \times r \times (1+r)^n}{(1+r)^n - 1}$$
  di mana:
  - $A$ = Besaran angsuran per periode (*Installment amount*)
  - $P$ = Pokok pinjaman (*Principal amount*)
  - $r$ = Suku bunga periodik (*Periodic interest rate*, misal per bulan)
  - $n$ = Jumlah periode angsuran (*Tenor*)
- **Penanganan Khusus (*Edge Case*)**:
  - Jika $r = 0$, maka rumus anuitas menjadi:
    $$A = \frac{P}{n}$$
- Untuk pembentukan jadwal angsuran (*amortization schedule*):
  - Bunga bulan ke-$k$: $\text{interest}_k = \text{remaining\_principal}_{k-1} \times r$
  - Pokok bulan ke-$k$: $\text{principal}_k = A - \text{interest}_k$
  - Seluruh nilai dibulatkan ke **Integer Rupiah utuh** tanpa floating point. Selisih pembulatan rupiah (*rounding discrepancy*) dialokasikan pada angsuran periode terakhir agar total pokok lunas sempurna.

---

## 2. PENYIMPANAN SUKU BUNGA (INTEREST RATE STORAGE)
- Simpan suku bunga secara presisi untuk menghindari distorsi finansial.
- Gunakan basis points (`interest_rate_basis_points`, contoh: `200` untuk `2.00%`) atau kolom desimal terstandarisasi.
- Dilarang menggunakan *binary floating point* (`float`/`double`) di level bahasa pemrograman atau database.
- Tampilan persentase di UI diformat secara benar sesuai standar Indonesia (contoh: `2% per bulan`).

---

## 3. TENOR & FREKUENSI ANGSURAN
- **Tenor**:
  - Mendukung konfigurasi fleksibel: **1 bulan, 2 bulan, 3 bulan, 6 bulan, 12 bulan**.
  - Disimpan sebagai **integer jumlah periode pembayaran**.
- **Frekuensi Angsuran (*Installment Frequency*)**:
  - Mendukung: **`MONTHLY`** (Bulanan) dan **`WEEKLY`** (Mingguan).
  - Struktur kode harus terisolasi rapi sehingga frekuensi lain (misal dua mingguan / tahunan) dapat ditambahkan di kemudian hari tanpa merombak skema inti.

---

## 4. STATUS & MESIN TRANSISI PINJAMAN (LOAN STATE MACHINE)
Sistem memiliki 11 status pinjaman resmi:
```
1. DRAFT                    7. ACTIVE
2. SUBMITTED                8. OVERDUE
3. UNDER_REVIEW             9. COMPLETED
4. APPROVED                10. DEFAULTED
5. REJECTED                11. CANCELLED
6. READY_FOR_DISBURSEMENT
```

### Diagram Alur Transisi Resmi:
```
[DRAFT]
   ↓
[SUBMITTED]
   ↓
[UNDER_REVIEW]
   ↓
[APPROVED]   atau   [REJECTED]
   ↓
[READY_FOR_DISBURSEMENT]
   ↓
[ACTIVE]
   ↓
[COMPLETED]
   atau
[ACTIVE] → [OVERDUE] → [ACTIVE] / [COMPLETED] / [DEFAULTED]
```

### Aturan Ketat:
1. **Dilarang melakukan perubahan status sembarangan / acak**. Setiap transisi harus melewati `LoanStatusService`.
2. Setiap perubahan status **wajib dicatat** ke tabel `loan_status_histories` lengkap dengan ID pengguna yang mengubah dan alasan transisi.

---

## 5. FORMULIR PENGAJUAN PINJAMAN (LOAN APPLICATION SCREEN)
Formulir pembuatan pinjaman memiliki input:
- Nasabah (*Customer*)
- Pokok Pinjaman (*Principal Amount*)
- Suku Bunga (*Interest Rate*)
- Metode Bunga (*Interest Method*: `FLAT` / `REDUCING_BALANCE`)
- Tenor (*Tenor*)
- Frekuensi Angsuran (*Installment Frequency*: `MONTHLY` / `WEEKLY`)
- Tanggal Pencairan Rencana (*Disbursement Date*)
- Tanggal Jatuh Tempo Pertama (*First Due Date*)

### Live Calculation Preview (Pratinjau Kalkulasi Real-Time):
Ketika operator mengubah nilai input (misal pokok, bunga, atau tenor), UI wajib menampilkan pratinjau kalkulasi seketika tanpa perlu reload halaman:
- Pokok Pinjaman (*Principal*)
- Suku Bunga (*Interest Rate*)
- Metode Bunga (*Interest Method*)
- Tenor (*Tenor*)
- Total Bunga (*Total Interest*)
- Total Kewajiban Bayar (*Total Payable*)
- Besaran Angsuran per Periode (*Installment Amount*)
- Tanggal Jatuh Tempo Pertama (*First Due Date*)
- Tanggal Jatuh Tempo Akhir (*Maturity Date*)

*Konfirmasi Mutlak*: Mewajibkan modal konfirmasi sebelum pengajuan disimpan/disubmit.

---

## 6. PEMBENTUKAN JADWAL ANGSURAN (INSTALLMENT GENERATION)
Saat pinjaman dicairkan (*disbursed*):
1. `InstallmentScheduleService` secara otomatis membentuk seluruh baris jadwal angsuran di tabel `installments`.
2. Setiap baris angsuran memiliki:
   - `installment_number` (1..n)
   - `due_date` (dihitung berdasarkan frekuensi)
   - `principal_due` (kewajiban pokok)
   - `interest_due` (kewajiban bunga)
   - `penalty_due` (0 pada awal pembentukan)
   - `total_due` (`principal_due` + `interest_due`)
   - `remaining_amount` (sama dengan `total_due`)
   - `status`: **`PENDING`**
3. Status angsuran yang didukung:
   - `PENDING`: Belum jatuh tempo, belum ada pembayaran.
   - `PARTIALLY_PAID`: Telah dibayar sebagian namun belum lunas.
   - `PAID`: Telah lunas 100%.
   - `OVERDUE`: Tanggal saat ini melewati `due_date` dan sisa tagihan > 0.
   - `WAIVED`: Dihapuskan berdasarkan kebijakan resmi.
