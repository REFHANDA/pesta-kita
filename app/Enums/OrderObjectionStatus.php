<?php

namespace App\Enums;

/**
 * Status keberatan penyedia atas item pesanan.
 */
enum OrderObjectionStatus: string
{
    case Diajukan = 'diajukan';
    case Ditinjau = 'ditinjau';
    case Diterima = 'diterima';
    case Ditolak = 'ditolak';
}
