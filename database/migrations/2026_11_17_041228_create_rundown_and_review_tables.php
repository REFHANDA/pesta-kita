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
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('status');
        });

        Schema::create('rundown_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained();
            $table->foreignId('order_item_id')->nullable()->constrained();
            $table->foreignId('created_by')->constrained('users');
            $table->string('title');
            $table->time('starts_at')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'starts_at']);
        });

        Schema::create('event_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained();
            $table->foreignId('order_item_id')->constrained();
            $table->foreignId('client_id')->constrained('users');
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique('order_item_id');
            $table->index(['event_id', 'rating']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_reviews');
        Schema::dropIfExists('rundown_items');
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('closed_at');
        });
    }
};
