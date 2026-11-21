<?php

namespace App\Enums;

/**
 * Susunan ruangan acara (Tahap D2). Daftar tetap; tidak ada editor denah
 * interaktif dan tidak ada validasi kapasitas per susunan.
 */
enum RoomLayout: string
{
    case Teater = 'teater';
    case MejaBundar = 'meja_bundar';
    case Kelas = 'kelas';
    case HurufU = 'huruf_u';
    case PrasmananBerdiri = 'prasmanan_berdiri';
    case Lesehan = 'lesehan';

    public function label(): string
    {
        return match ($this) {
            self::Teater => 'Teater (kursi berbaris)',
            self::MejaBundar => 'Meja bundar',
            self::Kelas => 'Kelas',
            self::HurufU => 'Huruf U',
            self::PrasmananBerdiri => 'Prasmanan berdiri',
            self::Lesehan => 'Lesehan',
        };
    }

    /**
     * Daftar nilai untuk validasi form.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
