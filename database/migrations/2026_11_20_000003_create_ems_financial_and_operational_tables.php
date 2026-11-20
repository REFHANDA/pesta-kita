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
        // 1. Settings (parameter bisnis sistem yang dapat diatur Admin)
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->timestamps();
        });

        // 2. Invoices (tagihan acara)
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained();
            $table->string('invoice_number')->unique();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('subtotal_provider');
            $table->unsignedBigInteger('total_commission');
            $table->unsignedBigInteger('cleaning_fee')->default(0);
            $table->unsignedBigInteger('grand_total');
            $table->unsignedInteger('unique_code')->default(0);
            $table->unsignedBigInteger('transfer_total');
            $table->timestamp('due_at');
            $table->string('status')->default('belum_dibayar');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
        });

        // 3. Invoice Items (rincian tagihan per pesanan, komisi, dan kebersihan)
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // harga_penyedia, komisi, kebersihan
            $table->string('description');
            $table->unsignedBigInteger('amount');
            $table->timestamps();

            $table->index('invoice_id');
        });

        // 4. Payments (bukti transfer manual dari klien)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained();
            $table->foreignId('company_bank_account_id')->constrained();
            $table->unsignedBigInteger('amount');
            $table->string('sender_name');
            $table->date('transfer_date');
            $table->foreignId('proof_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('status')->default('menunggu_validasi');
            $table->text('admin_note')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
        });

        // 5. Cancellations (pembatalan acara dan perhitungan refund)
        Schema::create('cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->text('reason');
            $table->string('cause'); // klien, vendor, force_majeure
            $table->unsignedInteger('refund_percent');
            $table->unsignedBigInteger('refund_amount');
            $table->unsignedBigInteger('unique_code_refund')->default(0);
            $table->unsignedBigInteger('provider_compensation_total')->default(0);
            $table->unsignedBigInteger('company_income')->default(0);
            $table->json('policy_snapshot')->nullable();
            $table->string('status')->default('diajukan');
            $table->foreignId('refund_proof_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_reason')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
        });

        // 6. Disbursements (pencairan hak penyedia / kompensasi pembatalan)
        Schema::create('disbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained();
            $table->string('provider_type');
            $table->unsignedBigInteger('provider_id');
            $table->string('type'); // hak_pesanan, kompensasi
            $table->unsignedBigInteger('amount');
            $table->string('status')->default('belum_cair');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('proof_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['order_item_id', 'type']);
            $table->index(['provider_type', 'provider_id', 'status']);
        });

        // 7. Rundown Items (agenda rundown acara)
        Schema::create('rundown_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('start_time', 10);
            $table->string('end_time', 10)->nullable();
            $table->string('activity');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'start_time']);
        });

        // 8. Evaluations & Items (penilaian acara)
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('system_feedback')->nullable();
            $table->timestamps();
        });

        Schema::create('evaluation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
            $table->string('target_type'); // venue, vendor, admin
            $table->unsignedBigInteger('target_id')->nullable();
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['evaluation_id', 'target_type']);
            $table->index(['target_type', 'target_id']);
        });

        // 9. Notifications (notifikasi internal sistem)
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('evaluation_items');
        Schema::dropIfExists('evaluations');
        Schema::dropIfExists('rundown_items');
        Schema::dropIfExists('disbursements');
        Schema::dropIfExists('cancellations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('settings');
    }
};
