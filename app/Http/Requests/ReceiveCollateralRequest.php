<?php

namespace App\Http\Requests;

use App\Models\Collateral;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReceiveCollateralRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('collaterals.receive') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $rawValue = $this->input('estimated_value');

        if (is_string($rawValue) && trim($rawValue) !== '') {
            $this->merge(['estimated_value' => parse_rupiah($rawValue)]);
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
            'loan_id' => ['required', 'exists:loans,id'],
            'collateral_type' => ['required', Rule::in([
                Collateral::TYPE_DOCUMENT,
                Collateral::TYPE_VEHICLE,
                Collateral::TYPE_ELECTRONIC,
                Collateral::TYPE_OTHER,
            ])],
            'description' => ['required', 'string', 'max:1000'],
            'identification_number' => ['required', 'string', 'max:100'],
            'estimated_value' => ['required', 'integer', 'min:1'],
            'received_date' => ['required', 'date', 'before_or_equal:today'],
            'condition_on_receipt' => ['required', 'string', 'max:500'],
            'storage_location' => ['required', 'string', 'max:255'],
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
            'collateral_type.required' => 'Jenis agunan wajib dipilih.',
            'collateral_type.in' => 'Jenis agunan yang dipilih tidak valid.',
            'description.required' => 'Deskripsi agunan wajib diisi.',
            'description.max' => 'Deskripsi agunan maksimal 1.000 karakter.',
            'identification_number.required' => 'Nomor identifikasi agunan wajib diisi.',
            'identification_number.max' => 'Nomor identifikasi agunan maksimal 100 karakter.',
            'estimated_value.required' => 'Nilai taksasi agunan wajib diisi.',
            'estimated_value.integer' => 'Nilai taksasi agunan harus berupa angka.',
            'estimated_value.min' => 'Nilai taksasi agunan minimal Rp 1.',
            'received_date.required' => 'Tanggal penerimaan wajib diisi.',
            'received_date.date' => 'Format tanggal penerimaan tidak valid.',
            'received_date.before_or_equal' => 'Tanggal penerimaan tidak boleh berasal dari masa depan.',
            'condition_on_receipt.required' => 'Kondisi fisik agunan saat diterima wajib diisi.',
            'condition_on_receipt.max' => 'Kondisi fisik agunan maksimal 500 karakter.',
            'storage_location.required' => 'Lokasi penyimpanan wajib diisi.',
            'storage_location.max' => 'Lokasi penyimpanan maksimal 255 karakter.',
        ];
    }
}
