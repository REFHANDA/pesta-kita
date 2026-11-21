<?php

namespace App\Http\Requests\Rundown;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi bentuk item rundown (Tahap C1). Aturan peran, status acara,
 * kepemilikan pesanan, dan batas 100 item ditegakkan RundownService.
 */
class SaveRundownItemRequest extends FormRequest
{
    /**
     * Otorisasi dilakukan lewat RundownItemPolicy di controller.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Aturan validasi item rundown.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_time' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'end_time' => [
                'nullable',
                'string',
                'regex:/^([01]\d|2[0-3]):[0-5]\d$/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $start = (string) $this->input('start_time');
                    if (is_string($value) && $value !== '' && $start !== '' && $value <= $start) {
                        $fail('Jam selesai harus setelah jam mulai.');
                    }
                },
            ],
            'activity' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'order_item_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * Pesan validasi Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_time.required' => 'Jam mulai wajib diisi.',
            'start_time.regex' => 'Jam mulai harus berformat HH:MM (24 jam), misalnya 09:30.',
            'end_time.regex' => 'Jam selesai harus berformat HH:MM (24 jam), misalnya 11:00.',
            'activity.required' => 'Kegiatan wajib diisi.',
            'activity.max' => 'Kegiatan maksimal 150 karakter.',
            'description.max' => 'Keterangan maksimal 1000 karakter.',
            'order_item_id.integer' => 'Pesanan terkait tidak valid.',
        ];
    }
}
