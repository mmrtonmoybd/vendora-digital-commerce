<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key', 40)->unique();
            $table->string('type', 20);
            $table->string('driver', 40)->nullable();
            $table->string('chip_color', 40)->default('bg-stone-900');
            $table->string('short', 4)->default('W');
            $table->string('logo')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_type', 40)->nullable();
            $table->text('instructions')->nullable();

            // Amounts in paisa; the fee rate is basis points so no float is ever needed.
            $table->unsignedBigInteger('min_amount')->default(0);
            $table->unsignedBigInteger('max_amount')->nullable();
            $table->unsignedInteger('fee_bps')->default(0);
            $table->unsignedBigInteger('fee_fixed')->default(0);

            $table->boolean('available_for_orders')->default(true);
            $table->boolean('available_for_deposits')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        /**
         * Append-only ledger. Nothing updates or deletes a row here; the model refuses to.
         * balance_after makes every historical balance reproducible without replaying sums.
         */
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('type', 30)->index();
            $table->bigInteger('amount');
            $table->bigInteger('balance_before');
            $table->bigInteger('balance_after');
            $table->string('idempotency_key')->unique();
            $table->nullableMorphs('reference');
            $table->string('description')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });

        /** One row per gateway attempt. The payable is an order, deposit, invoice or top-up. */
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->morphs('payable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway', 30)->default('shurjopay');
            $table->string('merchant_order_id')->unique();
            $table->string('sp_order_id')->nullable()->unique();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('BDT');
            $table->string('status', 20)->default('initiated');
            $table->string('sp_code', 20)->nullable();
            $table->string('sp_message')->nullable();
            $table->string('method', 60)->nullable();
            $table->string('bank_trx_id')->nullable();
            $table->string('invoice_no')->nullable();
            $table->text('checkout_url')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('verify_response')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['status', 'last_checked_at']);
        });

        /** Every callback we received, whatever it claimed. The audit trail for disputes. */
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('source', 20);
            $table->json('payload')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'created_at']);
        });

        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->string('deposit_number', 40)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('fee')->default(0);
            $table->unsignedBigInteger('payable');
            $table->string('sender_number')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('screenshot')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('admin_note')->nullable();
            $table->text('reject_reason')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->timestamps();

            // A TrxID can only be claimed once per method. This is the DB-level defence
            // against a customer reusing one receipt for two deposits.
            $table->unique(['payment_method_id', 'transaction_id'], 'deposits_method_trx_unique');
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('payment_methods');
    }
};
