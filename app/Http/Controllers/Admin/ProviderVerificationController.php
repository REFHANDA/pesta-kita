<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MediaKind;
use App\Enums\MediaVisibility;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectVerificationRequest;
use App\Models\AuditLog;
use App\Models\Media;
use App\Models\ProviderBankAccount;
use App\Models\VendorProfile;
use App\Models\VenueProfile;
use App\Models\VenueRoom;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * BE-3 (B14): peninjauan verifikasi mitra oleh Admin.
 * Antrean menunggu -> detail dokumen -> setujui/tolak (+alasan).
 * Approve mempublikasikan foto (B12) dan denah ruangan, serta venue
 * mengikuti profil (keputusan Gate 4B butir 3); dokumen tetap privat
 * selamanya.
 * Keputusan tidak pernah menyentuh status akun penyedia (T2).
 */
class ProviderVerificationController extends Controller
{
    /**
     * Antrean verifikasi profil berstatus menunggu (vendor & venue).
     * Tahap G1: filter `?rekening=menunggu|terverifikasi|ditolak|belum`
     * menampilkan penyedia menurut status rekening pencairannya, agar
     * antrean verifikasi rekening bisa ditindaklanjuti dari satu halaman.
     */
    public function index(Request $request): View
    {
        Gate::authorize('review', VendorProfile::class);

        $rekening = (string) $request->query('rekening');
        $rekening = in_array($rekening, ['menunggu', 'terverifikasi', 'ditolak', 'nonaktif', 'belum'], true)
            ? $rekening
            : null;

        $vendors = $this->providerQuery(VendorProfile::class, $rekening)->get();
        $venues = $this->providerQuery(VenueProfile::class, $rekening)->get();

        return view('admin.admin-penyedia', [
            'vendors' => $vendors,
            'venues' => $venues,
            'rekening' => $rekening,
            'rekeningMenungguCount' => ProviderBankAccount::query()
                ->where('verification_status', VerificationStatus::Menunggu->value)
                ->count(),
        ]);
    }

    /**
     * Query dasar antrean/dafar penyedia sesuai filter rekening.
     *
     * @param  class-string<VendorProfile|VenueProfile>  $model
     * @return Builder<VendorProfile|VenueProfile>
     */
    protected function providerQuery(string $model, ?string $rekening)
    {
        $query = $model::query()->with(['user', 'bankAccounts']);

        if ($rekening === null) {
            return $query->where('verification_status', VerificationStatus::Menunggu)
                ->orderBy('verification_submitted_at');
        }

        if ($rekening === 'belum') {
            return $query->whereDoesntHave('bankAccounts')->orderBy('business_name');
        }

        return $query->whereHas('bankAccounts', fn ($account) => $account->where('verification_status', $rekening))
            ->orderBy('business_name');
    }

    /**
     * Detail mitra: profil, dokumen verifikasi, dan subjek usahanya.
     */
    public function show(Request $request, string $type, int $id): View
    {
        $profile = $this->resolveProfile($type, $id);
        Gate::authorize('review', $profile);

        $subjects = $profile instanceof VendorProfile
            ? $profile->catalogItems()->latest()->get()
            : $profile->venues()->latest()->get();

        // Tahap G1: rekening pencairan (aktif + pengajuan menunggu) untuk
        // diputuskan Admin. Nomor penuh boleh tampil di halaman Admin.
        $accounts = $profile->bankAccounts();
        $pendingAccount = (clone $accounts)->where('verification_status', VerificationStatus::Menunggu->value)->latest('id')->first();

        return view('admin.mitra-detail', [
            'profile' => $profile->load('user', 'reviewer'),
            'type' => $type,
            'documents' => $profile->media()->dokumenVerifikasi()->get()->keyBy(fn ($media) => $media->doc_type->value),
            'subjects' => $subjects,
            'verifiedAccount' => (clone $accounts)->where('verification_status', VerificationStatus::Terverifikasi->value)->latest('id')->first(),
            'pendingAccount' => $pendingAccount,
            'rejectedAccount' => (clone $accounts)->where('verification_status', VerificationStatus::Ditolak->value)->latest('id')->first(),
            // Peringatan informatif (tidak memblokir): nomor sama di penyedia lain.
            'duplicateOwners' => $pendingAccount === null
                ? collect()
                : ProviderBankAccount::query()
                    ->with('owner')
                    ->where('account_number', $pendingAccount->account_number)
                    ->whereKeyNot($pendingAccount->getKey())
                    ->where(function ($query) use ($profile): void {
                        $query->where('owner_type', '!=', $profile->getMorphClass())
                            ->orWhere('owner_id', '!=', $profile->getKey());
                    })
                    ->get()
                    ->map(fn (ProviderBankAccount $account) => $account->owner?->business_name)
                    ->filter()
                    ->unique()
                    ->values(),
        ]);
    }

    /**
     * Setujui verifikasi: profil terverifikasi, foto menjadi publik,
     * venue mengikuti profil (kecuali yang dinonaktifkan individual).
     */
    public function approve(Request $request, string $type, int $id): RedirectResponse
    {
        $profile = $this->resolveProfile($type, $id);
        Gate::authorize('decide', $profile);

        if ($profile->verification_status !== VerificationStatus::Menunggu) {
            return redirect()->back()->withErrors([
                'verification' => 'Hanya pengajuan berstatus menunggu yang dapat diputuskan.',
            ]);
        }

        DB::transaction(function () use ($request, $profile): void {
            // Kunci baris profil agar keputusan tidak balapan dengan submit.
            $profile = $profile->newQuery()->whereKey($profile->getKey())->lockForUpdate()->first();

            if ($profile->verification_status !== VerificationStatus::Menunggu) {
                return;
            }

            $from = $profile->verification_status->value;

            $profile->update([
                'verification_status' => VerificationStatus::Terverifikasi,
                'verified_at' => now(),
                'reviewed_by' => $request->user()->id,
                'verification_note' => null,
            ]);

            // B12: foto profil dan subjek usaha menjadi publik; dokumen
            // verifikasi tidak pernah ikut dipublikasikan.
            $owners = collect([$profile]);
            if ($profile instanceof VendorProfile) {
                $owners = $owners->merge($profile->catalogItems);
            } else {
                $owners = $owners->merge($profile->venues);

                // Keputusan butir 3: venue mengikuti profil, kecuali yang
                // dinonaktifkan Admin secara individual.
                $profile->venues()
                    ->where('verification_status', '!=', VerificationStatus::Nonaktif)
                    ->update([
                        'verification_status' => VerificationStatus::Terverifikasi,
                        'verified_at' => now(),
                    ]);
            }

            foreach ($owners as $owner) {
                $owner->media()->foto()
                    ->where('visibility', MediaVisibility::Privat)
                    ->update(['visibility' => MediaVisibility::Publik]);
            }

            // F1: denah ruangan venue ikut menjadi publik pada keputusan yang
            // sama. Hanya denah berstatus privat yang diubah (idempoten) dan
            // venue yang dinonaktifkan Admin secara individual tetap privat
            // (keputusan butir 3).
            if ($profile instanceof VenueProfile) {
                $activeRoomIds = VenueRoom::query()
                    ->whereIn('venue_id', $profile->venues()
                        ->where('verification_status', '!=', VerificationStatus::Nonaktif)
                        ->select('id'))
                    ->pluck('id');

                Media::query()
                    ->where('owner_type', (new VenueRoom)->getMorphClass())
                    ->whereIn('owner_id', $activeRoomIds)
                    ->where('kind', MediaKind::Denah)
                    ->where('visibility', MediaVisibility::Privat)
                    ->update(['visibility' => MediaVisibility::Publik]);
            }

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'verification_approved',
                'subject_type' => $profile->getMorphClass(),
                'subject_id' => $profile->id,
                'from_status' => $from,
                'to_status' => VerificationStatus::Terverifikasi->value,
                'reason' => null,
                'ip' => $request->ip(),
            ]);
        });

        return redirect()->route('admin.penyedia.index')
            ->with('status', 'Verifikasi mitra disetujui. Foto dan katalog kini dapat tampil untuk Klien.');
    }

    /**
     * Tolak verifikasi dengan alasan; akun tetap aktif (T2), penyedia
     * dapat memperbaiki dokumen dan mengajukan ulang.
     */
    public function reject(RejectVerificationRequest $request, string $type, int $id): RedirectResponse
    {
        $profile = $this->resolveProfile($type, $id);
        Gate::authorize('decide', $profile);

        if ($profile->verification_status !== VerificationStatus::Menunggu) {
            return redirect()->back()->withErrors([
                'verification' => 'Hanya pengajuan berstatus menunggu yang dapat diputuskan.',
            ]);
        }

        DB::transaction(function () use ($request, $profile): void {
            $profile = $profile->newQuery()->whereKey($profile->getKey())->lockForUpdate()->first();

            if ($profile->verification_status !== VerificationStatus::Menunggu) {
                return;
            }

            $from = $profile->verification_status->value;

            $profile->update([
                'verification_status' => VerificationStatus::Ditolak,
                'verification_note' => $request->validated('reason'),
                'reviewed_by' => $request->user()->id,
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'verification_rejected',
                'subject_type' => $profile->getMorphClass(),
                'subject_id' => $profile->id,
                'from_status' => $from,
                'to_status' => VerificationStatus::Ditolak->value,
                'reason' => $request->validated('reason'),
                'ip' => $request->ip(),
            ]);
        });

        return redirect()->route('admin.penyedia.index')
            ->with('status', 'Verifikasi mitra ditolak. Alasan telah disampaikan ke penyedia.');
    }

    /**
     * Resolusi profil dari alias morph + id; 404 bila tidak ada.
     */
    protected function resolveProfile(string $type, int $id): VendorProfile|VenueProfile
    {
        $profile = match ($type) {
            'vendor_profile' => VendorProfile::find($id),
            'venue_profile' => VenueProfile::find($id),
            default => null,
        };

        abort_if($profile === null, 404);

        return $profile;
    }
}
