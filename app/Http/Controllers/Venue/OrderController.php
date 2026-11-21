<?php

namespace App\Http\Controllers\Venue;

use App\Enums\OrderItemStatus;
use App\Enums\OrderObjectionCategory;
use App\Enums\OrderObjectionStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\OrderItem;
use App\Models\OrderObjection;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\OrderObjectionSubmittedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Controller Pemilik Venue untuk memantau pesanan venue (read-only) dan mengajukan keberatan.
 * Aturan: Acara ditampilkan sebagai 'Acara #ID', tanpa nama klien.
 */
class OrderController extends Controller
{
    /**
     * Daftar pesanan venue (read-only).
     */
    public function index(Request $request): View
    {
        $venueProfile = $request->user()->venueProfile;
        abort_unless($venueProfile, 403, 'Profil venue tidak ditemukan.');

        $statusTab = $request->query('status', 'aktif');

        $query = OrderItem::query()
            ->where('provider_type', 'venue_profile')
            ->where('provider_id', $venueProfile->id)
            ->with(['event', 'subject'])
            ->latest();

        if ($statusTab === 'keberatan') {
            $query->where(function ($q) {
                $q->whereHas('objections')
                    ->orWhere(function ($open) {
                        $open->whereIn('status', [OrderItemStatus::Disetujui, OrderItemStatus::Terkonfirmasi])
                            ->where('objection_deadline', '>', now());
                    });
            });
        } elseif ($statusTab === 'riwayat') {
            $query->whereIn('status', [
                OrderItemStatus::Selesai,
                OrderItemStatus::Ditolak,
                OrderItemStatus::Dialihkan,
                OrderItemStatus::Dibatalkan,
            ]);
        } else {
            $query->whereIn('status', [
                OrderItemStatus::MenungguReview,
                OrderItemStatus::Disetujui,
                OrderItemStatus::Terkonfirmasi,
            ]);
        }

        $orderItems = $query->paginate(15)->withQueryString();

        return view('venue.pesanan', [
            'orderItems' => $orderItems,
            'statusTab' => $statusTab,
            'venueProfile' => $venueProfile,
        ]);
    }

    /**
     * Detail satu pesanan venue (read-only).
     */
    public function show(Request $request, OrderItem $orderItem): View
    {
        $venueProfile = $request->user()->venueProfile;
        abort_unless($venueProfile, 403, 'Profil venue tidak ditemukan.');
        abort_unless(
            $orderItem->provider_type === 'venue_profile' && $orderItem->provider_id === $venueProfile->id,
            403,
            'Anda tidak memiliki akses ke pesanan venue ini.'
        );

        $orderItem->load(['event.rundownItems', 'subject', 'objections.evidence']);

        $now = now();
        $windowHours = (int) Setting::get('objection_window_hours', 24);
        $deadline = $orderItem->objection_deadline
            ?? ($orderItem->approved_at ? Carbon::parse($orderItem->approved_at)->addHours($windowHours) : null);

        $canFileObjection = false;
        $isEmergency = false;

        if ($orderItem->status === OrderItemStatus::Disetujui) {
            $canFileObjection = $deadline && $now->lte($deadline);
        } elseif ($orderItem->status === OrderItemStatus::Terkonfirmasi) {
            $canFileObjection = true;
            $isEmergency = true;
        }

        $latestObjection = $orderItem->objections()->latest()->first();

        return view('venue.pesanan-detail', [
            'orderItem' => $orderItem,
            'event' => $orderItem->event,
            'deadline' => $deadline,
            'canFileObjection' => $canFileObjection,
            'isEmergency' => $isEmergency,
            'latestObjection' => $latestObjection,
        ]);
    }

    /**
     * Ajukan keberatan atas pesanan venue.
     * Tidak mengubah status pesanan sampai Admin memutuskan.
     */
    public function storeObjection(Request $request, OrderItem $orderItem): RedirectResponse
    {
        $venueProfile = $request->user()->venueProfile;
        abort_unless($venueProfile, 403, 'Profil venue tidak ditemukan.');
        abort_unless(
            $orderItem->provider_type === 'venue_profile' && $orderItem->provider_id === $venueProfile->id,
            403,
            'Anda tidak memiliki akses ke pesanan venue ini.'
        );

        $validated = $request->validate([
            'category' => 'required|in:alat_rusak,jadwal_bentrok,kapasitas,lainnya',
            'reason' => 'required|string|min:5|max:1000',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $now = now();
        $windowHours = (int) Setting::get('objection_window_hours', 24);
        $deadline = $orderItem->objection_deadline
            ?? ($orderItem->approved_at ? Carbon::parse($orderItem->approved_at)->addHours($windowHours) : null);

        $isEmergency = $orderItem->status === OrderItemStatus::Terkonfirmasi;

        if (! $isEmergency) {
            if ($orderItem->status !== OrderItemStatus::Disetujui) {
                return redirect()->back()->with('error', 'Keberatan hanya dapat diajukan untuk pesanan yang disetujui.');
            }
            if ($deadline && $now->gt($deadline)) {
                return redirect()->back()->with('error', 'Masa keberatan untuk pesanan ini telah berakhir.');
            }
        } else {
            if (! $request->hasFile('evidence')) {
                return redirect()->back()->with('error', 'Bukti pendukung wajib dilampirkan untuk keberatan darurat.');
            }
        }

        return DB::transaction(function () use ($request, $validated, $orderItem, $isEmergency) {
            $evidenceMediaId = null;
            if ($request->hasFile('evidence')) {
                $file = $request->file('evidence');
                $path = $file->store('objections', 'private');
                $media = Media::create([
                    'disk' => 'private',
                    'path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => $request->user()->id,
                ]);
                $evidenceMediaId = $media->id;
            }

            $objection = OrderObjection::create([
                'order_item_id' => $orderItem->id,
                'provider_user_id' => $request->user()->id,
                'category' => OrderObjectionCategory::from($validated['category']),
                'reason' => $validated['reason'],
                'evidence_media_id' => $evidenceMediaId,
                'is_emergency' => $isEmergency,
                'status' => OrderObjectionStatus::Diajukan,
            ]);

            $admins = User::query()->where('role', Role::Admin->value)->get();
            foreach ($admins as $admin) {
                $admin->notify(new OrderObjectionSubmittedNotification($objection));
            }

            return redirect()
                ->route('venue.pesanan.show', $orderItem)
                ->with('success', 'Keberatan Anda berhasil diajukan dan sedang ditinjau oleh Admin Pesta Kita.');
        });
    }
}
