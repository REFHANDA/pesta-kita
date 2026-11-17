<?php

namespace App\Http\Requests\Client;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'guest_count' => ['required', 'integer', 'min:1', 'max:100000'],
            'theme' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
