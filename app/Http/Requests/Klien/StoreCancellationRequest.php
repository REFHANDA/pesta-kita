<?php

namespace App\Http\Requests\Klien;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Pengajuan pembatalan acara setelah lunas (Tahap B3). Kepemilikan acara
 * diperiksa di controller.
 */
class StoreCancellationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'refund_bank_name' => ['required', 'string', 'max:100'],
            'refund_account_number' => ['required', 'string', 'max:50', 'regex:/^[0-9\- ]+$/'],
            'refund_account_name' => ['required', 'string', 'max:150'],
            'acknowledge' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan pembatalan wajib diisi.',
            'reason.min' => 'Alasan pembatalan minimal 10 karakter.',
            'reason.max' => 'Alasan pembatalan maksimal 2000 karakter.',
            'refund_bank_name.required' => 'Nama bank tujuan refund wajib diisi.',
            'refund_bank_name.max' => 'Nama bank maksimal 100 karakter.',
            'refund_account_number.required' => 'Nomor rekening tujuan refund wajib diisi.',
            'refund_account_number.regex' => 'Nomor rekening hanya boleh berisi angka.',
            'refund_account_number.max' => 'Nomor rekening maksimal 50 karakter.',
            'refund_account_name.required' => 'Nama pemilik rekening wajib diisi.',
            'refund_account_name.max' => 'Nama pemilik rekening maksimal 150 karakter.',
            'acknowledge.accepted' => 'Centang pernyataan bahwa nominal akhir ditetapkan Admin.',
        ];
    }
}
