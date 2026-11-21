<?php

namespace App\Http\Requests\Venue;

/**
 * BE-3: pembaruan venue milik sendiri. Ruangan dikelola lewat endpoint
 * terpisah; fasilitas disinkronkan dari daftar. cleaning_fee dan
 * verification_status tidak pernah diterima dari pemilik (whitelist).
 */
class UpdateVenueRequest extends StoreVenueRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['rooms'], $rules['rooms.*.name'], $rules['rooms.*.capacity']);

        return $rules;
    }
}
