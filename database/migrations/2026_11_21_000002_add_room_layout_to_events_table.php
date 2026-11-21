<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tahap D2: pilihan susunan ruangan acara (enum tetap) dan catatan opsional.
 * Hanya menambah kolom nullable pada events; tidak mengubah kolom lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('room_layout', 30)->nullable()->after('theme');
            $table->string('room_layout_notes', 500)->nullable()->after('room_layout');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['room_layout', 'room_layout_notes']);
        });
    }
};
