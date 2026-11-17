<?php

namespace App\Http\Requests\Client;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class StoreRefundRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === Role::Klien;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
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
            'reason.required' => 'Alasan pengajuan refund wajib diisi.',
            'reason.min' => 'Alasan pengajuan refund minimal 10 karakter.',
            'reason.max' => 'Alasan pengajuan refund maksimal 2000 karakter.',
        ];
    }
}
