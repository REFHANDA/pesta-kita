<?php

namespace App\Http\Requests\Vendor;

/**
 * BE-3: pembaruan item katalog milik vendor login.
 * Kind tidak dapat diubah setelah item dibuat; field kepemilikan dan
 * status tidak pernah diterima dari input (whitelist tervalidasi).
 */
class UpdateCatalogItemRequest extends StoreCatalogItemRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['kind']);

        return $rules;
    }
}
