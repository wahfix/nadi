<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Employment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SequentialNumberService $sequentialNumberService,
    ) {}

    /**
     * Register a new customer with a sequential business code and audit trail.
     *
     * @param  array<string, mixed>  $data
     */
    public function createCustomer(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $this->assertUniqueIdentity((string) $data['national_id_number'], (string) $data['phone']);

            $customer = Customer::create([
                'customer_code' => $this->sequentialNumberService->generateCustomerCode(),
                'full_name' => $data['full_name'],
                'national_id_number' => $data['national_id_number'],
                'date_of_birth' => $data['date_of_birth'],
                'gender' => $data['gender'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'address' => $data['address'],
                'city' => $data['city'],
                'emergency_contact_name' => $data['emergency_contact_name'],
                'emergency_contact_phone' => $data['emergency_contact_phone'],
                'status' => Customer::STATUS_ACTIVE,
            ]);

            if (isset($data['employment']) && is_array($data['employment'])) {
                $this->upsertEmployment($customer, $data['employment']);
            }

            $this->auditLogService->log(
                AuditLogService::CUSTOMER_CREATED,
                $customer,
                null,
                null,
                $this->customerSnapshot($customer),
            );

            return $customer;
        });
    }

    /**
     * Update an existing customer profile and record the before/after audit trail.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateCustomer(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $this->assertUniqueIdentity((string) $data['national_id_number'], (string) $data['phone'], $customer->id);

            $oldValues = $this->customerSnapshot($customer);

            $customer->update([
                'full_name' => $data['full_name'],
                'national_id_number' => $data['national_id_number'],
                'date_of_birth' => $data['date_of_birth'],
                'gender' => $data['gender'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'address' => $data['address'],
                'city' => $data['city'],
                'emergency_contact_name' => $data['emergency_contact_name'],
                'emergency_contact_phone' => $data['emergency_contact_phone'],
            ]);

            if (isset($data['employment']) && is_array($data['employment'])) {
                $this->upsertEmployment($customer, $data['employment']);
            }

            $this->auditLogService->log(
                AuditLogService::CUSTOMER_UPDATED,
                $customer,
                null,
                $oldValues,
                $this->customerSnapshot($customer),
            );

            return $customer->refresh();
        });
    }

    /**
     * Ensure the national ID and phone number are not already used by another customer.
     */
    private function assertUniqueIdentity(string $nik, string $phone, ?int $ignoreId = null): void
    {
        $matches = Customer::withTrashed()
            ->where(function ($query) use ($nik, $phone) {
                $query->where('national_id_number', $nik)->orWhere('phone', $phone);
            })
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->get(['id', 'national_id_number', 'phone']);

        if ($matches->isEmpty()) {
            return;
        }

        $errors = [];

        foreach ($matches as $match) {
            if ($match->national_id_number === $nik) {
                $errors['national_id_number'] = 'Nomor identitas (NIK) sudah terdaftar untuk nasabah lain.';
            }

            if ($match->phone === $phone) {
                $errors['phone'] = 'Nomor telepon sudah terdaftar untuk nasabah lain.';
            }
        }

        throw ValidationException::withMessages($errors);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertEmployment(Customer $customer, array $data): void
    {
        if (filled($data['company_name'] ?? null) === false) {
            return;
        }

        $employment = $customer->employments()->latest('id')->first();

        $payload = [
            'company_name' => $data['company_name'],
            'department' => $data['department'] ?? null,
            'position' => $data['position'] ?? null,
            'employment_type' => $data['employment_type'] ?? null,
            'employment_start_date' => $data['employment_start_date'] ?? null,
            'estimated_monthly_income' => $data['estimated_monthly_income'] ?? 0,
            'employment_status' => $data['employment_status'] ?? Employment::STATUS_ACTIVE,
            'notes' => $data['notes'] ?? null,
        ];

        if ($employment) {
            $employment->update($payload);
        } else {
            $customer->employments()->create($payload);
        }
    }

    /**
     * @return array<string, int|string|null>
     */
    private function customerSnapshot(Customer $customer): array
    {
        return [
            'customer_code' => $customer->customer_code,
            'full_name' => $customer->full_name,
            'national_id_number' => $customer->national_id_number,
            'date_of_birth' => $customer->date_of_birth ? CarbonImmutable::parse($customer->date_of_birth)->toDateString() : null,
            'gender' => $customer->gender,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'address' => $customer->address,
            'city' => $customer->city,
            'emergency_contact_name' => $customer->emergency_contact_name,
            'emergency_contact_phone' => $customer->emergency_contact_phone,
            'status' => $customer->status,
        ];
    }
}
