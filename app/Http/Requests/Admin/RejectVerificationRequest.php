<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * BE-3 (B14): penolakan verifikasi mitra wajib menyertakan alasan agar
 * penyedia dapat memperbaiki dan mengunggah ulang dokumen.
 */
class RejectVerificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Ability decide diperiksa controller lewat ProviderProfilePolicy.
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
            'reason' => ['required', 'string', 'max:1000'],
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
            'reason.required' => 'Alasan penolakan wajib diisi.',
            'reason.max' => 'Alasan penolakan maksimal 1000 karakter.',
        ];
    }
}
