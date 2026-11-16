<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BE-2: venue milik profil Pemilik Venue. B4 (disetujui 2026-11-16):
 * cleaning_fee opsional, terpisah dari rental_price, boleh null; snapshot
 * transaksi menyusul BE-6. B5: deposit_amount nullable tanpa logika.
 * Soft delete agar riwayat tetap utuh.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_profile_id')->constrained();
            $table->string('name');
            $table->string('kind');
            $table->unsignedInteger('capacity');
            $table->string('address');
            $table->text('description')->nullable();
            $table->text('rules')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->unsignedBigInteger('rental_price');
            $table->unsignedBigInteger('cleaning_fee')->nullable();
            $table->unsignedInteger('duration_hours')->nullable();
            $table->unsignedBigInteger('deposit_amount')->nullable();
            $table->string('verification_status')->default('menunggu');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['venue_profile_id', 'verification_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
