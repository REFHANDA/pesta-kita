<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class DecideOrderItemRequest extends FormRequest
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
            'reason.min' => 'Alasan keputusan minimal 5 karakter.',
            'reason.max' => 'Alasan keputusan maksimal 2000 karakter.',
        ];
    }
}
