<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BE-3 (B12): media membedakan dua kelas dengan aturan akses berbeda —
 * foto (calon publik setelah verifikasi) dan dokumen verifikasi (privat
 * selamanya). Pembeda kolom kind/doc_type, bukan ekstensi file. Metadata
 * MIME/ukuran/nama asli untuk validasi ulang dan header unduhan; disk
 * eksplisit (private, serve=false). Additive; baris lama default foto.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('kind')->default('foto')->after('owner_id');
            $table->string('doc_type')->nullable()->after('kind');
            $table->string('disk')->default('private')->after('path');
            $table->string('mime_type')->nullable()->after('disk');
            $table->unsignedBigInteger('size')->nullable()->after('mime_type');
            $table->string('original_name')->nullable()->after('size');

            $table->index(['owner_type', 'owner_id', 'kind']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex(['owner_type', 'owner_id', 'kind']);
            $table->dropColumn(['kind', 'doc_type', 'disk', 'mime_type', 'size', 'original_name']);
        });
    }
};
