<?php

namespace App\Http\Requests\Media;

use App\Enums\Role;
use App\Enums\VerificationDocType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * BE-3 (B14): unggah dokumen verifikasi mitra.
 * PDF/JPG/JPEG/PNG maksimal 5 MB; jenis dokumen sesuai role:
 * Vendor ktp/nib/npwp, Venue ktp/bukti_venue/npwp. Bukti rekening bukan
 * persyaratan (keputusan Gate 4B butir 7). File selalu privat; satu
 * dokumen aktif per jenis (upload ulang menggantikan yang lama).
 */
class StoreVerificationDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Kepemilikan profil diperiksa controller lewat policy.
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
        $allowed = $this->user()?->role === Role::Venue
            ? [VerificationDocType::Ktp->value, VerificationDocType::BuktiVenue->value, VerificationDocType::Npwp->value]
            : [VerificationDocType::Ktp->value, VerificationDocType::Nib->value, VerificationDocType::Npwp->value];

        return [
            'doc_type' => ['required', 'string', Rule::in($allowed)],
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value instanceof UploadedFile && $value->isValid()) {
                        $mime = $value->getMimeType();
                        if (! in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
                            $fail('Berkas harus PDF, JPG, JPEG, atau PNG yang valid.');
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
            'doc_type.required' => 'Jenis dokumen wajib dipilih.',
            'doc_type.in' => 'Jenis dokumen tidak sesuai untuk portal Anda.',
            'file.required' => 'Berkas dokumen wajib dipilih.',
            'file.mimes' => 'Dokumen harus berformat PDF, JPG, JPEG, atau PNG.',
            'file.max' => 'Ukuran dokumen maksimal 5 MB.',
        ];
    }
}
