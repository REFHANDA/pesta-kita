<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * BE-3b: keputusan Admin atas pengajuan harga. Untuk penolakan, alasan
 * wajib (paralel RejectVerificationRequest B14); persetujuan boleh tanpa
 * catatan tambahan.
 */
class DecidePriceChangeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Otorisasi keputusan diperiksa controller (role admin + status menunggu).
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
        $rejecting = $this->route('action') === 'reject';

        return [
            'reason' => [$rejecting ? 'required' : 'nullable', 'string', 'min:5', 'max:2000'],
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
            'reason.min' => 'Alasan terlalu singkat (minimal 5 karakter).',
        ];
    }
}
