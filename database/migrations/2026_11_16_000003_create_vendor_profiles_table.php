<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BE-1: profil usaha Vendor. Telepon pribadi tetap di users.phone dan tidak
 * di-query untuk katalog Klien. Verifikasi bisnis terpisah dari status akun (T2).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->string('category');
            $table->string('address');
            $table->string('pic_name')->nullable();
            $table->string('service_area')->nullable();
            $table->text('description')->nullable();
            $table->string('verification_status')->default('menunggu');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_profiles');
    }
};
