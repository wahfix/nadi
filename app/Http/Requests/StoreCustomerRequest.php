<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('customers.create') ?? false;
    }

    /**
     * Parse formatted monthly income into an integer amount of Rupiah.
     */
    protected function prepareForValidation(): void
    {
        $rawIncome = $this->input('employment.estimated_monthly_income');

        if (is_string($rawIncome) && trim($rawIncome) !== '') {
            $employment = $this->input('employment', []);
            $employment['estimated_monthly_income'] = parse_rupiah($rawIncome);

            $this->merge(['employment' => $employment]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'national_id_number' => [
                'required',
                'digits:16',
                Rule::unique('customers', 'national_id_number')->whereNull('deleted_at'),
            ],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['required', Rule::in(['MALE', 'FEMALE'])],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('customers', 'phone')->whereNull('deleted_at'),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['required', 'string'],
            'city' => ['required', 'string', 'max:100'],
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'string', 'max:20'],
            'employment.company_name' => ['nullable', 'string', 'max:255'],
            'employment.department' => ['nullable', 'string', 'max:255'],
            'employment.position' => ['required_with:employment.company_name', 'nullable', 'string', 'max:255'],
            'employment.employment_type' => ['required_with:employment.company_name', 'nullable', Rule::in(['PERMANENT', 'CONTRACT', 'SELF_EMPLOYED', 'OTHER'])],
            'employment.employment_start_date' => ['nullable', 'date'],
            'employment.estimated_monthly_income' => ['required_with:employment.company_name', 'nullable', 'integer', 'min:0'],
            'employment.employment_status' => ['nullable', Rule::in(['ACTIVE', 'RESIGNED', 'TERMINATED', 'UNKNOWN'])],
            'employment.notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Nama lengkap nasabah wajib diisi.',
            'full_name.max' => 'Nama lengkap maksimal 255 karakter.',
            'national_id_number.required' => 'Nomor identitas (NIK) wajib diisi.',
            'national_id_number.digits' => 'Nomor identitas (NIK) harus terdiri dari 16 digit angka.',
            'national_id_number.unique' => 'Nomor identitas (NIK) sudah terdaftar untuk nasabah lain.',
            'date_of_birth.required' => 'Tanggal lahir wajib diisi.',
            'date_of_birth.date' => 'Format tanggal lahir tidak valid.',
            'date_of_birth.before_or_equal' => 'Tanggal lahir tidak boleh berasal dari masa depan.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'gender.in' => 'Jenis kelamin yang dipilih tidak valid.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.max' => 'Nomor telepon maksimal 20 karakter.',
            'phone.unique' => 'Nomor telepon sudah terdaftar untuk nasabah lain.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.max' => 'Alamat email maksimal 255 karakter.',
            'address.required' => 'Alamat domisili wajib diisi.',
            'city.required' => 'Kota domisili wajib diisi.',
            'city.max' => 'Kota domisili maksimal 100 karakter.',
            'emergency_contact_name.required' => 'Nama kontak darurat wajib diisi.',
            'emergency_contact_name.max' => 'Nama kontak darurat maksimal 255 karakter.',
            'emergency_contact_phone.required' => 'Nomor telepon kontak darurat wajib diisi.',
            'emergency_contact_phone.max' => 'Nomor telepon kontak darurat maksimal 20 karakter.',
            'employment.company_name.max' => 'Nama perusahaan maksimal 255 karakter.',
            'employment.department.max' => 'Nama divisi maksimal 255 karakter.',
            'employment.position.required_with' => 'Jabatan wajib diisi jika data perusahaan diisi.',
            'employment.position.max' => 'Jabatan maksimal 255 karakter.',
            'employment.employment_type.required_with' => 'Jenis pekerjaan wajib dipilih jika data perusahaan diisi.',
            'employment.employment_type.in' => 'Jenis pekerjaan yang dipilih tidak valid.',
            'employment.employment_start_date.date' => 'Format tanggal mulai bekerja tidak valid.',
            'employment.estimated_monthly_income.required_with' => 'Perkiraan penghasilan bulanan wajib diisi jika data perusahaan diisi.',
            'employment.estimated_monthly_income.integer' => 'Perkiraan penghasilan bulanan harus berupa angka.',
            'employment.estimated_monthly_income.min' => 'Perkiraan penghasilan bulanan tidak boleh negatif.',
            'employment.employment_status.in' => 'Status pekerjaan yang dipilih tidak valid.',
        ];
    }
}
