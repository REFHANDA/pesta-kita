<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BE-2: usulan harga penyedia atas permintaan penawaran. Vendor mengisi
 * harga usulan, rincian, dan catatan; venue mengisi harga sewa, biaya
 * tambahan, dan catatan. Usulan tidak menjadi harga final sebelum
 * keputusan Admin.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quote_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('proposed_amount');
            $table->json('breakdown')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index('quote_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_proposals');
    }
};
