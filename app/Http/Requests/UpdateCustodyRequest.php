<?php

namespace App\Http\Requests;

use App\Models\Collateral;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustodyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('collaterals.update_custody') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'custody_status' => ['required', Rule::in([
                Collateral::STATUS_IN_CUSTODY,
                Collateral::STATUS_READY_FOR_RELEASE,
                Collateral::STATUS_DISPUTED,
            ])],
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
            'custody_status.required' => 'Status penyimpanan wajib dipilih.',
            'custody_status.in' => 'Status penyimpanan yang dipilih tidak valid.',
        ];
    }
}
