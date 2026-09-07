# NADI — MODUL PENAGIHAN & LOAN COLLECTOR (LC)
**File:** `instructions/06_COLLECTION_AND_LC_MODULE.md`  
**Sumber:** `MASTER_BUILD_SPECIFICATION.md` (Bagian 7, 23, 61)

---

## 1. IDENTITAS JABATAN & MANDAT LOAN COLLECTOR (LC)
- **LC** adalah singkatan resmi dari **Loan Collector**.
- Penamaan ini adalah sebutan operasional internal yang disengaja di dalam sistem NADI.
- Tugas utama LC adalah memantau debitur dalam penanganan, melakukan interaksi penagihan, serta mencatat kepatuhan dan janji bayar debitur.

---

## 2. DASBOR PENAGIHAN KHUSUS LC (LC DASHBOARD)
Ketika pengguna dengan peran `LC` login, sistem menampilkan dasbor operasional penagihan dengan metrik utama:
1. **Total Assigned Customers**: Jumlah total nasabah yang ditugaskan kepada LC tersebut.
2. **Due Today**: Jumlah nasabah / angsuran yang jatuh tempo pada hari ini.
3. **Overdue**: Jumlah nasabah / angsuran yang telah melewati jatuh tempo (menunggak).
4. **Due This Week**: Jumlah angsuran yang jatuh tempo dalam 7 hari ke depan.
5. **Total Outstanding**: Akumulasi total saldo tagihan berjalan dari seluruh nasabah binaan LC.
6. **Promise-to-Pay**: Daftar debitur yang memiliki komitmen janji bayar aktif beserta tanggal dan nominalnya.

---

## 3. PENCATATAN AKTIVITAS PENAGIHAN (COLLECTION ACTIVITIES)
Setiap interaksi penagihan wajib dicatat ke tabel `collection_activities`:
- `loan_id` (Pinjaman terkait)
- `customer_id` (Nasabah terkait)
- `collector_id` (ID pengguna LC yang bertugas)
- `contact_date` (Waktu kontak/kunjungan dilakukan)
- `contact_method` (Metode kontak)
- `result` (Hasil penagihan)
- `promise_to_pay_date` (Tanggal janji bayar, jika ada)
- `promise_to_pay_amount` (Nominal janji bayar dalam integer Rupiah, jika ada)
- `notes` (Catatan hasil pembicaraan/lapangan)

### Metode Kontak yang Didukung (`contact_method`):
- `PHONE`: Panggilan telepon suara
- `WHATSAPP`: Pesan atau panggilan WhatsApp
- `IN_PERSON`: Kunjungan langsung ke alamat domisili atau tempat kerja nasabah
- `OTHER`: Saluran komunikasi lainnya

### Hasil Penagihan yang Didukung (`result`):
- `PAID`: Nasabah menyatakan sudah membayar / langsung melakukan penyetoran ke kasir.
- `PROMISE_TO_PAY`: Nasabah berkomitmen akan membayar pada tanggal tertentu dengan nominal tertentu.
- `NO_RESPONSE`: Tidak ada jawaban / telepon tidak diangkat / rumah kosong.
- `CONTACT_FAILED`: Nomor tidak aktif / nasabah pindah alamat tidak diketahui.
- `DISPUTED`: Nasabah menyanggah kewajiban pinjaman atau nominal tagihan.
- `OTHER`: Hasil interaksi lainnya.

---

## 4. BATASAN KETAT KEWENANGAN LC (LC PERMISSION BOUNDARIES)
Untuk menjamin pemisahan tugas (*segregation of duties*) dan integritas finansial, sistem wajib memblokir LC dari tindakan berikut di level server-side:
- **Dilarang mengubah pokok pinjaman** (*modify loan principal*).
- **Dilarang mengubah suku bunga** (*change interest*).
- **Dilarang menyetujui pengajuan pinjaman** (*approve loans*).
- **Dilarang membalikkan transaksi pembayaran** (*reverse payments*).
- **Dilarang melepas atau menyerahkan jaminan** (*release collateral*).
