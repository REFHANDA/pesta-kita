<?php

namespace App\Http\Requests\Verification;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

/**
 * BE-3 (B14): pengajuan verifikasi bisnis oleh penyedia login.
 * Prasyarat dokumen dan state machine diperiksa controller; request ini
 * hanya memastikan aktor adalah penyedia dengan profil usaha.
 */
class SubmitVerificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null
            && in_array($this->user()->role, [Role::Vendor, Role::Venue], true)
            && $this->user()->providerProfile() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
