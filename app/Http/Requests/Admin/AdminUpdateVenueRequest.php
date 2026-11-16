<?php

namespace App\Http\Requests\Admin;

use App\Enums\VerificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * BE-3: pengelolaan venue oleh Admin — cleaning_fee (B4, keputusan
 * Gate 4B butir 8) dan status venue individual (keputusan butir 3:
 * menonaktifkan venue tanpa memengaruhi verifikasi profil penyedia).
 */
class AdminUpdateVenueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Ability adminManage diperiksa controller lewat VenuePolicy.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cleaning_fee' => ['nullable', 'integer', 'min:0'],
            'verification_status' => ['required', Rule::in([
                VerificationStatus::Terverifikasi->value,
                VerificationStatus::Nonaktif->value,
            ])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cleaning_fee.integer' => 'Biaya kebersihan harus bilangan bulat rupiah.',
            'cleaning_fee.min' => 'Biaya kebersihan tidak boleh negatif.',
            'verification_status.required' => 'Status venue wajib dipilih.',
            'verification_status.in' => 'Status venue harus terverifikasi atau nonaktif.',
        ];
    }
}
