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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained();
            $table->unsignedSmallInteger('version');
            $table->unsignedSmallInteger('unique_code');
            $table->unsignedSmallInteger('active_code')->nullable()->unique();
            $table->unsignedBigInteger('provider_subtotal');
            $table->unsignedBigInteger('commission_total');
            $table->unsignedBigInteger('cleaning_total');
            $table->unsignedBigInteger('total_amount');
            $table->string('status')->default('terbit');
            $table->timestamp('issued_at');
            $table->timestamp('due_at');
            $table->timestamps();

            $table->unique(['event_id', 'version']);
            $table->index(['status', 'due_at']);
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained();
            $table->string('description_snapshot');
            $table->unsignedBigInteger('provider_amount');
            $table->unsignedBigInteger('commission_amount');
            $table->unsignedBigInteger('cleaning_fee_amount')->default(0);
            $table->unsignedBigInteger('total_amount');
            $table->timestamps();

            $table->unique(['invoice_id', 'order_item_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained();
            $table->foreignId('active_invoice_id')->nullable()->unique()->constrained('invoices')->nullOnDelete();
            $table->foreignId('company_bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('bank_name_snapshot');
            $table->string('account_number_snapshot');
            $table->string('account_name_snapshot');
            $table->string('sender_name');
            $table->unsignedBigInteger('amount');
            $table->timestamp('transferred_at');
            $table->string('proof_disk')->default('private');
            $table->string('proof_path')->nullable();
            $table->string('status')->default('menunggu_verifikasi');
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
        });

        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained();
            $table->foreignId('invoice_id')->constrained();
            $table->foreignId('client_id')->constrained('users');
            $table->text('reason');
            $table->string('status')->default('diajukan');
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->unique('event_id');
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->unique()->constrained();
            $table->foreignId('provider_bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('bank_name_snapshot');
            $table->string('account_number_snapshot');
            $table->string('account_name_snapshot');
            $table->unsignedBigInteger('amount');
            $table->date('transferred_at');
            $table->string('proof_disk')->default('private');
            $table->string('proof_path')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('refund_requests');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
    }
};
