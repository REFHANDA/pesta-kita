<?php

namespace App\Http\Requests\Provider;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class StoreRundownItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === Role::Vendor;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:720'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
