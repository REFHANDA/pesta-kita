<?php

namespace App\Enums;

/**
 * Status pengajuan pembatalan acara dan proses refund.
 */
enum CancellationStatus: string
{
    case Diajukan = 'diajukan';
    case Ditinjau = 'ditinjau';
    case Disetujui = 'disetujui';
    case RefundDiproses = 'refund_diproses';
    case RefundSelesai = 'refund_selesai';
    case Ditolak = 'ditolak';

    /**
     * Status pengajuan yang masih berjalan (maksimal satu per acara).
     *
     * @return array<int, self>
     */
    public static function active(): array
    {
        return [self::Diajukan, self::Ditinjau, self::Disetujui, self::RefundDiproses];
    }

    public function label(): string
    {
        return match ($this) {
            self::Diajukan => 'Diajukan',
            self::Ditinjau => 'Ditinjau',
            self::Disetujui => 'Disetujui',
            self::RefundDiproses => 'Refund diproses',
            self::RefundSelesai => 'Refund selesai',
            self::Ditolak => 'Ditolak',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Diajukan, self::Ditinjau => 'b-warn',
            self::Disetujui, self::RefundDiproses => 'b-info',
            self::RefundSelesai => 'b-ok',
            self::Ditolak => 'b-bad',
        };
    }
}
