<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUpdateVenueRequest;
use App\Models\AuditLog;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * BE-3: pengelolaan venue oleh Admin.
 * - cleaning_fee hanya dapat diubah Admin (B4, keputusan Gate 4B butir 8).
 * - Admin dapat menonaktifkan/mengaktifkan venue secara individual tanpa
 *   mengubah status verifikasi profil penyedia (keputusan butir 3).
 * Setiap perubahan dicatat di audit_logs.
 */
class VenueController extends Controller
{
    /**
     * Form Admin untuk cleaning fee dan status venue.
     */
    public function edit(Request $request, Venue $venue): View
    {
        Gate::authorize('adminManage', $venue);

        return view('admin.admin-venue-form', ['venue' => $venue->load('venueProfile')]);
    }

    /**
     * Simpan cleaning fee dan/atau status venue; catat jejak audit.
     */
    public function update(AdminUpdateVenueRequest $request, Venue $venue): RedirectResponse
    {
        Gate::authorize('adminManage', $venue);

        $data = $request->validated();

        DB::transaction(function () use ($request, $venue, $data): void {
            $fromStatus = $venue->verification_status->value;

            $venue->fill([
                'cleaning_fee' => $data['cleaning_fee'] ?? null,
                'verification_status' => $data['verification_status'],
            ]);

            if ($venue->verification_status === VerificationStatus::Terverifikasi && $venue->verified_at === null) {
                $venue->verified_at = now();
            }

            $venue->save();

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'venue_admin_update',
                'subject_type' => $venue->getMorphClass(),
                'subject_id' => $venue->id,
                'from_status' => $fromStatus,
                'to_status' => $venue->verification_status->value,
                'reason' => 'Perubahan cleaning fee/status venue oleh Admin.',
                'ip' => $request->ip(),
            ]);
        });

        return redirect()->back()->with('status', 'Pengaturan venue berhasil disimpan.');
    }
}
