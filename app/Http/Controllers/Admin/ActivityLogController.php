<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Wiring read-only log aktivitas Admin: membaca tabel `audit_logs` yang sudah
 * ditulis oleh keputusan nyata (verifikasi mitra, ubah venue oleh Admin,
 * keputusan pengajuan harga). Audit log bersifat append-only — tidak ada aksi
 * tulis dari halaman ini, dan entri palsu prototipe dihapus.
 */
class ActivityLogController extends Controller
{
    /**
     * Label aksi yang sudah ditulis backend. Aksi di luar daftar ini
     * dihumanisasi di view, bukan disembunyikan.
     */
    public const ACTION_LABELS = [
        'verification_submitted' => 'Verifikasi mitra diajukan',
        'verification_approved' => 'Verifikasi mitra disetujui',
        'verification_rejected' => 'Verifikasi mitra ditolak',
        'venue_admin_update' => 'Venue diubah Admin',
        'price_change_approved' => 'Pengajuan harga disetujui',
        'price_change_rejected' => 'Pengajuan harga ditolak',
    ];

    /**
     * Batas baris daftar; log lama tetap terbaca tanpa pagination palsu.
     */
    protected const LIST_LIMIT = 200;

    /**
     * Daftar log aktivitas (filter ?aksi= dan ?q= diproses server-side).
     */
    public function index(Request $request): View
    {
        $action = $this->actionFilter($request);
        $term = $request->string('q')->trim()->toString();

        $query = AuditLog::with(['actor', 'subject'])->latest();

        if ($action !== null) {
            $query->where('action', $action);
        }

        if ($term !== '') {
            $query->where(function (Builder $inner) use ($term): void {
                $inner->where('reason', 'like', "%{$term}%")
                    ->orWhereHas('actor', function (Builder $actor) use ($term): void {
                        $actor->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    });
            });
        }

        return view('admin.aktivitas', [
            'logs' => $query->limit(self::LIST_LIMIT)->get(),
            'actionFilter' => $action,
            'term' => $term,
            'total_count' => AuditLog::count(),
        ]);
    }

    /**
     * Detail satu entri log beserta aktor dan subjek morph-nya.
     */
    public function show(AuditLog $auditLog): View
    {
        return view('admin.aktivitas-detail', [
            'log' => $auditLog->load(['actor', 'subject']),
        ]);
    }

    /**
     * Filter aksi dari query; nilai tak dikenal diperlakukan sebagai
     * "semua aksi" agar URL manual tidak memalsukan hasil.
     */
    protected function actionFilter(Request $request): ?string
    {
        $action = $request->string('aksi')->trim()->toString();

        return array_key_exists($action, self::ACTION_LABELS) ? $action : null;
    }
}
