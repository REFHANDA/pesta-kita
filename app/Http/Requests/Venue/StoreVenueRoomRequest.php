<?php

namespace App\Http\Requests\Venue;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * BE-3: tambah ruangan pada venue milik sendiri. Nama unik per venue
 * (constraint BE-2); kepemilikan venue diperiksa controller.
 */
class StoreVenueRoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('venue_rooms', 'name')->where('venue_id', $this->route('venue')?->id),
            ],
            'capacity' => ['nullable', 'integer', 'min:1'],
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
            'name.required' => 'Nama ruangan wajib diisi.',
            'name.unique' => 'Nama ruangan sudah dipakai di venue ini.',
            'capacity.integer' => 'Kapasitas ruangan harus bilangan bulat.',
            'capacity.min' => 'Kapasitas ruangan minimal 1.',
        ];
    }
}
