<?php

namespace App\Http\Requests;

use App\Models\Loan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('loans.create') ?? false;
    }

    /**
     * Convert the interest percentage into basis points before validation.
     */
    protected function prepareForValidation(): void
    {
        $rate = $this->input('interest_rate');

        if (is_numeric($rate)) {
            $this->merge([
                'interest_rate_bps' => (int) round((float) $rate * 100),
            ]);
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
            'customer_id' => ['required', 'exists:customers,id'],
            'principal_amount' => ['required', 'integer', 'min:1'],
            'interest_rate' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'interest_rate_bps' => ['required', 'integer', 'min:1', 'max:10000'],
            'interest_method' => ['required', Rule::in([Loan::METHOD_FLAT, Loan::METHOD_REDUCING_BALANCE])],
            'tenor' => ['required', 'integer', 'min:1', 'max:120'],
            'installment_frequency' => ['required', Rule::in([Loan::FREQUENCY_MONTHLY, Loan::FREQUENCY_WEEKLY])],
            'disbursement_date' => ['nullable', 'date'],
            'first_due_date' => ['required', 'date', 'after_or_equal:disbursement_date'],
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
            'customer_id.required' => 'Nasabah wajib dipilih.',
            'customer_id.exists' => 'Nasabah yang dipilih tidak ditemukan.',
            'principal_amount.required' => 'Pokok pinjaman wajib diisi.',
            'principal_amount.integer' => 'Pokok pinjaman harus berupa angka Rupiah utuh.',
            'principal_amount.min' => 'Pokok pinjaman minimal Rp 1.',
            'interest_rate.required' => 'Suku bunga wajib diisi.',
            'interest_rate.numeric' => 'Suku bunga harus berupa angka (contoh: 2 untuk 2%).',
            'interest_rate.min' => 'Suku bunga minimal 0,01%.',
            'interest_rate.max' => 'Suku bunga maksimal 100%.',
            'interest_rate_bps.required' => 'Suku bunga tidak valid.',
            'interest_rate_bps.integer' => 'Suku bunga harus berupa bilangan bulat basis poin.',
            'interest_rate_bps.min' => 'Suku bunga minimal 1 basis poin.',
            'interest_rate_bps.max' => 'Suku bunga maksimal 10.000 basis poin.',
            'interest_method.required' => 'Metode bunga wajib dipilih.',
            'interest_method.in' => 'Metode bunga yang dipilih tidak valid.',
            'tenor.required' => 'Tenor wajib diisi.',
            'tenor.integer' => 'Tenor harus berupa angka bulat (jumlah periode).',
            'tenor.min' => 'Tenor minimal 1 periode.',
            'tenor.max' => 'Tenor maksimal 120 periode.',
            'installment_frequency.required' => 'Frekuensi angsuran wajib dipilih.',
            'installment_frequency.in' => 'Frekuensi angsuran yang dipilih tidak valid.',
            'disbursement_date.date' => 'Format tanggal pencairan tidak valid.',
            'first_due_date.required' => 'Tanggal jatuh tempo pertama wajib diisi.',
            'first_due_date.date' => 'Format tanggal jatuh tempo pertama tidak valid.',
            'first_due_date.after_or_equal' => 'Tanggal jatuh tempo pertama tidak boleh mendahului tanggal pencairan.',
        ];
    }
}
