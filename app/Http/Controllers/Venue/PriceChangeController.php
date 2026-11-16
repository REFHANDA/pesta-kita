<?php

namespace App\Http\Controllers\Venue;

use App\Enums\PriceChangeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Venue\StorePriceChangeRequest;
use App\Models\PriceChangeRequest;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * BE-3b: pengajuan perubahan harga sewa venue oleh pemilik venue.
 * old_price selalu dibaca server dari rental_price aktif (aturan terkunci
 * A11); satu venue hanya boleh punya satu pengajuan menunggu.
 */
class PriceChangeController extends Controller
{
    /**
     * Riwayat pengajuan harga venue milik pemilik login + form pengajuan.
     */
    public function index(Request $request): View
    {
        $venueIds = $this->venueIds($request);

        $requests = PriceChangeRequest::with('subject')
            ->where('subject_type', 'venue')
            ->whereIn('subject_id', $venueIds)
            ->latest()
            ->get();

        return view('venue.pengajuan-harga', [
            'requests' => $requests,
            'venues' => $this->formVenues($request),
            'statusFilter' => $this->statusFilter($request),
        ]);
    }

    /**
     * Kirim pengajuan harga baru ke antrean Admin.
     */
    public function store(StorePriceChangeRequest $request): RedirectResponse
    {
        $venue = Venue::findOrFail($request->validated('venue_id'));
        Gate::authorize('update', $venue);

        if ((int) $request->validated('new_price') === (int) $venue->rental_price) {
            return redirect()->back()->withErrors([
                'new_price' => 'Harga baru harus berbeda dari harga saat ini.',
            ])->withInput();
        }

        if ($venue->priceChangeRequests()->where('status', PriceChangeStatus::Menunggu)->exists()) {
            return redirect()->back()->withErrors([
                'venue_id' => 'Venue ini masih punya pengajuan yang menunggu keputusan Admin.',
            ]);
        }

        PriceChangeRequest::create([
            'subject_type' => 'venue',
            'subject_id' => $venue->id,
            'old_price' => $venue->rental_price,
            'new_price' => $request->validated('new_price'),
            'reason' => $request->validated('reason'),
            'status' => PriceChangeStatus::Menunggu,
        ]);

        return redirect()->route('venue.pengajuan-harga')
            ->with('status', 'Pengajuan harga terkirim ke Admin. Harga lama tetap berlaku sampai diputuskan.');
    }

    /**
     * Filter status riwayat dari query (?status=), null untuk semua.
     */
    protected function statusFilter(Request $request): ?string
    {
        $status = (string) $request->query('status', '');

        return in_array($status, array_column(PriceChangeStatus::cases(), 'value'), true)
            ? $status
            : null;
    }

    /**
     * ID venue milik pemilik login.
     *
     * @return array<int, int>
     */
    protected function venueIds(Request $request): array
    {
        $profile = $request->user()->venueProfile;

        return $profile === null
            ? []
            : $profile->venues()->pluck('id')->all();
    }

    /**
     * Venue milik pemilik login yang dapat diajukan perubahan harganya.
     *
     * @return Collection<int, Venue>
     */
    protected function formVenues(Request $request): Collection
    {
        $profile = $request->user()->venueProfile;

        if ($profile === null) {
            return collect();
        }

        return $profile->venues()->orderBy('name')->get();
    }
}
