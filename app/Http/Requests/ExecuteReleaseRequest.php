<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExecuteReleaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('releases.execute') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'collateral_id' => ['required', 'exists:collaterals,id'],
            'identity_verification_id' => ['required', 'exists:identity_verifications,id'],
            'released_to_name' => ['required', 'string', 'max:255'],
            'relationship_to_customer' => ['required', 'string', 'max:100'],
            'release_date' => ['required', 'date', 'before_or_equal:today'],
            'release_location' => ['required', 'string', 'max:255'],
            'witness_id' => ['nullable', 'exists:users,id'],
            'customer_signature_reference' => ['nullable', 'string', 'max:255'],
            'handover_notes' => ['nullable', 'string', 'max:1000'],
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
            'collateral_id.required' => 'Jaminan wajib dipilih.',
            'collateral_id.exists' => 'Jaminan yang dipilih tidak ditemukan.',
            'identity_verification_id.required' => 'Verifikasi identitas wajib dipilih.',
            'identity_verification_id.exists' => 'Verifikasi identitas yang dipilih tidak ditemukan.',
            'released_to_name.required' => 'Nama penerima jaminan wajib diisi.',
            'released_to_name.max' => 'Nama penerima jaminan maksimal 255 karakter.',
            'relationship_to_customer.required' => 'Hubungan penerima dengan nasabah wajib diisi.',
            'relationship_to_customer.max' => 'Hubungan penerima maksimal 100 karakter.',
            'release_date.required' => 'Tanggal penyerahan wajib diisi.',
            'release_date.date' => 'Format tanggal penyerahan tidak valid.',
            'release_date.before_or_equal' => 'Tanggal penyerahan tidak boleh berasal dari masa depan.',
            'release_location.required' => 'Lokasi penyerahan wajib diisi.',
            'release_location.max' => 'Lokasi penyerahan maksimal 255 karakter.',
            'witness_id.exists' => 'Saksi yang dipilih tidak ditemukan.',
            'handover_notes.max' => 'Catatan serah terima maksimal 1.000 karakter.',
        ];
    }
}
