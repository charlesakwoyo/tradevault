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
            $table->string('code', 40)->unique();
            $table->string('name');
            // Key into config('payments.providers'); resolves a PaymentGatewayInterface.
            $table->string('provider', 40);
            $table->boolean('supports_deposit')->default(true);
            $table->boolean('supports_withdrawal')->default(false);
            $table->json('currencies');
            $table->decimal('min_amount', 24, 8)->default(0);
            $table->decimal('max_amount', 24, 8)->nullable();
            $table->boolean('is_active')->default(false);
            // Non-secret display/config only. Credentials live in .env.
            $table->json('settings')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 24, 8);
            $table->decimal('fee', 24, 8)->default(0);
            $table->decimal('net_amount', 24, 8);
            $table->string('currency', 10);
            $table->string('status', 20)->default('pending');
            $table->string('provider_reference', 120)->nullable();
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->foreignId('wallet_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('failure_reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->unique(['payment_method_id', 'provider_reference']);
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 24, 8);
            $table->decimal('fee', 24, 8)->default(0);
            $table->decimal('net_amount', 24, 8);
            $table->string('currency', 10);
            // Encrypted at the model layer (account / phone number).
            $table->text('destination');
            $table->string('destination_masked', 60);
            $table->string('description')->nullable();
            $table->string('status', 20)->default('pending');
            // Shown to the user: why it was rejected or is under review.
            $table->string('status_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('provider_reference', 120)->nullable();
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->foreignId('reservation_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->foreignId('settlement_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['payment_method_id', 'provider_reference']);
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // Raw provider-level exchanges (STK push requests, payout calls...).
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->morphs('payable');
            $table->string('provider', 40);
            $table->string('direction', 10);
            $table->string('provider_reference', 120)->nullable();
            $table->decimal('amount', 24, 8);
            $table->string('currency', 10);
            $table->string('status', 20);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_reference']);
        });

        // Every inbound webhook is recorded once; (provider, event_id) makes replays no-ops.
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40);
            $table->string('event_id', 150);
            $table->boolean('signature_valid')->default(false);
            $table->string('status', 20)->default('received');
            $table->json('payload');
            $table->json('headers')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('deposits');
        Schema::dropIfExists('payment_methods');
    }
};
