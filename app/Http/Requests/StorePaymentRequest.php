<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Payment::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'loan_id' => ['required', 'exists:loans,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', Rule::in([
                Payment::METHOD_CASH,
                Payment::METHOD_BANK_TRANSFER,
                Payment::METHOD_QRIS,
                Payment::METHOD_OTHER,
            ])],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
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
            'loan_id.required' => 'Pinjaman wajib dipilih.',
            'loan_id.exists' => 'Pinjaman yang dipilih tidak ditemukan.',
            'amount.required' => 'Nominal pembayaran wajib diisi.',
            'amount.integer' => 'Nominal pembayaran harus berupa angka Rupiah utuh.',
            'amount.min' => 'Nominal pembayaran minimal Rp 1.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'payment_method.in' => 'Metode pembayaran yang dipilih tidak valid.',
            'payment_date.required' => 'Tanggal pembayaran wajib diisi.',
            'payment_date.date' => 'Format tanggal pembayaran tidak valid.',
            'payment_date.before_or_equal' => 'Tanggal pembayaran tidak boleh melewati hari ini.',
            'reference_number.max' => 'Nomor referensi maksimal 255 karakter.',
            'notes.max' => 'Catatan maksimal 1.000 karakter.',
        ];
    }
}
