<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BE-2: ruangan venue. Kalender venue memilih ruangan; venue dengan satu
 * ruang tetap punya satu baris room agar kalender seragam. Nama ruangan
 * unik per venue; penghapusan venue mengalir cascade ke ruangan.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('venue_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('capacity')->nullable();
            $table->timestamps();

            $table->unique(['venue_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_rooms');
    }
};
