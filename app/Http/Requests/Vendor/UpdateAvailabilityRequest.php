<?php

namespace App\Http\Requests\Vendor;

use App\Enums\AvailabilityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * BE-3b: tandai status ketersediaan satu tanggal untuk item katalog milik
 * vendor login. Kepemilikan item diperiksa controller lewat policy;
 * tanggal tidak boleh di masa lalu.
 */
class UpdateAvailabilityRequest extends FormRequest
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
            'item_id' => ['required', 'integer', Rule::exists('catalog_items', 'id')],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'status' => ['required', Rule::enum(AvailabilityStatus::class)],
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
            'item_id.required' => 'Item untuk jadwal belum dipilih.',
            'date.required' => 'Tanggal wajib dipilih.',
            'date.date_format' => 'Tanggal harus berformat YYYY-MM-DD.',
            'date.after_or_equal' => 'Tanggal tidak boleh di masa lalu.',
            'status.required' => 'Status ketersediaan wajib dipilih.',
            'status.enum' => 'Status ketersediaan tidak dikenali.',
        ];
    }
}
