NADI — LOAN MANAGEMENT SYSTEM

MASTER BUILD SPECIFICATION

Target: Google AI Studio Build

Stack: Laravel + SQLite

---

0. INSTRUCTION TO THE AI

You are a senior Laravel architect, backend engineer, database designer, financial-system software engineer, security engineer, and UI/UX designer.

Build a complete, functional prototype called:

NADI

NADI is an internal loan/customer management system.

The application manages:

- customers
- employment information
- loan applications
- loan contracts
- interest
- tenor
- installment schedules
- payments
- collection activities
- Loan Collectors (LC)
- collateral
- identity verification
- collateral release
- users
- roles
- permissions
- audit logs
- dashboards
- reports
- printable documents

The prototype must be functional end-to-end.

Do not build a static mockup.

Every major button must perform a real operation against the SQLite database.

Use Laravel conventions and server-side validation.

Use synthetic/demo data only.

---

1. TECHNOLOGY REQUIREMENTS

Use:

- PHP
- Laravel
- SQLite
- Blade
- Livewire if available and appropriate
- Tailwind CSS
- Laravel authentication
- Laravel migrations
- Laravel seeders
- Laravel factories
- Laravel validation
- Laravel authorization/policies
- Laravel database transactions

Database:

"database/database.sqlite"

Do not require MySQL or PostgreSQL.

Do not introduce unnecessary external services.

The application must be able to run locally.

---

2. APPLICATION IDENTITY

Application name:

NADI

Meaning/branding:

NADI is an independent business/application.

The domain:

"nadi.plenger.id"

is only the domain/hosting address.

Do not create a "Plenger" module.

Do not treat Plenger as the parent company inside the application.

The application branding should display:

NADI

Subtitle:

Loan Management System

---

3. LANGUAGE

The entire visible UI must use Indonesian.

Examples:

- Dashboard
- Nasabah
- Pinjaman
- Angsuran
- Pembayaran
- Penagihan
- Jaminan
- Verifikasi Identitas
- Pengambilan Jaminan
- Pengguna
- Laporan
- Audit Log

Internal code may use English naming conventions.

Database tables and PHP classes should preferably use English names for maintainability.

---

4. DESIGN

Create a professional internal financial operations dashboard.

Visual characteristics:

- clean
- modern
- compact
- professional
- responsive
- desktop-first
- mobile-friendly
- clear tables
- clear status badges
- sidebar navigation
- top navigation
- breadcrumbs
- modal confirmation for dangerous actions
- toast/flash notifications
- empty states
- loading states
- validation errors

Use a neutral professional visual system.

Do not make it look like a generic blog or ecommerce website.

---

5. MAIN NAVIGATION

Sidebar:

1. Dashboard
2. Nasabah
3. Pinjaman
4. Angsuran
5. Pembayaran
6. Penagihan
7. Jaminan
8. Verifikasi Identitas
9. Pengambilan Jaminan
10. Pengguna
11. Laporan
12. Audit Log
13. Pengaturan

Navigation visibility must respect permissions.

---

6. AUTHENTICATION

Implement login.

Fields:

- email
- password

Support logout.

Protect all application pages except login.

Passwords must use Laravel password hashing.

Never store plaintext passwords.

---

7. ROLES

Implement these roles:

ADMIN

Full access.

LO

Loan Officer.

Can:

- create customers
- edit customers
- create loan applications
- view assigned customers
- view loans

Cannot independently release collateral.

---

LC

IMPORTANT:

LC means:

Loan Collector

This is an intentional internal job title.

LC can:

- see assigned borrowers
- see due installments
- see overdue installments
- record collection activity
- record promise-to-pay
- view customer payment status

LC cannot:

- modify loan principal
- change interest
- approve loans
- reverse payments
- release collateral

---

CASHIER

Can:

- record payments
- view payments
- generate receipts

Cannot:

- approve loans
- change loan terms
- release collateral

---

COLLATERAL OFFICER

Can:

- receive collateral
- update collateral custody
- prepare collateral for release
- perform authorized collateral handover

Cannot bypass financial eligibility rules.

---

IDENTITY VERIFIER

Can:

- verify identity
- reject verification
- review verification history

Cannot independently release collateral.

---

AUDITOR

Read-only access.

Can view:

- customers
- loans
- payments
- collateral
- identity verification
- collection
- audit logs
- reports

Cannot modify records.

---

8. DATABASE

Use SQLite.

Create migrations for all tables.

Minimum tables:

1. users
2. roles
3. permissions
4. role_user
5. permission_role
6. customers
7. employments
8. loans
9. loan_status_histories
10. installments
11. payments
12. payment_reversals
13. collection_activities
14. collaterals
15. identity_verifications
16. collateral_releases
17. audit_logs
18. document_references

Use proper foreign keys.

Use indexes on frequently searched fields.

Use unique constraints where appropriate.

Do not store money as floating point.

For SQLite, use integer values representing the smallest currency unit where practical.

For Indonesian Rupiah, store monetary amounts as integer Rupiah.

Example:

Rp1.500.000

must be stored as:

"1500000"

not:

"1500000.00" floating point.

---

9. CUSTOMER TABLE

Table:

"customers"

Fields:

- id
- customer_code
- full_name
- national_id_number
- date_of_birth
- gender
- phone
- email
- address
- city
- emergency_contact_name
- emergency_contact_phone
- status
- created_at
- updated_at
- deleted_at if soft deletion is appropriate

Customer code format:

"CUS-YYYY-000001"

Example:

"CUS-2026-000001"

Customer code must be unique.

Customer status:

- ACTIVE
- INACTIVE
- BLOCKED

Do not expose national ID in URL parameters.

---

10. EMPLOYMENT

Table:

"employments"

Fields:

- id
- customer_id
- company_name
- department
- position
- employment_type
- employment_start_date
- estimated_monthly_income
- employment_status
- notes
- created_at
- updated_at

Employment statuses:

- ACTIVE
- RESIGNED
- TERMINATED
- UNKNOWN

---

11. LOAN

Table:

"loans"

Fields:

- id
- loan_number
- customer_id
- principal_amount
- interest_rate
- interest_method
- tenor
- installment_frequency
- disbursement_date
- first_due_date
- maturity_date
- total_interest
- total_payable
- installment_amount
- outstanding_principal
- outstanding_interest
- outstanding_penalty
- outstanding_total
- status
- created_by
- approved_by
- approved_at
- disbursed_at
- completed_at
- created_at
- updated_at

Loan number:

"NADI-LOAN-YYYY-000001"

Example:

"NADI-LOAN-2026-000001"

---

12. INTEREST METHODS

Support:

FLAT

Formula:

"total_interest = principal × interest_rate × tenor"

If interest rate is stored as a percentage, convert it correctly.

Example:

Principal:

Rp10,000,000

Rate:

2% per month

Tenor:

10 months

Interest:

Rp10,000,000 × 2% × 10

= Rp2,000,000

Total:

Rp12,000,000

Installment:

Rp1,200,000

---

REDUCING_BALANCE

Implement a standard reducing-balance installment calculation.

Use monthly periodic interest for monthly installments.

Formula:

"A = P × r × (1+r)^n / ((1+r)^n - 1)"

where:

- A = installment
- P = principal
- r = periodic interest rate
- n = number of installments

If r = 0, installment = principal / n.

Create a dedicated service:

"LoanCalculationService"

Do not put financial calculations directly inside Blade templates.

---

13. INTEREST RATE STORAGE

Store the original rate precisely enough for calculation.

For example:

"interest_rate_basis_points"

or an appropriate decimal representation.

Do not use binary floating point for financial calculations.

The displayed percentage must be formatted correctly.

---

14. TENOR

Support configurable tenor.

Example:

- 1 month
- 2 months
- 3 months
- 6 months
- 12 months

Store tenor as an integer number of payment periods.

---

15. INSTALLMENT FREQUENCY

Support:

- MONTHLY
- WEEKLY

Structure the system so other frequencies can be added later.

---

16. LOAN STATUS

Allowed statuses:

- DRAFT
- SUBMITTED
- UNDER_REVIEW
- APPROVED
- REJECTED
- READY_FOR_DISBURSEMENT
- ACTIVE
- OVERDUE
- COMPLETED
- DEFAULTED
- CANCELLED

Do not allow arbitrary status changes.

Create a loan state transition service.

---

17. LOAN WORKFLOW

Workflow:

DRAFT

↓

SUBMITTED

↓

UNDER_REVIEW

↓

APPROVED / REJECTED

↓

READY_FOR_DISBURSEMENT

↓

ACTIVE

↓

COMPLETED

or

ACTIVE

↓

OVERDUE

↓

ACTIVE / COMPLETED / DEFAULTED

Every status transition must be recorded.

---

18. LOAN APPLICATION SCREEN

Create a loan creation form.

Fields:

- customer
- principal
- interest rate
- interest method
- tenor
- installment frequency
- disbursement date
- first due date

As the user changes the values, show a calculation preview.

Show:

- Principal
- Interest rate
- Interest method
- Tenor
- Total interest
- Total payable
- Installment amount
- First due date
- Maturity date

Require confirmation before submission.

---

19. INSTALLMENTS

Table:

"installments"

Fields:

- id
- loan_id
- installment_number
- due_date
- principal_due
- interest_due
- penalty_due
- total_due
- principal_paid
- interest_paid
- penalty_paid
- total_paid
- remaining_amount
- status
- paid_at
- created_at
- updated_at

Statuses:

- PENDING
- PARTIALLY_PAID
- PAID
- OVERDUE
- WAIVED

Generate installments automatically after loan activation/disbursement.

---

20. PAYMENT

Table:

"payments"

Fields:

- id
- payment_number
- loan_id
- customer_id
- installment_id
- payment_date
- amount
- principal_component
- interest_component
- penalty_component
- payment_method
- reference_number
- received_by
- notes
- created_at
- updated_at

Payment number:

"PAY-YYYY-000001"

Payment methods:

- CASH
- BANK_TRANSFER
- QRIS
- OTHER

---

21. PAYMENT ALLOCATION

Create:

"PaymentAllocationService"

Default allocation:

1. penalty
2. interest
3. principal

Example:

Payment:

Rp1,100,000

Penalty:

Rp0

Interest:

Rp100,000

Principal:

Rp1,000,000

Store the actual allocation in the payment record.

Never infer historical allocation later from the current loan state.

---

22. PAYMENT IMMUTABILITY

A completed payment must not be directly edited.

If incorrect:

create a reversal transaction.

Table:

"payment_reversals"

Fields:

- id
- payment_id
- reason
- reversed_by
- reversed_at
- created_at

The original payment remains in history.

The reversal must create an audit event.

---

23. COLLECTION / LC MODULE

Create collection management.

LC dashboard:

- total assigned customers
- due today
- overdue
- due this week
- total outstanding
- promise-to-pay

Table:

"collection_activities"

Fields:

- id
- loan_id
- customer_id
- collector_id
- contact_date
- contact_method
- result
- promise_to_pay_date
- promise_to_pay_amount
- notes
- created_at
- updated_at

Contact methods:

- PHONE
- WHATSAPP
- IN_PERSON
- OTHER

Results:

- PAID
- PROMISE_TO_PAY
- NO_RESPONSE
- CONTACT_FAILED
- DISPUTED
- OTHER

---

24. COLLATERAL

Table:

"collaterals"

Fields:

- id
- collateral_code
- loan_id
- customer_id
- collateral_type
- description
- identification_number
- estimated_value
- received_date
- condition_on_receipt
- storage_location
- custody_status
- received_by
- released_by
- released_at
- created_at
- updated_at

Collateral code:

"COL-YYYY-000001"

Statuses:

- PENDING
- RECEIVED
- IN_CUSTODY
- READY_FOR_RELEASE
- RELEASED
- DISPUTED

---

25. COLLATERAL TYPES

Support:

- DOCUMENT
- VEHICLE
- ELECTRONIC
- OTHER

Allow custom description.

Do not assume that a particular asset is legally acceptable collateral.

---

26. COLLATERAL RECEIVING

When receiving collateral:

1. Select loan.
2. Confirm customer.
3. Enter collateral type.
4. Enter identification number.
5. Enter description.
6. Enter estimated value.
7. Record condition.
8. Record storage location.
9. Record receiving officer.
10. Generate collateral code.
11. Save.
12. Create audit event.

Generate a printable collateral receipt.

---

27. IDENTITY VERIFICATION

Table:

"identity_verifications"

Fields:

- id
- customer_id
- loan_id
- release_id nullable
- verification_method
- verified_name
- verified_id_number
- result
- verifier_id
- verification_timestamp
- notes
- created_at

Verification methods:

- GOVERNMENT_ID
- ACCOUNT_MATCH
- MANUAL_CHECK
- OTHER

Results:

- VERIFIED
- FAILED
- REQUIRES_REVIEW

For the prototype, identity verification is a workflow record.

Do not pretend that manual verification is equivalent to an official government identity API.

---

28. COLLATERAL RELEASE

Table:

"collateral_releases"

Fields:

- id
- release_number
- collateral_id
- loan_id
- customer_id
- verified_identity_id
- released_to_name
- relationship_to_customer
- release_date
- release_location
- released_by
- witness_id
- customer_signature_reference
- handover_notes
- created_at

Release number:

"REL-YYYY-000001"

---

29. COLLATERAL RELEASE RULE

This rule MUST be enforced server-side.

A collateral may be released only if:

1. collateral exists
2. collateral belongs to the loan
3. loan is eligible for release
4. required financial obligations are settled
5. collateral status is READY_FOR_RELEASE
6. identity verification result is VERIFIED
7. authorized user performs the release
8. release transaction is valid

If any condition fails:

BLOCK THE OPERATION.

Never rely solely on frontend validation.

---

30. RELEASE WORKFLOW

The UI must guide the operator through:

STEP 1

Find customer/loan.

STEP 2

Select collateral.

STEP 3

Display:

- customer
- loan number
- outstanding balance
- collateral code
- collateral description
- collateral status

STEP 4

Run identity verification.

STEP 5

Show verification result.

STEP 6

If verified and eligible, show release confirmation.

STEP 7

Operator confirms.

STEP 8

Create collateral release transaction.

STEP 9

Change collateral status to RELEASED.

STEP 10

Generate release receipt.

STEP 11

Create audit logs.

All critical steps must be inside appropriate database transactions.

---

31. AUDIT LOG

Table:

"audit_logs"

Fields:

- id
- user_id
- action
- entity_type
- entity_id
- old_values
- new_values
- ip_address
- user_agent
- created_at

Record at minimum:

- CUSTOMER_CREATED
- CUSTOMER_UPDATED
- LOAN_CREATED
- LOAN_SUBMITTED
- LOAN_APPROVED
- LOAN_REJECTED
- LOAN_DISBURSED
- LOAN_STATUS_CHANGED
- PAYMENT_CREATED
- PAYMENT_REVERSED
- COLLECTION_CREATED
- COLLATERAL_RECEIVED
- IDENTITY_VERIFIED
- IDENTITY_FAILED
- RELEASE_CREATED
- COLLATERAL_RELEASED
- USER_CREATED
- PERMISSION_CHANGED

Use JSON for old/new values.

Do not expose audit log deletion through the normal UI.

---

32. CUSTOMER PAGE

Customer detail page must contain:

Overview

- customer identity
- contact information
- employment
- status

Loans

List all loans.

Payments

Payment history.

Collateral

All collateral.

Collection

Collection history.

Verification

Identity verification history.

Audit

Relevant activity.

---

33. LOAN PAGE

Loan detail:

- customer
- loan number
- principal
- interest
- method
- tenor
- installment
- total payable
- outstanding
- status
- approval
- disbursement

Tabs:

- Ringkasan
- Angsuran
- Pembayaran
- Jaminan
- Penagihan
- Riwayat

---

34. DASHBOARD

Dashboard must show:

Portfolio

- Total nasabah
- Pinjaman aktif
- Outstanding principal
- Outstanding interest
- Total portfolio

Collection

- Jatuh tempo hari ini
- Terlambat
- Jatuh tempo minggu ini
- Promise-to-pay

Collateral

- Total jaminan
- Dalam penyimpanan
- Siap diambil
- Sudah diserahkan

Recent activity

Show latest:

- payments
- loan approvals
- collateral receipts
- identity verifications
- collateral releases

---

35. SEARCH

Implement global search.

Search by:

- customer code
- customer name
- phone
- national ID
- loan number
- collateral code
- payment number

Results must respect authorization.

---

36. FILTERS

Tables must support filters where relevant.

Examples:

Loans:

- status
- date
- customer
- overdue

Payments:

- date
- payment method
- collector/cashier
- loan

Collateral:

- status
- type
- storage location

Customers:

- status
- company
- employment status

---

37. PAGINATION

All potentially large tables must use pagination.

Do not load unlimited records onto one page.

---

38. REPORTS

Implement basic reports:

1. Daftar nasabah
2. Daftar pinjaman
3. Outstanding pinjaman
4. Jatuh tempo
5. Tunggakan
6. Pembayaran
7. Jaminan
8. Pengambilan jaminan
9. Aktivitas LC
10. Audit log

Allow filtering by date.

Provide print-friendly views.

---

39. PRINTABLE DOCUMENTS

Create print views for:

- Loan summary
- Installment schedule
- Payment receipt
- Collateral receipt
- Identity verification result
- Collateral release receipt

Each document should have:

NADI

document title

document number

date

customer information

relevant transaction information

authorized officer

signature area

---

40. AUTHORIZATION

Use Laravel Policies/Gates/Middleware.

Never rely only on hiding buttons.

Example:

Even if a malicious user manually sends a request to:

"POST /collaterals/{id}/release"

the server must verify:

- permission
- loan state
- collateral state
- identity verification
- financial eligibility

and reject unauthorized requests.

---

41. FINANCIAL TRANSACTIONS

Use:

"DB::transaction()"

for:

- loan approval
- loan disbursement
- payment creation
- payment reversal
- collateral receiving
- identity verification
- collateral release

If any operation fails, rollback the entire operation.

---

42. NUMBER GENERATION

Implement reliable sequential business identifiers.

Examples:

Customer:

"CUS-2026-000001"

Loan:

"NADI-LOAN-2026-000001"

Payment:

"PAY-2026-000001"

Collateral:

"COL-2026-000001"

Release:

"REL-2026-000001"

Do not rely on the database primary key alone as the public business identifier.

---

43. MONEY FORMATTING

Display Rupiah using Indonesian formatting.

Example:

"Rp 1.500.000"

Database:

"1500000"

Create reusable formatter/helper/component.

---

44. DATE FORMATTING

Display dates in Indonesian-friendly format.

Example:

"10 September 2026"

Database should use standard date/datetime formats.

---

45. FILE/DOCUMENT SECURITY

If the prototype supports identity or collateral document uploads:

- do not store them in public directories
- use private storage
- authorize every download
- never expose raw filesystem paths
- validate file types
- validate file size
- generate safe filenames

Use demo documents only.

---

46. PRIVACY

The prototype must use synthetic data.

Do not create realistic personal identity documents.

Do not use actual NIKs.

Do not include real customer information in seeders.

Mask sensitive information in normal displays when appropriate.

---

47. DEMO DATA

Create realistic synthetic seed data:

20 customers

15 active loans

5 completed loans

multiple installments

some overdue installments

multiple payments

multiple collection records

multiple collateral records

identity verification records

release records

audit records

Create demo users:

admin@example.test
lo@example.test
lc@example.test
cashier@example.test
collateral@example.test
verifier@example.test
auditor@example.test

Use a documented development-only password.

---

48. SEED USER PERMISSIONS

Admin:

full access

LO:

customer + loan application

LC:

collection + customer/loan read access

Cashier:

payment

Collateral Officer:

collateral

Identity Verifier:

identity verification

Auditor:

read-only

---

49. VALIDATION

Validate all input server-side.

Examples:

principal:

required
integer
minimum 1

interest:

required
valid numeric range

tenor:

required
integer
minimum 1

phone:

required
valid format

customer:

must exist

loan:

must exist

collateral:

must belong to loan

Never trust frontend input.

---

50. ERROR MESSAGES

Use Indonesian.

Example:

«Jaminan belum dapat diserahkan karena pinjaman masih memiliki kewajiban sebesar Rp 1.250.000.»

Example:

«Verifikasi identitas gagal. Data identitas tidak sesuai dengan data nasabah.»

Example:

«Anda tidak memiliki izin untuk melakukan tindakan ini.»

---

51. CONFIRMATION DIALOGS

Require explicit confirmation for:

- approval
- rejection
- disbursement
- payment
- reversal
- identity verification
- collateral release

For collateral release, display:

Customer

Loan

Collateral

Outstanding

Identity verification

Operator

Release status

Then ask:

"Konfirmasi pengambilan jaminan?"

---

52. SECURITY REQUIREMENTS

Implement:

- CSRF protection
- authentication
- authorization
- password hashing
- session security
- validation
- rate limiting for login where appropriate
- private document storage
- audit logging
- server-side permission checks
- database transactions

Do not expose secrets.

Do not hardcode production credentials.

---

53. SQLITE COMPATIBILITY

The entire prototype must work with SQLite.

Do not use database features that require MySQL/PostgreSQL unless there is a SQLite-compatible implementation.

Avoid vendor-specific SQL.

Prefer Laravel query builder/Eloquent.

Make migrations SQLite-compatible.

---

54. SEPARATION OF BUSINESS LOGIC

Create service classes.

At minimum:

"LoanCalculationService"

"InstallmentScheduleService"

"PaymentAllocationService"

"LoanStatusService"

"CollateralEligibilityService"

"CollateralReleaseService"

"IdentityVerificationService"

"AuditLogService"

Controllers should orchestrate operations, not contain all business logic.

---

55. IMPORTANT FINANCIAL RULE

Never calculate historical financial information from current mutable values.

When a loan is created/activated:

store its contractual values.

When an installment is generated:

store its original principal/interest due.

When payment occurs:

store actual allocation.

Historical records must remain understandable even if configuration changes later.

---

56. NO SILENT MUTATION

Never silently modify:

- principal
- interest
- installment amount
- payment
- collateral status
- release record

Important changes must produce:

- explicit action
- authorization
- audit record

---

57. TESTS

Create automated tests.

At minimum:

Customer

- create
- update
- validation

Loan

- create
- calculation
- approval
- rejection
- activation
- completion

Installment

- schedule generation
- overdue detection

Payment

- allocation
- partial payment
- full payment
- reversal

Collection

- activity creation
- permission checks

Collateral

- receiving
- eligibility
- release

Identity

- verified
- failed
- requires review

Security

Test that:

- LC cannot approve loans
- Cashier cannot release collateral
- Auditor cannot modify records
- unauthorized user cannot release collateral

---

58. CRITICAL END-TO-END TEST

The following scenario MUST work:

1. Login as Admin.
2. Create synthetic customer.
3. Login as LO.
4. Create loan.
5. Submit loan.
6. Admin reviews.
7. Admin approves.
8. Loan becomes ready for disbursement.
9. Admin disburses.
10. System generates installments.
11. Collateral Officer receives collateral.
12. LC sees due installment.
13. Cashier records payment.
14. System allocates payment.
15. Installment updates.
16. Continue payments until obligations are satisfied.
17. Collateral becomes eligible for release.
18. Identity Verifier verifies customer.
19. Collateral Officer opens release workflow.
20. System checks eligibility.
21. System confirms verified identity.
22. Operator confirms handover.
23. Release transaction is created.
24. Collateral status becomes RELEASED.
25. Release receipt is generated.
26. Audit log contains every important event.

---

59. USER EXPERIENCE FOR COLLATERAL RELEASE

Make this workflow extremely explicit.

The operator should see a checklist:

[✓] Loan exists

[✓] Customer matched

[✓] Financial obligation settled

[✓] Collateral exists

[✓] Collateral eligible

[✓] Identity verified

[✓] Authorized officer

Then:

JAMINAN SIAP DISERAHKAN

If one condition fails:

[✗] Financial obligation not settled

JAMINAN TIDAK DAPAT DISERAHKAN

Do not allow override unless a separately designed authorized exception workflow exists.

For this prototype, do NOT implement an unrestricted override.

---

60. AUDIT LOG DISPLAY

Create an audit page.

Columns:

- waktu
- pengguna
- aksi
- modul
- record
- perubahan

Clicking an audit entry should show:

Before:

JSON

After:

JSON

This allows the operator/auditor to understand what changed.

---

61. DASHBOARD ROLE CUSTOMIZATION

Admin dashboard:

portfolio overview

LC dashboard:

collections

Cashier dashboard:

payments

Collateral Officer:

custody and release

Identity Verifier:

verification queue

Auditor:

recent audit activity

LO:

customer and loan applications

Do not show irrelevant financial controls to each role.

---

62. EMPTY STATES

Every list must have a useful empty state.

Example:

«Belum ada jaminan.»

Button:

Tambah Jaminan

---

63. LOADING AND ERROR STATES

Forms should prevent accidental double submission.

Buttons performing transactions should temporarily disable while processing.

Show success/failure notification.

---

64. RESPONSIVE UI

Desktop:

sidebar + content

Mobile:

collapsible navigation

Tables:

horizontal scrolling where necessary

Forms:

single-column on small screens

---

65. CODE QUALITY

Use:

- Laravel conventions
- Eloquent relationships
- Form Requests
- Policies
- Services
- reusable Blade components
- named routes
- migrations
- factories
- seeders
- tests

Avoid:

- giant controllers
- duplicated code
- inline SQL when Eloquent/query builder is sufficient
- financial calculations in Blade
- hardcoded IDs
- hardcoded user permissions
- hardcoded loan status strings scattered throughout code

Use enums/constants/value objects where appropriate and SQLite-compatible.

---

66. README

Create a README containing:

Installation

1. install dependencies
2. configure ".env"
3. create SQLite database
4. run migrations
5. run seeders
6. start Laravel

Demo accounts

List all roles.

Architecture

Explain modules.

Database

Explain major relationships.

Loan calculation

Explain FLAT and REDUCING_BALANCE.

Collateral release

Explain eligibility workflow.

Testing

Explain how to run tests.

Prototype limitations

Clearly state what is not production-ready.

---

67. DATABASE RELATIONSHIPS

Implement approximately:

Customer
→ hasMany Employments

Customer
→ hasMany Loans

Loan
→ belongsTo Customer

Loan
→ hasMany Installments

Loan
→ hasMany Payments

Loan
→ hasMany CollectionActivities

Loan
→ hasMany Collaterals

Installment
→ hasMany Payments

Collateral
→ belongsTo Loan

Collateral
→ belongsTo Customer

Collateral
→ hasMany/hasOne Releases

IdentityVerification
→ belongsTo Customer

IdentityVerification
→ belongsTo Loan

CollateralRelease
→ belongsTo Collateral

CollateralRelease
→ belongsTo Loan

CollateralRelease
→ belongsTo Customer

AuditLog
→ belongsTo User

---

68. INDEXES

Create indexes for:

customers.customer_code

customers.national_id_number

customers.phone

loans.loan_number

loans.customer_id

loans.status

installments.loan_id

installments.due_date

installments.status

payments.payment_number

payments.loan_id

collaterals.collateral_code

collaterals.loan_id

collaterals.custody_status

audit_logs.entity_type

audit_logs.entity_id

audit_logs.created_at

---

69. NO PRODUCTION CLAIM

This is a prototype.

Do not claim:

- regulatory compliance
- legal validity
- government identity verification
- production security certification
- accounting compliance
- lending license
- guaranteed data protection compliance

At the end show:

PROTOTYPE LIMITATIONS

Include areas requiring professional/legal/security review before real-world deployment.

---

70. BUILD STRATEGY

Do not generate an unstructured giant code dump.

Build incrementally.

Phase 1

Project setup

Authentication

Database

Roles

Permissions

Layout

Phase 2

Customers

Employment

Phase 3

Loans

Interest calculations

Loan state machine

Installments

Phase 4

Payments

Payment allocation

Reversal

Phase 5

Collection

LC dashboard

Phase 6

Collateral

Custody

Phase 7

Identity verification

Collateral release

Phase 8

Audit logs

Reports

Printable documents

Phase 9

Testing

Security review

UX polish

---

71. AFTER EACH PHASE

After each phase:

1. Run migrations.
2. Run seeders if appropriate.
3. Run tests.
4. Check for errors.
5. Fix errors.
6. Verify database relationships.
7. Verify permissions.
8. Verify UI workflow.
9. Continue only after the phase works.

Do not leave known build errors unresolved.

---

72. FINAL QUALITY CHECK

Before considering the prototype complete, verify:

- application boots
- login works
- every role works
- database migrations work on SQLite
- seeders work
- customer CRUD works
- loan calculations work
- installment schedules work
- payment allocation works
- payment reversal works
- collection works
- collateral receiving works
- identity verification works
- collateral release protection works
- audit logs work
- reports work
- printable documents work
- unauthorized actions are blocked
- tests pass

---

73. FINAL OUTPUT

At completion provide a concise technical summary containing:

1. What was implemented
2. Database schema
3. Main services
4. Main workflows
5. Demo accounts
6. Test status
7. Known limitations
8. How to run the application

Do not merely say "the application is complete."

Explain what actually exists.

---

74. MOST IMPORTANT RULE

The application must prioritize:

DATA INTEGRITY > FINANCIAL CORRECTNESS > SECURITY > AUDITABILITY > UX > VISUAL POLISH

A beautiful interface with incorrect financial calculations is a failed application.

A working loan calculator without auditability is a failed application.

A collateral-release system that allows unauthorized release is a failed application.

Build NADI as a coherent loan-management system where the database, business rules, permissions, and UI all enforce the same workflow.
