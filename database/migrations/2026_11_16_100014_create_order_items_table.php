<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BE-2: item pesanan dalam acara. provider morph ke profil penyedia;
 * subject morph ke catalog_item atau venue_room (B6: tanpa events.venue_id,
 * satu acara boleh memuat beberapa venue lewat item berbeda). Tiga nominal
 * terpisah: snapshot katalog, snapshot usulan, dan final_price yang hanya
 * dikunci Admin. B7: objection_deadline hanya kolom nullable, tanpa logika.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained();
            $table->string('provider_type');
            $table->unsignedBigInteger('provider_id');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('quote_request_id')->nullable()->constrained();
            $table->unsignedBigInteger('catalog_price_snapshot');
            $table->unsignedBigInteger('proposed_price_snapshot')->nullable();
            $table->unsignedBigInteger('final_price')->nullable();
            $table->string('status')->default('menunggu_admin');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('objection_deadline')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
