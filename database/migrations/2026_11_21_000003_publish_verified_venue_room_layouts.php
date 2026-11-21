<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * F1: perbaikan data denah ruangan yang terlanjur privat.
 *
 * Sebelum perbaikan, persetujuan verifikasi hanya mempublikasikan foto
 * pemilik sehingga denah yang diunggah sebelum verifikasi tetap privat
 * selamanya. Migrasi ini menaikkan denah berstatus privat menjadi publik
 * bila profil penyedia pemilik venue sudah terverifikasi dan venue tidak
 * dinonaktifkan Admin secara individual. Idempoten: hanya baris privat yang
 * disentuh, sehingga menjalankannya ulang tidak mengubah apa pun. Media
 * berjenis lain tidak pernah diubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('media')
            ->where('owner_type', 'venue_room')
            ->where('kind', 'denah')
            ->where('visibility', 'privat')
            ->whereIn('owner_id', function ($query): void {
                $query->select('venue_rooms.id')
                    ->from('venue_rooms')
                    ->join('venues', 'venues.id', '=', 'venue_rooms.venue_id')
                    ->join('venue_profiles', 'venue_profiles.id', '=', 'venues.venue_profile_id')
                    ->where('venue_profiles.verification_status', 'terverifikasi')
                    ->where('venues.verification_status', '!=', 'nonaktif');
            })
            ->update(['visibility' => 'publik']);
    }

    /**
     * Tidak ada pembalikan: data yang sudah publik tidak dikembalikan ke
     * privat karena itu akan menyembunyikan denah yang sah dari Klien.
     */
    public function down(): void
    {
        //
    }
};
