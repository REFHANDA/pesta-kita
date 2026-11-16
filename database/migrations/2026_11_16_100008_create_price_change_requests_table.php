<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BE-2: pengajuan perubahan harga katalog atau venue (morph subject).
 * Selama status menunggu, harga lama tetap berlaku (aturan terkunci A11).
 * Keputusan Admin dicatat lewat reviewed_by/reviewed_at; alur keputusan
 * menyusul tahap berikutnya.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('price_change_requests', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->unsignedBigInteger('old_price');
            $table->unsignedBigInteger('new_price');
            $table->text('reason');
            $table->string('status')->default('menunggu');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_change_requests');
    }
};
