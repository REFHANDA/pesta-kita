<?php

namespace App\Enums;

/**
 * Status akun pengguna. Terpisah dari status verifikasi bisnis penyedia (T2):
 * akun provider baru tetap aktif meski verifikasi bisnisnya masih menunggu.
 */
enum UserStatus: string
{
    case Aktif = 'aktif';
    case Nonaktif = 'nonaktif';
}
