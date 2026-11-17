<?php

namespace App\Http\Requests\Client;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderItemRequest extends FormRequest
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
            'subject' => ['required', 'string', 'regex:/\A(?:catalog_item|venue_room):[1-9][0-9]*\z/'],
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
            'subject.regex' => 'Pilih layanan dengan harga tetap atau ruangan venue yang tersedia.',
        ];
    }
}
