<?php

namespace App\Http\Requests;

use App\Models\IdentityVerification;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVerificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('verifications.create') ?? false;
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
            'verification_method' => ['required', Rule::in([
                IdentityVerification::METHOD_GOVERNMENT_ID,
                IdentityVerification::METHOD_ACCOUNT_MATCH,
                IdentityVerification::METHOD_MANUAL_CHECK,
                IdentityVerification::METHOD_OTHER,
            ])],
            'verified_name' => ['required', 'string', 'max:255'],
            'verified_id_number' => ['required', 'string', 'max:50'],
            'result' => ['required', Rule::in([
                IdentityVerification::RESULT_VERIFIED,
                IdentityVerification::RESULT_FAILED,
                IdentityVerification::RESULT_REQUIRES_REVIEW,
            ])],
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
            'verification_method.required' => 'Metode verifikasi wajib dipilih.',
            'verification_method.in' => 'Metode verifikasi yang dipilih tidak valid.',
            'verified_name.required' => 'Nama pemohon yang diverifikasi wajib diisi.',
            'verified_name.max' => 'Nama pemohon maksimal 255 karakter.',
            'verified_id_number.required' => 'Nomor identitas pemohon wajib diisi.',
            'verified_id_number.max' => 'Nomor identitas pemohon maksimal 50 karakter.',
            'result.required' => 'Hasil verifikasi wajib dipilih.',
            'result.in' => 'Hasil verifikasi yang dipilih tidak valid.',
            'notes.max' => 'Catatan maksimal 1.000 karakter.',
        ];
    }
}
