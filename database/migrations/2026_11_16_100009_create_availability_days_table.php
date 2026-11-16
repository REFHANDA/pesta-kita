<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BE-2: ketersediaan harian per subjek (catalog_item atau venue_room).
 * B11: hanya status manual tersedia/tidak_tersedia/belum_dikonfirmasi;
 * status dipesan tidak disimpan di sini, kelak dihitung dari order_items
 * terkonfirmasi pada tanggal yang sama. Satu baris per subjek per tanggal.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('availability_days', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->date('date');
            $table->string('status');
            $table->timestamps();

            $table->unique(['subject_type', 'subject_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability_days');
    }
};
