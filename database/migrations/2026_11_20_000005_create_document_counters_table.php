<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_counters', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30)->default('PK');
            $table->string('period', 10); // Format: YYYY/MM (contoh: 2026/11)
            $table->unsignedInteger('current_number')->default(0);
            $table->timestamps();

            $table->unique(['type', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_counters');
    }
};
