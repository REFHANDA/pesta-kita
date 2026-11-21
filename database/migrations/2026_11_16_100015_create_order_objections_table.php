<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BE-2: keberatan penyedia atas item pesanan. Keberatan menjadi catatan
 * untuk Admin dan tidak mengubah status pesanan otomatis. Nilai status
 * dan admin_decision belum menjadi himpunan tertutup pada rancangan,
 * sehingga tetap string tanpa enum (C.1 hanya menetapkan default diajukan).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_objections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained();
            $table->foreignId('provider_user_id')->constrained('users');
            $table->text('reason');
            $table->string('status')->default('diajukan');
            $table->string('admin_decision')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index('order_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_objections');
    }
};
