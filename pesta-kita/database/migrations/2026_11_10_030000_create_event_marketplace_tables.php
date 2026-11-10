<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->string('category');
            $table->string('address');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('event_date')->nullable();
            $table->unsignedInteger('guest_count')->nullable();
            $table->string('theme')->nullable();
            $table->text('description')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('name');
            $table->string('category')->index();
            $table->text('description')->nullable();
            $table->string('location')->nullable()->index();
            $table->unsignedInteger('capacity')->nullable();
            $table->decimal('price', 12, 2);
            $table->boolean('is_available')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('catalog_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->string('status')->default('pending')->index();
            $table->string('payment_status')->default('pending')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending')->index();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->text('suggestion')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('catalog_items');
        Schema::dropIfExists('events');
        Schema::dropIfExists('vendor_profiles');
    }
};
