<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BE-2: katalog Vendor. B15 (disetujui 2026-11-16): layanan dan peralatan
 * memakai satu tabel ini, dibedakan kolom kind; tanpa tabel peralatan
 * terpisah. Harga katalog integer rupiah; harga final kelak dikunci Admin.
 * Soft delete agar riwayat pesanan tetap utuh.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_profile_id')->constrained();
            $table->string('kind');
            $table->string('name');
            $table->string('category');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('catalog_price');
            $table->string('pricing_mode');
            $table->unsignedInteger('max_per_day')->nullable();
            $table->string('status');
            $table->text('policies')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vendor_profile_id', 'status', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_items');
    }
};
