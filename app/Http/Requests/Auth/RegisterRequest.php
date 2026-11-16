<?php

namespace App\Http\Requests\Auth;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * BE-1: validasi registrasi publik.
 * Hanya klien/vendor/venue; role admin ditolak server (aturan terkunci A13).
 * Kategori vendor mengikuti pilihan yang tersedia pada form auth/register.
 */
class RegisterRequest extends FormRequest
{
    /**
     * Kategori layanan vendor sesuai form registrasi yang tersedia.
     *
     * @var array<int, string>
     */
    public const VENDOR_CATEGORIES = [
        'Katering & F&B',
        'Dekorasi Pesta',
        'Sound System & Audio',
        'MC & Hiburan',
        'Fotografi & Dokumentasi',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isProvider = in_array($this->input('role'), [Role::Vendor->value, Role::Venue->value], true);

        return [
            'role' => ['required', Rule::in([Role::Klien->value, Role::Vendor->value, Role::Venue->value])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'business_name' => [$isProvider ? 'required' : 'nullable', 'string', 'max:255'],
            'address' => [$isProvider ? 'required' : 'nullable', 'string', 'max:255'],
            'category' => $this->input('role') === Role::Vendor->value
                ? ['required', 'string', Rule::in(self::VENDOR_CATEGORIES)]
                : ['nullable', 'string', 'max:255'],
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
            'role.required' => 'Peran akun wajib dipilih.',
            'role.in' => 'Peran akun tidak valid.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sesuai.',
            'business_name.required' => 'Nama usaha wajib diisi.',
            'address.required' => 'Alamat operasional wajib diisi.',
            'category.required' => 'Kategori layanan wajib dipilih.',
            'category.in' => 'Kategori layanan tidak valid.',
        ];
    }
}
