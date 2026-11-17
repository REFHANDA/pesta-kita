<?php

namespace App\Http\Requests\Venue;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteProposalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === Role::Venue;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'proposed_amount' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
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
            'proposed_amount.required' => 'Harga usulan wajib diisi.',
            'proposed_amount.integer' => 'Harga usulan harus berupa bilangan bulat rupiah.',
            'proposed_amount.min' => 'Harga usulan minimal Rp 1.',
            'notes.max' => 'Catatan maksimal 2000 karakter.',
        ];
    }
}
