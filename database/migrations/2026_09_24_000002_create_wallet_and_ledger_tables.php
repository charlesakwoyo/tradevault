<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * A wallet is a ledger account. User wallets belong to a user; system
         * wallets (user_id null, system_code set) are the platform-side
         * counter-accounts that keep every posting balanced (deposits clearing,
         * fee income, withdrawals clearing, adjustments...).
         *
         * `balance` is a cached projection of SUM(ledger_entries.amount) and is
         * only ever written by LedgerService inside a locked DB transaction.
         */
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('system_code', 50)->nullable();
            $table->string('type', 20);
            $table->string('currency', 10);
            $table->decimal('balance', 24, 8)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'type', 'currency']);
            $table->unique(['system_code', 'currency']);
        });

        // Journal header: one business event (a deposit, a fee, a trade fill...).
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type', 30);
            $table->decimal('amount', 24, 8);
            $table->string('currency', 10);
            $table->string('status', 20)->default('completed');
            // Idempotency key: the same business event can never be posted twice.
            $table->string('reference', 120)->unique();
            $table->string('description');
            $table->nullableMorphs('source');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reverses_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'status']);
            $table->index('created_at');
        });

        // Journal lines. Append-only. Signed amount: + credits the wallet, - debits it.
        // The amounts of all lines for one transaction sum to zero.
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_transaction_id')->constrained()->restrictOnDelete();
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 24, 8);
            $table->decimal('balance_after', 24, 8);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['wallet_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
