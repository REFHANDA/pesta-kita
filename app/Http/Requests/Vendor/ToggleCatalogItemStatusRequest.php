<?php

namespace App\Http\Requests\Vendor;

use App\Enums\CatalogItemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * BE-3: penyedia mengaktifkan/menonaktifkan item miliknya.
 * Status menunggu_persetujuan tidak dipakai (keputusan Gate 4B butir 2:
 * visibilitas mengikuti verifikasi penyedia, bukan persetujuan per item).
 */
class ToggleCatalogItemStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Kepemilikan item diperiksa controller lewat CatalogItemPolicy.
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
            'status' => ['required', Rule::in([CatalogItemStatus::Aktif->value, CatalogItemStatus::Nonaktif->value])],
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
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status harus aktif atau nonaktif.',
        ];
    }
}
