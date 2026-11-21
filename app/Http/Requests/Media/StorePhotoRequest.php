<?php

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * BE-3 (B12): unggah foto katalog/venue/profil.
 * JPG/JPEG/PNG/WebP, maksimal 5 MB per file. Validasi MIME sisi server
 * (mimes + getimagesize), bukan hanya ekstensi dari klien. Batas jumlah
 * 10 foto per subjek ditegakkan controller dalam transaksi terkunci.
 */
class StorePhotoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Kepemilikan subjek diperiksa controller lewat policy.
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
            'photo' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value instanceof UploadedFile && $value->isValid()) {
                        if (@getimagesize($value->getRealPath()) === false) {
                            $fail('Berkas bukan gambar yang valid.');
                        }
                    }
                },
            ],
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
            'photo.required' => 'Foto wajib dipilih.',
            'photo.file' => 'Berkas foto tidak valid.',
            'photo.mimes' => 'Foto harus berformat JPG, JPEG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto maksimal 5 MB.',
        ];
    }
}
