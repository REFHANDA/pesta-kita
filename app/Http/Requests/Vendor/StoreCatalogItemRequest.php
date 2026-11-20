<?php

namespace App\Http\Requests\Vendor;

use App\Enums\CatalogKind;
use App\Enums\PricingMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * BE-3: pembuatan item katalog vendor (B15 satu tabel, kind layanan/
 * peralatan). Harga integer rupiah. vendor_profile_id dan status tidak
 * pernah diambil dari input — kepemilikan dari akun login, status baru
 * selalu aktif (keputusan Gate 4B butir 2: tanpa persetujuan per item).
 */
class StoreCatalogItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Ability create diperiksa controller lewat CatalogItemPolicy.
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
            'kind' => ['required', Rule::enum(CatalogKind::class)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'catalog_price' => ['required', 'integer', 'min:0'],
            'pricing_mode' => ['required', Rule::enum(PricingMode::class)],
            'max_per_day' => ['nullable', 'integer', 'min:1'],
            'policies' => ['nullable', 'string'],
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
            'kind.required' => 'Jenis item wajib dipilih.',
            'kind.enum' => 'Jenis item harus layanan atau peralatan.',
            'name.required' => 'Nama item wajib diisi.',
            'category.required' => 'Kategori wajib diisi.',
            'catalog_price.required' => 'Harga katalog wajib diisi.',
            'catalog_price.integer' => 'Harga harus bilangan bulat rupiah.',
            'catalog_price.min' => 'Harga tidak boleh negatif.',
            'pricing_mode.required' => 'Mode harga wajib dipilih.',
            'pricing_mode.enum' => 'Mode harga harus tetap.',
            'max_per_day.integer' => 'Kuota harian harus bilangan bulat.',
            'max_per_day.min' => 'Kuota harian minimal 1.',
        ];
    }
}
