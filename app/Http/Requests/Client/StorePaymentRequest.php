<?php

namespace App\Http\Requests\Client;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
            'company_bank_account_id' => ['required', 'integer', 'exists:company_bank_accounts,id'],
            'sender_name' => ['required', 'string', 'min:2', 'max:255'],
            'transferred_at' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'integer', 'min:1'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}
