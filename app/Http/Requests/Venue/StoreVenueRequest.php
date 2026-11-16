<?php

namespace App\Http\Requests\Venue;

use Illuminate\Foundation\Http\FormRequest;

/**
 * BE-3: pembuatan venue oleh Pemilik Venue login.
 * B4: cleaning_fee TIDAK diterima dari pemilik (hanya Admin, keputusan
 * Gate 4B butir 8). B5: deposit_amount opsional tanpa logika pembayaran.
 * Venue baru menunggu verifikasi profil (keputusan butir 3); ruangan
 * pertama wajib agar kalender seragam (desain BE-2).
 */
class StoreVenueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Ability create diperiksa controller lewat VenuePolicy.
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
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1'],
            'address' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'rules' => ['nullable', 'string'],
            'cancellation_policy' => ['nullable', 'string'],
            'rental_price' => ['required', 'integer', 'min:0'],
            'duration_hours' => ['nullable', 'integer', 'min:1'],
            'deposit_amount' => ['nullable', 'integer', 'min:0'],
            'rooms' => ['required', 'array', 'min:1'],
            'rooms.*.name' => ['required', 'string', 'max:255', 'distinct'],
            'rooms.*.capacity' => ['nullable', 'integer', 'min:1'],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['string', 'max:100', 'distinct'],
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
            'name.required' => 'Nama venue wajib diisi.',
            'kind.required' => 'Jenis venue wajib diisi.',
            'capacity.required' => 'Kapasitas venue wajib diisi.',
            'capacity.integer' => 'Kapasitas harus bilangan bulat.',
            'capacity.min' => 'Kapasitas minimal 1.',
            'address.required' => 'Alamat venue wajib diisi.',
            'rental_price.required' => 'Harga sewa wajib diisi.',
            'rental_price.integer' => 'Harga sewa harus bilangan bulat rupiah.',
            'rental_price.min' => 'Harga sewa tidak boleh negatif.',
            'duration_hours.integer' => 'Durasi sewa harus bilangan bulat jam.',
            'deposit_amount.integer' => 'Deposit harus bilangan bulat rupiah.',
            'rooms.required' => 'Minimal satu ruangan wajib diisi.',
            'rooms.min' => 'Minimal satu ruangan wajib diisi.',
            'rooms.*.name.required' => 'Nama ruangan wajib diisi.',
            'rooms.*.name.distinct' => 'Nama ruangan tidak boleh duplikat.',
            'rooms.*.capacity.min' => 'Kapasitas ruangan minimal 1.',
            'facilities.*.distinct' => 'Fasilitas tidak boleh duplikat.',
        ];
    }
}
