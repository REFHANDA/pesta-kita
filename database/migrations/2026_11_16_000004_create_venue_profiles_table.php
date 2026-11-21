<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BE-1: profil usaha Pemilik Venue. Pola sama dengan vendor_profiles;
 * category nullable karena form registrasi Venue tidak memiliki category.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('venue_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->string('category')->nullable();
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
        Schema::dropIfExists('venue_profiles');
    }
};
