<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BE-2: permintaan penawaran Klien atas katalog atau ruangan venue.
 * event_id nullable selama acara masih draft. catalog_price_snapshot
 * menyimpan harga katalog saat pengajuan; harga usulan dan harga final
 * tetap terpisah (aturan terkunci A5).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users');
            $table->foreignId('event_id')->nullable()->constrained();
            $table->morphs('subject');
            $table->date('event_date');
            $table->unsignedInteger('guest_count')->nullable();
            $table->unsignedBigInteger('budget')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('catalog_price_snapshot');
            $table->string('status')->default('diajukan');
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index('event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};
