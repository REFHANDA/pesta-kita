<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BE-2: acara milik Klien (client_id mengacu users). B6: tidak ada
 * events.venue_id; venue dalam acara direpresentasikan lewat order_items
 * yang mengacu ke venue_room, sehingga satu atau banyak venue sama-sama
 * mungkin. Validasi jumlah venue menunggu jawaban B6 pada BE-5.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users');
            $table->string('name');
            $table->date('event_date');
            $table->unsignedInteger('guest_count');
            $table->string('theme')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->index(['client_id', 'status', 'event_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
