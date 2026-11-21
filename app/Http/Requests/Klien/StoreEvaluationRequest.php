<?php

namespace App\Http\Requests\Klien;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi bentuk evaluasi acara (Tahap C4). Daftar subjek wajib dihitung
 * server lewat EvaluationService; request ini hanya memvalidasi bentuk.
 */
class StoreEvaluationRequest extends FormRequest
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
            'ratings' => ['required', 'array', 'min:1'],
            'ratings.*' => ['required', 'array'],
            'ratings.*.rating' => ['required', 'integer', 'between:1,5'],
            'ratings.*.comment' => ['nullable', 'string', 'max:1000'],
            'system_feedback' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ratings.required' => 'Penilaian wajib diisi untuk setiap subjek acara.',
            'ratings.array' => 'Format penilaian tidak valid.',
            'ratings.*.rating.required' => 'Setiap subjek wajib diberi rating 1 sampai 5.',
            'ratings.*.rating.integer' => 'Rating harus bilangan bulat 1 sampai 5.',
            'ratings.*.rating.between' => 'Rating harus berada di antara 1 dan 5.',
            'ratings.*.comment.max' => 'Komentar maksimal 1000 karakter.',
            'system_feedback.max' => 'Masukan untuk sistem maksimal 2000 karakter.',
        ];
    }
}
