<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validasi parameter sistem di halaman Pengaturan Admin. Semua aturan
 * dicek di server; nomor WhatsApp dinormalisasi sebelum divalidasi.
 */
class UpdateSettingsRequest extends FormRequest
{
    /**
     * Kategori alokasi budget rekomendasi (total wajib 100%).
     */
    public const BUDGET_CATEGORIES = ['venue', 'catering', 'dekorasi', 'hiburan', 'peralatan', 'cadangan'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $whatsapp = trim((string) $this->input('admin_whatsapp', ''));

        $tiers = collect($this->input('refund_tiers', []))
            ->filter(fn ($row): bool => is_array($row) && (($row['min_days'] ?? '') !== '' || ($row['percent'] ?? '') !== ''))
            ->values()
            ->all();

        $this->merge([
            'admin_whatsapp' => $whatsapp === '' ? '' : Setting::normalizeWhatsapp($whatsapp),
            'unique_code_enabled' => $this->boolean('unique_code_enabled'),
            'refund_tiers' => $tiers,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'commission_percent' => ['required', 'integer', 'min:0', 'max:50'],
            'payment_deadline_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'objection_window_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'admin_review_sla_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'unique_code_enabled' => ['required', 'boolean'],
            'admin_whatsapp' => ['nullable', 'string', 'regex:'.Setting::ADMIN_WHATSAPP_PATTERN],
            'refund_tiers' => ['required', 'array', 'min:1'],
            'refund_tiers.*.min_days' => ['required', 'integer', 'min:0', 'distinct'],
            'refund_tiers.*.percent' => ['required', 'integer', 'min:0', 'max:100'],
            'budget_allocation' => ['required', 'array'],
        ];

        foreach (self::BUDGET_CATEGORIES as $category) {
            $rules["budget_allocation.{$category}"] = ['required', 'integer', 'min:0'];
        }

        return $rules;
    }

    /**
     * Aturan lintas baris: urutan tier refund dan total alokasi budget.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $tiers = array_values($this->input('refund_tiers', []));

                for ($i = 1; $i < count($tiers); $i++) {
                    if ((int) $tiers[$i]['min_days'] >= (int) $tiers[$i - 1]['min_days']) {
                        $validator->errors()->add('refund_tiers', 'Tier refund harus diurutkan dari sisa hari terbesar ke terkecil.');

                        return;
                    }

                    if ((int) $tiers[$i]['percent'] > (int) $tiers[$i - 1]['percent']) {
                        $validator->errors()->add('refund_tiers', 'Persentase refund tidak boleh naik ketika sisa hari makin sedikit.');

                        return;
                    }
                }

                if ((int) end($tiers)['min_days'] !== 0) {
                    $validator->errors()->add('refund_tiers', 'Baris terakhir tier refund wajib untuk sisa 0 hari.');
                }

                $total = array_sum(array_map('intval', $this->input('budget_allocation', [])));

                if ($total !== 100) {
                    $validator->errors()->add('budget_allocation', "Total alokasi budget harus tepat 100% (sekarang {$total}%).");
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'integer' => ':attribute harus berupa bilangan bulat.',
            'min' => ':attribute minimal :min.',
            'max' => ':attribute maksimal :max.',
            'boolean' => ':attribute tidak valid.',
            'array' => ':attribute tidak valid.',
            'admin_whatsapp.regex' => 'Nomor WhatsApp Admin tidak valid. Gunakan format 08xx atau +62xx (8–13 digit setelah 62).',
            'refund_tiers.*.min_days.distinct' => 'Sisa hari pada tier refund tidak boleh sama.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'commission_percent' => 'Komisi platform',
            'payment_deadline_hours' => 'Batas pembayaran',
            'objection_window_hours' => 'Masa keberatan',
            'admin_review_sla_hours' => 'SLA review Admin',
            'unique_code_enabled' => 'Kode unik',
            'admin_whatsapp' => 'Nomor WhatsApp Admin',
            'refund_tiers' => 'Tier refund',
            'refund_tiers.*.min_days' => 'Sisa hari tier refund',
            'refund_tiers.*.percent' => 'Persentase refund',
            'budget_allocation' => 'Alokasi budget',
            'budget_allocation.*' => 'Persentase alokasi budget',
        ];
    }

    /**
     * Nilai siap simpan per kunci pengaturan.
     *
     * @return array<string, mixed>
     */
    public function settingValues(): array
    {
        $data = $this->validated();

        return [
            'commission_percent' => (int) $data['commission_percent'],
            'payment_deadline_hours' => (int) $data['payment_deadline_hours'],
            'objection_window_hours' => (int) $data['objection_window_hours'],
            'admin_review_sla_hours' => (int) $data['admin_review_sla_hours'],
            'unique_code_enabled' => (bool) $data['unique_code_enabled'],
            'admin_whatsapp' => (string) ($data['admin_whatsapp'] ?? ''),
            'refund_tiers' => array_map(fn (array $row): array => [
                'min_days' => (int) $row['min_days'],
                'percent' => (int) $row['percent'],
            ], array_values($data['refund_tiers'])),
            'budget_allocation' => collect(self::BUDGET_CATEGORIES)
                ->mapWithKeys(fn (string $category): array => [$category => (int) $data['budget_allocation'][$category]])
                ->all(),
        ];
    }
}
