<?php

namespace App\Http\Requests\Klien;

use App\Enums\RoomLayout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi susunan ruangan acara (Tahap D2): enum tetap dan catatan maks 500.
 */
class UpdateRoomLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'room_layout' => ['nullable', 'string', Rule::in(RoomLayout::values())],
            'room_layout_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'room_layout.in' => 'Susunan ruangan tidak dikenal.',
            'room_layout_notes.max' => 'Catatan susunan maksimal 500 karakter.',
        ];
    }
}
