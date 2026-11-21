<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\VerificationDocType;
use App\Enums\VerificationStatus;
use App\Http\Requests\Verification\SubmitVerificationRequest;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * BE-3 (B14): halaman verifikasi penyedia dan pengajuan verifikasi.
 * Alur: akun aktif (T2) -> lengkapi profil/dokumen -> ajukan -> Admin
 * meninjau. Penolakan tidak menonaktifkan akun; penyedia dapat memperbaiki
 * dan mengajukan ulang. Dokumen wajib: KTP (semua), bukti venue (venue).
 */
class VerificationController extends Controller
{
    /**
     * Halaman verifikasi sesuai portal penyedia login.
     */
    public function show(Request $request): View
    {
        $profile = $request->user()->providerProfile();
        abort_if($profile === null, 403);

        $view = $request->user()->role === Role::Vendor ? 'vendor.verifikasi' : 'venue.verifikasi';

        return view($view, [
            'profile' => $profile,
            'documents' => $profile->media()->dokumenVerifikasi()->get()->keyBy(fn ($media) => $media->doc_type->value),
            'requiredTypes' => $this->requiredDocTypes($request->user()->role),
        ]);
    }

    /**
     * Ajukan (atau ajukan ulang setelah ditolak) verifikasi bisnis.
     */
    public function submit(SubmitVerificationRequest $request): RedirectResponse
    {
        $profile = $request->user()->providerProfile();
        Gate::authorize('submit', $profile);

        // Selalu baca status terkini dari database (hindari relasi ter-cache).
        $profile->refresh();

        if (! in_array($profile->verification_status, [VerificationStatus::Menunggu, VerificationStatus::Ditolak], true)) {
            return redirect()->back()->withErrors([
                'verification' => 'Verifikasi tidak dapat diajukan pada status saat ini.',
            ]);
        }

        // B14: dokumen wajib harus sudah diunggah sebelum mengajukan.
        $uploaded = $profile->media()->dokumenVerifikasi()->pluck('doc_type')->all();
        $missing = array_filter(
            $this->requiredDocTypes($request->user()->role),
            fn (VerificationDocType $type) => ! in_array($type, $uploaded, true)
        );

        if ($missing !== []) {
            $labels = implode(', ', array_map(fn (VerificationDocType $type) => $type->label(), $missing));

            return redirect()->back()->withErrors([
                'verification' => 'Lengkapi dokumen wajib sebelum mengajukan: '.$labels.'.',
            ]);
        }

        DB::transaction(function () use ($request, $profile): void {
            $from = $profile->verification_status->value;

            $profile->update([
                'verification_status' => VerificationStatus::Menunggu,
                'verification_submitted_at' => now(),
                'verification_note' => null,
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'verification_submitted',
                'subject_type' => $profile->getMorphClass(),
                'subject_id' => $profile->id,
                'from_status' => $from,
                'to_status' => VerificationStatus::Menunggu->value,
                'reason' => null,
                'ip' => $request->ip(),
            ]);
        });

        return redirect()->back()->with('status', 'Pengajuan verifikasi dikirim. Admin akan meninjau dokumen Anda.');
    }

    /**
     * Dokumen wajib per role (B14): nib/npwp opsional, bukti rekening
     * bukan persyaratan (keputusan Gate 4B butir 7).
     *
     * @return list<VerificationDocType>
     */
    protected function requiredDocTypes(Role $role): array
    {
        return $role === Role::Venue
            ? [VerificationDocType::Ktp, VerificationDocType::BuktiVenue]
            : [VerificationDocType::Ktp];
    }
}
