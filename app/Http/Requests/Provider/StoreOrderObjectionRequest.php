<?php

namespace App\Http\Requests\Provider;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderObjectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [Role::Vendor, Role::Venue], true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
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
            'reason.required' => 'Alasan keberatan wajib diisi.',
            'reason.min' => 'Alasan keberatan minimal 5 karakter.',
            'reason.max' => 'Alasan keberatan maksimal 2000 karakter.',
        ];
    }
}
