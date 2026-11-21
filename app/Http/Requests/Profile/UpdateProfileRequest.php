<?php

namespace App\Http\Requests\Profile;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

/**
 * BE-1: pembaruan profil pengguna login.
 * Email read-only; kepemilikan data selalu dari authenticated user,
 * bukan dari input ID. Field usaha hanya berlaku untuk vendor/venue.
 */
class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];

        if (in_array($this->user()->role, [Role::Vendor, Role::Venue], true)) {
            $rules['business_name'] = ['required', 'string', 'max:255'];
            $rules['address'] = ['required', 'string', 'max:255'];
            $rules['pic_name'] = ['nullable', 'string', 'max:255'];
            $rules['category'] = $this->user()->role === Role::Vendor
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'business_name.required' => 'Nama usaha wajib diisi.',
            'address.required' => 'Alamat usaha wajib diisi.',
            'category.required' => 'Kategori usaha wajib diisi.',
        ];
    }
}
