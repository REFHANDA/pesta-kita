<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BE-2: media (foto/dokumen) milik profil, katalog, atau venue via morph
 * owner. Path memakai disk local, bukan URL publik. Visibility privat
 * sampai verifikasi; profil Klien hanya membaca yang publik. Batas unggah
 * (B12) belum diputuskan dan tidak dikodekan di sini.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->string('path');
            $table->string('visibility')->default('privat');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
