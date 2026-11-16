<?php

namespace App\Http\Requests\Venue;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * BE-3b: pengajuan perubahan harga sewa venue milik pemilik login.
 * old_price tidak diambil dari input — selalu dibaca server dari harga
 * aktif venue saat controller menyimpan (aturan terkunci A11).
 */
class StorePriceChangeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Kepemilikan venue diperiksa controller lewat VenuePolicy.
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
            'venue_id' => ['required', 'integer', Rule::exists('venues', 'id')],
            'new_price' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
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
            'venue_id.required' => 'Venue yang akan diubah harganya belum dipilih.',
            'new_price.required' => 'Harga baru wajib diisi.',
            'new_price.integer' => 'Harga harus bilangan bulat rupiah.',
            'new_price.min' => 'Harga baru harus lebih besar dari nol.',
            'reason.required' => 'Alasan perubahan wajib diisi.',
            'reason.min' => 'Alasan perubahan terlalu singkat (minimal 5 karakter).',
        ];
    }
}
