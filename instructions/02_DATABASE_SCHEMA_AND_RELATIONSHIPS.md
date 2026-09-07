# NADI — SKEMA DATABASE, TABEL, INDEKS & RELASI ELOQUENT
**File:** `instructions/02_DATABASE_SCHEMA_AND_RELATIONSHIPS.md`  
**Sumber:** `MASTER_BUILD_SPECIFICATION.md` (Bagian 8, 9, 10, 11, 19, 20, 22, 23, 24, 25, 27, 28, 31, 67, 68)

---

## 1. STRUKTUR 18 TABEL DATABASE MINIMAL
Sistem NADI wajib mendefinisikan migration SQLite untuk 18 tabel utama berikut:

```
 1. users                   10. installments
 2. roles                   11. payments
 3. permissions             12. payment_reversals
 4. role_user               13. collection_activities
 5. permission_role         14. collaterals
 6. customers               15. identity_verifications
 7. employments             16. collateral_releases
 8. loans                   17. audit_logs
 9. loan_status_histories   18. document_references
```

---

## 2. SPESIFIKASI SKEMA SETIAP TABEL

### 1. `users`
Tabel pengguna aplikasi untuk otentikasi login.
- `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `name` (VARCHAR)
- `email` (VARCHAR, UNIQUE)
- `password` (VARCHAR, hashed with bcrypt)
- `remember_token` (VARCHAR, NULLABLE)
- `created_at`, `updated_at` (TIMESTAMP)

### 2. `roles`
Tabel peran dalam sistem RBAC.
- `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `name` (VARCHAR, UNIQUE) — contoh: `ADMIN`, `LO`, `LC`, `CASHIER`, `COLLATERAL_OFFICER`, `IDENTITY_VERIFIER`, `AUDITOR`
- `display_name` (VARCHAR)
- `description` (TEXT, NULLABLE)
- `created_at`, `updated_at` (TIMESTAMP)

### 3. `permissions`
Tabel hak akses spesifik.
- `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `name` (VARCHAR, UNIQUE)
- `display_name` (VARCHAR)
- `description` (TEXT, NULLABLE)
- `created_at`, `updated_at` (TIMESTAMP)

### 4. `role_user`
Pivot table antara pengguna dan perannya.
- `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `user_id` (INTEGER, FOREIGN KEY -> `users.id` ON DELETE CASCADE)
- `role_id` (INTEGER, FOREIGN KEY -> `roles.id` ON DELETE CASCADE)
- `created_at`, `updated_at` (TIMESTAMP)

### 5. `permission_role`
Pivot table antara izin dan peran.
- `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `permission_id` (INTEGER, FOREIGN KEY -> `permissions.id` ON DELETE CASCADE)
- `role_id` (INTEGER, FOREIGN KEY -> `roles.id` ON DELETE CASCADE)
- `created_at`, `updated_at` (TIMESTAMP)

### 6. `customers`
Tabel data nasabah / debitur.
- `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `customer_code` (VARCHAR, UNIQUE, format: `CUS-YYYY-XXXXXX`, misal `CUS-2026-000001`)
- `full_name` (VARCHAR)
- `national_id_number` (VARCHAR) — *Aturan Privasi: Jangan pernah tampilkan nomor identitas ini di URL parameter!*
- `date_of_birth` (DATE)
- `gender` (VARCHAR: `MALE`, `FEMALE`)
- `phone` (VARCHAR)
- `email` (VARCHAR, NULLABLE)
- `address` (TEXT)
- `city` (VARCHAR)
- `emergency_contact_name` (VARCHAR)
- `emergency_contact_phone` (VARCHAR)
- `status` (VARCHAR: `ACTIVE`, `INACTIVE`, `BLOCKED`)
- `created_at`, `updated_at` (TIMESTAMP)
- `deleted_at` (TIMESTAMP, NULLABLE untuk soft delete jika diperlukan)

### 7. `employments`
Tabel profil pekerjaan nasabah.
- `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `customer_id` (INTEGER, FOREIGN KEY -> `customers.id` ON DELETE CASCADE)
- `company_name` (VARCHAR)
- `department` (VARCHAR, NULLABLE)
- `position` (VARCHAR)
- `employment_type` (VARCHAR: `PERMANENT`, `CONTRACT`, `SELF_EMPLOYED`, `OTHER`)
- `employment_start_date` (DATE, NULLABLE)
- `estimated_monthly_income` (INTEGER Rupiah utuh)
- `employment_status` (VARCHAR: `ACTIVE`, `RESIGNED`, `TERMINATED`, `UNKNOWN`)
- `notes` (TEXT, NULLABLE)
- `created_at`, `updated_at` (TIMESTAMP)

### 8. `loans`
Tabel kontrak pinjaman utama.
- `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `loan_number` (VARCHAR, UNIQUE, format: `NADI-LOAN-YYYY-XXXXXX`, misal `NADI-LOAN-2026-000001`)
- `customer_id` (INTEGER, FOREIGN KEY -> `customers.id`)
- `principal_amount` (INTEGER Rupiah utuh, nominal pokok)
- `interest_rate` (DECIMAL/INTEGER basis points, misal 200 bps = 2.0%)
- `interest_method` (VARCHAR: `FLAT`, `REDUCING_BALANCE`)
- `tenor` (INTEGER, jumlah periode angsuran)
- `installment_frequency` (VARCHAR: `MONTHLY`, `WEEKLY`)
- `disbursement_date` (DATE, NULLABLE)
- `first_due_date` (DATE)
- `maturity_date` (DATE)
- `total_interest` (INTEGER Rupiah utuh)
- `total_payable` (INTEGER Rupiah utuh: principal + interest)
- `installment_amount` (INTEGER Rupiah utuh per periode)
- `outstanding_principal` (INTEGER Rupiah utuh)
- `outstanding_interest` (INTEGER Rupiah utuh)
- `outstanding_penalty` (INTEGER Rupiah utuh, default 0)
- `outstanding_total` (INTEGER Rupiah utuh: outstanding principal + interest + penalty)
- `status` (VARCHAR: `DRAFT`, `SUBMITTED`, `UNDER_REVIEW`, `APPROVED`, `REJECTED`, `READY_FOR_DISBURSEMENT`, `ACTIVE`, `OVERDUE`, `COMPLETED`, `DEFAULTED`, `CANCELLED`)
- `created_by` (INTEGER, FOREIGN KEY -> `users.id`)
- `approved_by` (INTEGER, NULLABLE, FOREIGN KEY -> `users.id`)
- `approved_at` (TIMESTAMP, NULLABLE)
- `disbursed_at` (TIMESTAMP, NULLABLE)
- `completed_at` (TIMESTAMP, NULLABLE)
- `created_at`, `updated_at` (TIMESTAMP)

### 9. `loan_status_histories`
Tabel rekaman kronologis setiap transisi status pinjaman.
- `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `loan_id` (INTEGER, FOREIGN KEY -> `loans.id` ON DELETE CASCADE)
- `from_status` (VARCHAR, NULLABLE)
- `to_status` (VARCHAR)
- `changed_by` (INTEGER, FOREIGN KEY -> `users.id`)
- `reason` (TEXT, NULLABLE)
- `created_at` (TIMESTAMP)

### 10. `installments`
Tabel rincian jadwal angsuran pinjaman.
- `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `loan_id` (INTEGER, FOREIGN KEY -> `loans.id` ON DELETE CASCADE)
- `installment_number` (INTEGER, nomor urut angsuran 1..n)
- `due_date` (DATE, tanggal jatuh tempo)
- `principal_due` (INTEGER Rupiah utuh, tagihan pokok)
- `interest_due` (INTEGER Rupiah utuh, tagihan bunga)
- `penalty_due` (INTEGER Rupiah utuh, default 0)
- `total_due` (INTEGER Rupiah utuh: principal_due + interest_due + penalty_due)
- `principal_paid` (INTEGER Rupiah utuh, default 0)
- `interest_paid` (INTEGER Rupiah utuh, default 0)
- `penalty_paid` (INTEGER Rupiah utuh, default 0)
- `total_paid` (INTEGER Rupiah utuh, default 0)
- `remaining_amount` (INTEGER Rupiah utuh: total_due - total_paid)
- `status` (VARCHAR: `PENDING`, `PARTIALLY_PAID`, `PAID`, `OVERDUE`, `WAIVED`)
- `paid_at` (TIMESTAMP, NULLABLE)
- `created_at`, `updated_at` (TIMESTAMP)

### 11. `payments`
Tabel rekaman transaksi pembayaran angsuran dari nasabah.
- `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `payment_number` (VARCHAR, UNIQUE, format: `PAY-YYYY-XXXXXX`, misal `PAY-2026-000001`)
- `loan_id` (INTEGER, FOREIGN KEY -> `loans.id`)
- `customer_id` (INTEGER, FOREIGN KEY -> `customers.id`)
- `installment_id` (INTEGER, NULLABLE, FOREIGN KEY -> `installments.id`)
- `payment_date` (DATE)
- `amount` (INTEGER Rupiah utuh, nominal yang dibayarkan nasabah)
- `principal_component` (INTEGER Rupiah utuh, alokasi untuk pokok)
- `interest_component` (INTEGER Rupiah utuh, alokasi untuk bunga)
- `penalty_component` (INTEGER Rupiah utuh, alokasi untuk denda)
- `payment_method` (VARCHAR: `CASH`, `BANK_TRANSFER`, `QRIS`, `OTHER`)
- `reference_number` (VARCHAR, NULLABLE, no referensi bank / kuitansi)
- `received_by` (INTEGER, FOREIGN KEY -> `users.id`)
- `notes` (TEXT, NULLABLE)
- `created_at`, `updated_at` (TIMESTAMP)

### 12. `payment_reversals`
Tabel pencatatan transaksi pembalikan pembayaran karena kekeliruan kasir.
- `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `payment_id` (INTEGER, UNIQUE, FOREIGN KEY -> `payments.id`)
- `reason` (TEXT, alasan pembatalan / pembalikan pembayaran)
- `reversed_by` (INTEGER, FOREIGN KEY -> `users.id`)
- `reversed_at` (TIMESTAMP)
- `created_at` (TIMESTAMP)

### 13. `collection_activities`
Tabel catatan aktivitas penagihan oleh Loan Collector (LC).
- `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `loan_id` (INTEGER, FOREIGN KEY -> `loans.id`)
- `customer_id` (INTEGER, FOREIGN KEY -> `customers.id`)
- `collector_id` (INTEGER, FOREIGN KEY -> `users.id`)
- `contact_date` (DATETIME / TIMESTAMP)
- `contact_method` (VARCHAR: `PHONE`, `WHATSAPP`, `IN_PERSON`, `OTHER`)
- `result` (VARCHAR: `PAID`, `PROMISE_TO_PAY`, `NO_RESPONSE`, `CONTACT_FAILED`, `DISPUTED`, `OTHER`)
- `promise_to_pay_date` (DATE, NULLABLE)
- `promise_to_pay_amount` (INTEGER Rupiah utuh, NULLABLE)
- `notes` (TEXT, NULLABLE)
- `created_at`, `updated_at` (TIMESTAMP)

### 14. `collaterals`
Tabel jaminan / agunan yang diserahkan nasabah.
- `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `collateral_code` (VARCHAR, UNIQUE, format: `COL-YYYY-XXXXXX`, misal `COL-2026-000001`)
- `loan_id` (INTEGER, FOREIGN KEY -> `loans.id`)
- `customer_id` (INTEGER, FOREIGN KEY -> `customers.id`)
- `collateral_type` (VARCHAR: `DOCUMENT`, `VEHICLE`, `ELECTRONIC`, `OTHER`)
- `description` (TEXT)
- `identification_number` (VARCHAR, nomor BPKB / Sertifikat / SN Elektronik / No Polisi)
- `estimated_value` (INTEGER Rupiah utuh)
- `received_date` (DATE)
- `condition_on_receipt` (TEXT, kondisi fisik saat diserahkan)
- `storage_location` (VARCHAR, misal `Brankas A-01`, `Gudang Agunan B`)
- `custody_status` (VARCHAR: `PENDING`, `RECEIVED`, `IN_CUSTODY`, `READY_FOR_RELEASE`, `RELEASED`, `DISPUTED`)
- `received_by` (INTEGER, FOREIGN KEY -> `users.id`)
- `released_by` (INTEGER, NULLABLE, FOREIGN KEY -> `users.id`)
- `released_at` (TIMESTAMP, NULLABLE)
- `created_at`, `updated_at` (TIMESTAMP)

### 15. `identity_verifications`
Tabel catatan verifikasi identitas nasabah pemohon pengambilan jaminan.
- `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `customer_id` (INTEGER, FOREIGN KEY -> `customers.id`)
- `loan_id` (INTEGER, FOREIGN KEY -> `loans.id`)
- `release_id` (INTEGER, NULLABLE, FOREIGN KEY -> `collateral_releases.id`)
- `verification_method` (VARCHAR: `GOVERNMENT_ID`, `ACCOUNT_MATCH`, `MANUAL_CHECK`, `OTHER`)
- `verified_name` (VARCHAR, nama pemohon yang diverifikasi)
- `verified_id_number` (VARCHAR, nomor KTP/Identitas pemohon yang diverifikasi)
- `result` (VARCHAR: `VERIFIED`, `FAILED`, `REQUIRES_REVIEW`)
- `verifier_id` (INTEGER, FOREIGN KEY -> `users.id`)
- `verification_timestamp` (TIMESTAMP)
- `notes` (TEXT, NULLABLE)
- `created_at` (TIMESTAMP)

### 16. `collateral_releases`
Tabel transaksi serah terima pengembalian agunan.
- `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `release_number` (VARCHAR, UNIQUE, format: `REL-YYYY-XXXXXX`, misal `REL-2026-000001`)
- `collateral_id` (INTEGER, UNIQUE, FOREIGN KEY -> `collaterals.id`)
- `loan_id` (INTEGER, FOREIGN KEY -> `loans.id`)
- `customer_id` (INTEGER, FOREIGN KEY -> `customers.id`)
- `verified_identity_id` (INTEGER, FOREIGN KEY -> `identity_verifications.id`)
- `released_to_name` (VARCHAR, nama orang yang menerima fisik jaminan)
- `relationship_to_customer` (VARCHAR, misal `YANG_BERSANGKUTAN`, `PASANGAN`, `KELUARGA_DENGAN_KUASA`)
- `release_date` (DATE)
- `release_location` (VARCHAR, kantor/lokasi serah terima)
- `released_by` (INTEGER, FOREIGN KEY -> `users.id`)
- `witness_id` (INTEGER, NULLABLE, FOREIGN KEY -> `users.id`, saksi internal)
- `customer_signature_reference` (VARCHAR, NULLABLE, referensi file tanda tangan/dokumen fisik)
- `handover_notes` (TEXT, NULLABLE)
- `created_at` (TIMESTAMP)

### 17. `audit_logs`
Tabel pencatatan jejak audit sistem (*system audit trail*).
- `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `user_id` (INTEGER, NULLABLE, FOREIGN KEY -> `users.id` ON DELETE SET NULL)
- `action` (VARCHAR, nama aksi sistem)
- `entity_type` (VARCHAR, model yang dimutasi, misal `Loan`, `Payment`)
- `entity_id` (INTEGER, ID entitas yang dimutasi)
- `old_values` (TEXT / JSON, payload sebelum mutasi)
- `new_values` (TEXT / JSON, payload setelah mutasi)
- `ip_address` (VARCHAR, IP klien)
- `user_agent` (VARCHAR, NULLABLE)
- `created_at` (TIMESTAMP)

### 18. `document_references`
Tabel metadata dokumen sintetis/pendukung jaminan dan identitas.
- `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `reference_type` (VARCHAR: `CUSTOMER_ID`, `COLLATERAL_PROOF`, `RELEASE_RECEIPT`, `PAYMENT_RECEIPT`)
- `reference_id` (INTEGER)
- `file_name` (VARCHAR)
- `file_path` (VARCHAR, storage path privat)
- `mime_type` (VARCHAR)
- `file_size` (INTEGER)
- `uploaded_by` (INTEGER, FOREIGN KEY -> `users.id`)
- `created_at`, `updated_at` (TIMESTAMP)

---

## 3. INDEKS DATABASE WAJIB (15 INDEKS)
Untuk memastikan performa pencarian dan filter data yang cepat di SQLite, migrasi wajib menyertakan 15 indeks spesifik berikut:
1. `customers(customer_code)`
2. `customers(national_id_number)`
3. `customers(phone)`
4. `loans(loan_number)`
5. `loans(customer_id)`
6. `loans(status)`
7. `installments(loan_id)`
8. `installments(due_date)`
9. `installments(status)`
10. `payments(payment_number)`
11. `payments(loan_id)`
12. `collaterals(collateral_code)`
13. `collaterals(loan_id)`
14. `collaterals(custody_status)`
15. `audit_logs(entity_type, entity_id, created_at)`

---

## 4. RELASI ELOQUENT ANTAR MODEL (DATABASE RELATIONSHIPS)
Model Eloquent wajib mengimplementasikan relasi terstandarisasi berikut:

- **Customer**:
  - `hasMany(Employment::class)`
  - `hasMany(Loan::class)`
  - `hasMany(Collateral::class)`
  - `hasMany(Payment::class)`
  - `hasMany(CollectionActivity::class)`
  - `hasMany(IdentityVerification::class)`
  - `hasMany(CollateralRelease::class)`
- **Employment**:
  - `belongsTo(Customer::class)`
- **Loan**:
  - `belongsTo(Customer::class)`
  - `belongsTo(User::class, 'created_by')`
  - `belongsTo(User::class, 'approved_by')`
  - `hasMany(Installment::class)`
  - `hasMany(Payment::class)`
  - `hasMany(CollectionActivity::class)`
  - `hasMany(Collateral::class)`
  - `hasMany(LoanStatusHistory::class)`
  - `hasMany(IdentityVerification::class)`
  - `hasMany(CollateralRelease::class)`
- **Installment**:
  - `belongsTo(Loan::class)`
  - `hasMany(Payment::class)`
- **Payment**:
  - `belongsTo(Loan::class)`
  - `belongsTo(Customer::class)`
  - `belongsTo(Installment::class)`
  - `belongsTo(User::class, 'received_by')`
  - `hasOne(PaymentReversal::class)`
- **PaymentReversal**:
  - `belongsTo(Payment::class)`
  - `belongsTo(User::class, 'reversed_by')`
- **CollectionActivity**:
  - `belongsTo(Loan::class)`
  - `belongsTo(Customer::class)`
  - `belongsTo(User::class, 'collector_id')`
- **Collateral**:
  - `belongsTo(Loan::class)`
  - `belongsTo(Customer::class)`
  - `belongsTo(User::class, 'received_by')`
  - `belongsTo(User::class, 'released_by')`
  - `hasOne(CollateralRelease::class)`
- **IdentityVerification**:
  - `belongsTo(Customer::class)`
  - `belongsTo(Loan::class)`
  - `belongsTo(User::class, 'verifier_id')`
  - `hasOne(CollateralRelease::class, 'verified_identity_id')`
- **CollateralRelease**:
  - `belongsTo(Collateral::class)`
  - `belongsTo(Loan::class)`
  - `belongsTo(Customer::class)`
  - `belongsTo(IdentityVerification::class, 'verified_identity_id')`
  - `belongsTo(User::class, 'released_by')`
  - `belongsTo(User::class, 'witness_id')`
- **AuditLog**:
  - `belongsTo(User::class)`
