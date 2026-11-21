<?php

namespace App\Http\Controllers;

use App\Enums\MediaKind;
use App\Enums\MediaVisibility;
use App\Enums\VerificationStatus;
use App\Http\Requests\Media\StorePhotoRequest;
use App\Http\Requests\Media\StoreVerificationDocumentRequest;
use App\Models\CatalogItem;
use App\Models\Media;
use App\Models\VendorProfile;
use App\Models\Venue;
use App\Models\VenueProfile;
use App\Models\VenueRoom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * BE-3 (B12/B14): satu pintu seluruh media aplikasi.
 * Baca file hanya lewat show() berpolicy (disk private serve=false);
 * tulis lewat unggah foto/dokumen tervalidasi server. Nama file acak,
 * path internal tidak pernah dibagikan ke halaman publik.
 */
class MediaController extends Controller
{
    /**
     * B12: maksimal 10 foto per item katalog/venue (dan profil).
     */
    public const MAX_PHOTOS = 10;

    /**
     * Tahap D2: maksimal 3 denah per ruangan.
     */
    public const MAX_LAYOUTS = 3;

    /**
     * Alirkan file media setelah otorisasi; bukan URL publik.
     */
    public function show(Request $request, Media $media): StreamedResponse
    {
        Gate::authorize('view', $media);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($media->disk);
        abort_unless($disk->exists($media->path), 404);

        return $disk->response($media->path, $media->original_name ?? basename($media->path), [
            'Content-Type' => $media->mime_type ?? $disk->mimeType($media->path),
        ]);
    }

    /**
     * Hapus catatan dan file fisiknya (pemilik/Admin).
     */
    public function destroy(Request $request, Media $media): RedirectResponse
    {
        Gate::authorize('delete', $media);

        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        return redirect()->back()->with('status', 'Media berhasil dihapus.');
    }

    /**
     * Unggah foto item katalog (vendor pemilik).
     */
    public function storeItemPhoto(StorePhotoRequest $request, CatalogItem $item): RedirectResponse
    {
        Gate::authorize('managePhotos', $item);

        $this->storePhoto($request, $item);

        return redirect()->back()->with('status', 'Foto berhasil diunggah.');
    }

    /**
     * Unggah foto venue (pemilik venue).
     */
    public function storeVenuePhoto(StorePhotoRequest $request, Venue $venue): RedirectResponse
    {
        Gate::authorize('managePhotos', $venue);

        $this->storePhoto($request, $venue);

        return redirect()->back()->with('status', 'Foto berhasil diunggah.');
    }

    /**
     * Unggah denah tata ruang per ruangan (Tahap D2), maksimal 3 per ruangan.
     */
    public function storeRoomLayout(StorePhotoRequest $request, VenueRoom $room): RedirectResponse
    {
        Gate::authorize('manageMedia', $room);

        DB::transaction(function () use ($request, $room): void {
            $room->newQuery()->whereKey($room->getKey())->lockForUpdate()->first();

            $count = $room->media()->where('kind', MediaKind::Denah)->count();
            if ($count >= self::MAX_LAYOUTS) {
                throw ValidationException::withMessages([
                    'photo' => 'Maksimal '.self::MAX_LAYOUTS.' denah untuk ruangan ini.',
                ]);
            }

            $file = $request->file('photo');
            $path = $file->store('media/venue_room/'.$room->getKey().'/denah', 'private');

            $profile = $room->venue?->venueProfile;
            $verified = $profile !== null
                && $profile->verification_status === VerificationStatus::Terverifikasi;

            $room->media()->create([
                'kind' => MediaKind::Denah,
                'path' => $path,
                'disk' => 'private',
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'original_name' => $file->getClientOriginalName(),
                'visibility' => $verified ? MediaVisibility::Publik : MediaVisibility::Privat,
                'sort_order' => $count,
            ]);
        });

        return redirect()->back()->with('status', 'Denah ruangan berhasil diunggah.');
    }

    /**
     * Unggah foto profil/portofolio penyedia login.
     */
    public function storeProfilePhoto(StorePhotoRequest $request): RedirectResponse
    {
        $profile = $request->user()->providerProfile();
        abort_if($profile === null, 403);

        $this->storePhoto($request, $profile);

        return redirect()->back()->with('status', 'Foto profil berhasil diunggah.');
    }

    /**
     * Unggah dokumen verifikasi (B14). Satu dokumen aktif per jenis:
     * dokumen lama dihapus hanya setelah unggahan baru berhasil disimpan.
     */
    public function storeDocument(StoreVerificationDocumentRequest $request): RedirectResponse
    {
        $profile = $request->user()->providerProfile();
        abort_if($profile === null, 403);
        Gate::authorize('uploadDocument', $profile);

        $file = $request->file('file');
        $docType = $request->validated('doc_type');

        DB::transaction(function () use ($profile, $file, $docType): void {
            $path = $file->store('media/'.$profile->getMorphClass().'/'.$profile->getKey().'/dokumen', 'private');

            $new = $profile->media()->create([
                'kind' => MediaKind::DokumenVerifikasi,
                'doc_type' => $docType,
                'path' => $path,
                'disk' => 'private',
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'original_name' => $file->getClientOriginalName(),
                'visibility' => MediaVisibility::Privat,
                'sort_order' => 0,
            ]);

            // Keputusan Gate 4B butir 10: ganti dokumen lama jenis sama.
            $old = $profile->media()->dokumenVerifikasi()
                ->where('doc_type', $docType)
                ->whereKeyNot($new->getKey())
                ->get();

            foreach ($old as $media) {
                Storage::disk($media->disk)->delete($media->path);
                $media->delete();
            }
        });

        return redirect()->back()->with('status', 'Dokumen verifikasi berhasil diunggah.');
    }

    /**
     * Simpan foto dengan batas jumlah terkunci (anti race condition).
     * Visibility mengikuti verifikasi penyedia: publik hanya bila sudah
     * terverifikasi; selain itu privat sampai keputusan approve (B12).
     */
    protected function storePhoto(Request $request, Model $owner): void
    {
        DB::transaction(function () use ($request, $owner): void {
            // Kunci baris subjek agar hitungan foto konsisten antar request.
            $owner->newQuery()->whereKey($owner->getKey())->lockForUpdate()->first();

            $count = $owner->media()->foto()->count();
            if ($count >= self::MAX_PHOTOS) {
                throw ValidationException::withMessages([
                    'photo' => 'Maksimal '.self::MAX_PHOTOS.' foto untuk subjek ini.',
                ]);
            }

            $file = $request->file('photo');
            $path = $file->store('media/'.$owner->getMorphClass().'/'.$owner->getKey(), 'private');

            $profile = $this->resolveProfile($owner);
            $verified = $profile !== null
                && $profile->verification_status === VerificationStatus::Terverifikasi;

            $owner->media()->create([
                'kind' => MediaKind::Foto,
                'path' => $path,
                'disk' => 'private',
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'original_name' => $file->getClientOriginalName(),
                'visibility' => $verified ? MediaVisibility::Publik : MediaVisibility::Privat,
                'sort_order' => $count,
            ]);
        });
    }

    /**
     * Profil penyedia penaung subjek media.
     */
    protected function resolveProfile(Model $owner): VendorProfile|VenueProfile|null
    {
        return match (true) {
            $owner instanceof VendorProfile, $owner instanceof VenueProfile => $owner,
            $owner instanceof CatalogItem => $owner->vendorProfile,
            $owner instanceof Venue => $owner->venueProfile,
            default => null,
        };
    }
}
