<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Parameter pengaturan dinamis sistem EMS (commission_percent, SLA, refund_tiers, budget_allocation, dll.).
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    /**
     * Ambil nilai pengaturan berdasarkan kunci.
     */
    /**
     * Format nomor WhatsApp Admin yang valid setelah normalisasi.
     */
    public const ADMIN_WHATSAPP_PATTERN = '/^62[0-9]{8,13}$/';

    /**
     * Normalisasi nomor WhatsApp: buang spasi/tanda baca, awalan 0 atau +62 menjadi 62.
     */
    public static function normalizeWhatsapp(?string $number): string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return $digits;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Simpan atau perbarui nilai pengaturan.
     */
    public static function set(string $key, mixed $value): static
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
