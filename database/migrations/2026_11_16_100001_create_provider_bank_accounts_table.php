<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BE-2: rekening bank penyedia (Vendor/Venue) via morph owner ke profil.
 * Nomor utuh hanya untuk Admin dan pemilik akun; tampilan lain disamarkan.
 * Verifikasi rekening memakai status verifikasi yang sama dengan profil.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('provider_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('account_name');
            $table->string('verification_status')->default('menunggu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_bank_accounts');
    }
};
