<?php

namespace App\Http\Requests;

use App\Models\CollectionActivity;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', CollectionActivity::class) ?? false;
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
            'contact_date' => ['required', 'date', 'before_or_equal:now'],
            'contact_method' => ['required', Rule::in([
                CollectionActivity::METHOD_PHONE,
                CollectionActivity::METHOD_WHATSAPP,
                CollectionActivity::METHOD_IN_PERSON,
                CollectionActivity::METHOD_OTHER,
            ])],
            'result' => ['required', Rule::in([
                CollectionActivity::RESULT_PAID,
                CollectionActivity::RESULT_PROMISE_TO_PAY,
                CollectionActivity::RESULT_NO_RESPONSE,
                CollectionActivity::RESULT_CONTACT_FAILED,
                CollectionActivity::RESULT_DISPUTED,
                CollectionActivity::RESULT_OTHER,
            ])],
            'promise_to_pay_date' => ['nullable', 'date', 'after_or_equal:today', 'required_if:result,PROMISE_TO_PAY'],
            'promise_to_pay_amount' => ['nullable', 'integer', 'min:1', 'required_if:result,PROMISE_TO_PAY'],
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
            'contact_date.required' => 'Waktu kontak wajib diisi.',
            'contact_date.date' => 'Format waktu kontak tidak valid.',
            'contact_date.before_or_equal' => 'Waktu kontak tidak boleh berada di masa depan.',
            'contact_method.required' => 'Metode kontak wajib dipilih.',
            'contact_method.in' => 'Metode kontak yang dipilih tidak valid.',
            'result.required' => 'Hasil penagihan wajib dipilih.',
            'result.in' => 'Hasil penagihan yang dipilih tidak valid.',
            'promise_to_pay_date.required_if' => 'Tanggal janji bayar wajib diisi saat hasilnya janji bayar.',
            'promise_to_pay_date.date' => 'Format tanggal janji bayar tidak valid.',
            'promise_to_pay_date.after_or_equal' => 'Tanggal janji bayar tidak boleh berada di masa lalu.',
            'promise_to_pay_amount.required_if' => 'Nominal janji bayar wajib diisi saat hasilnya janji bayar.',
            'promise_to_pay_amount.integer' => 'Nominal janji bayar harus berupa angka Rupiah utuh.',
            'promise_to_pay_amount.min' => 'Nominal janji bayar minimal Rp 1.',
            'notes.max' => 'Catatan maksimal 1.000 karakter.',
        ];
    }
}
