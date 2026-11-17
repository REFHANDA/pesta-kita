<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class VerifyPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === Role::Admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rejecting = $this->route('action') === 'reject';

        return [
            'received_amount' => [$rejecting ? 'nullable' : 'required', 'integer', 'min:0'],
            'reason' => [$rejecting ? 'required' : 'nullable', 'string', 'min:5', 'max:2000'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'received_amount.required' => 'Nominal yang diterima wajib dicatat saat mengesahkan bukti.',
            'reason.required' => 'Alasan penolakan bukti wajib diisi.',
            'reason.min' => 'Alasan penolakan minimal 5 karakter.',
            'reason.max' => 'Alasan penolakan maksimal 2000 karakter.',
        ];
    }
}
