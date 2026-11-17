<?php

namespace App\Http\Requests\Client;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuoteRequest extends FormRequest
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
            'event_id' => [
                'nullable',
                'integer',
                Rule::exists('events', 'id')
                    ->where(fn ($query) => $query
                        ->where('client_id', $this->user()->id)
                        ->where('status', 'draft')),
            ],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'guest_count' => ['nullable', 'integer', 'min:1'],
            'budget' => ['nullable', 'integer', 'min:0'],
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
            'subject.required' => 'Layanan atau ruangan venue wajib dipilih.',
            'subject.regex' => 'Pilihan layanan atau venue tidak valid.',
            'event_id.exists' => 'Acara harus berupa draft milik akun Anda.',
            'event_date.required' => 'Tanggal acara wajib diisi.',
            'event_date.after_or_equal' => 'Tanggal acara tidak boleh di masa lalu.',
            'guest_count.integer' => 'Jumlah tamu harus berupa bilangan bulat.',
            'guest_count.min' => 'Jumlah tamu minimal 1 orang.',
            'budget.integer' => 'Anggaran harus berupa bilangan bulat rupiah.',
            'budget.min' => 'Anggaran tidak boleh negatif.',
            'notes.max' => 'Kebutuhan khusus maksimal 2000 karakter.',
        ];
    }
}
