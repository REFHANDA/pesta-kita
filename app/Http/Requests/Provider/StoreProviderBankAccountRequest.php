<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Tahap G1: pengajuan rekening pencairan oleh penyedia.
 * Kepemilikan rekening selalu diambil dari profil milik user login, bukan
 * dari input; karena itu hanya tiga kolom rekening yang divalidasi di sini.
 * Nomor dinormalisasi (spasi dan tanda hubung dibuang) sebelum disimpan.
 */
class StoreProviderBankAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Peran dan kepemilikan profil diperiksa controller lewat route + profil login.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Normalisasi nomor rekening dan teks sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('account_number'))) {
            $this->merge([
                'account_number' => preg_replace('/[\s\-]+/', '', $this->input('account_number')),
            ]);
        }

        foreach (['bank_name', 'account_name'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:60'],
            'account_number' => ['required', 'string', 'regex:/^\d{6,20}$/'],
            'account_name' => ['required', 'string', 'max:100'],
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
            'bank_name.required' => 'Nama bank wajib diisi.',
            'bank_name.max' => 'Nama bank maksimal 60 karakter.',
            'account_number.required' => 'Nomor rekening wajib diisi.',
            'account_number.regex' => 'Nomor rekening hanya boleh berisi digit (6–20 digit).',
            'account_name.required' => 'Nama pemilik rekening wajib diisi.',
            'account_name.max' => 'Nama pemilik rekening maksimal 100 karakter.',
        ];
    }
}
