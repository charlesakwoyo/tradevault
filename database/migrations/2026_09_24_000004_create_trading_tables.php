<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 30)->unique();
            $table->string('name');
            $table->string('asset_class', 20)->index();
            $table->string('base_asset', 20);
            $table->string('quote_currency', 10);
            $table->unsignedTinyInteger('price_precision')->default(2);
            $table->unsignedTinyInteger('quantity_precision')->default(4);
            $table->decimal('min_quantity', 24, 8)->default(0);
            $table->decimal('max_quantity', 24, 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_tradable')->default(true);
            // 'sandbox' = simulated prices, must be labelled as such in the UI.
            $table->string('data_source', 30)->default('sandbox');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('market_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->decimal('bid', 24, 8)->nullable();
            $table->decimal('ask', 24, 8)->nullable();
            $table->decimal('last', 24, 8);
            $table->decimal('open', 24, 8)->nullable();
            $table->decimal('high', 24, 8)->nullable();
            $table->decimal('low', 24, 8)->nullable();
            $table->decimal('volume', 24, 8)->nullable();
            $table->string('source', 30);
            $table->boolean('is_simulated')->default(true);
            $table->timestamp('recorded_at');

            $table->index(['market_id', 'recorded_at']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('market_id')->constrained()->restrictOnDelete();
            $table->string('side', 4);
            $table->string('type', 10);
            $table->decimal('quantity', 24, 8);
            $table->decimal('price', 24, 8)->nullable();
            $table->decimal('stop_price', 24, 8)->nullable();
            $table->string('status', 20)->default('pending');
            $table->decimal('filled_quantity', 24, 8)->default(0);
            $table->decimal('average_fill_price', 24, 8)->nullable();
            $table->decimal('fees', 24, 8)->default(0);
            // Funds held against the order while it is open.
            $table->decimal('reserved_amount', 24, 8)->default(0);
            $table->string('external_order_id', 120)->nullable()->unique();
            $table->string('client_order_id', 120)->nullable()->unique();
            $table->string('reject_reason')->nullable();
            $table->timestamp('filled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['market_id', 'status']);
        });

        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('market_id')->constrained()->restrictOnDelete();
            $table->string('side', 4);
            $table->decimal('quantity', 24, 8);
            $table->decimal('price', 24, 8);
            $table->decimal('fee', 24, 8)->default(0);
            // Set on closing fills only; derived from average entry price vs fill price.
            $table->decimal('realized_pnl', 24, 8)->nullable();
            $table->foreignId('wallet_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_trade_id', 120)->nullable()->unique();
            $table->timestamp('executed_at');
            $table->timestamps();

            $table->index(['user_id', 'executed_at']);
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('market_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 24, 8)->default(0);
            $table->decimal('average_entry_price', 24, 8)->default(0);
            $table->decimal('realized_pnl', 24, 8)->default(0);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'market_id']);
        });

        // Daily valuation snapshots used for the portfolio performance chart.
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->string('currency', 10);
            $table->decimal('cash_balance', 24, 8);
            $table->decimal('positions_value', 24, 8);
            $table->decimal('total_value', 24, 8);
            $table->decimal('unrealized_pnl', 24, 8);
            $table->decimal('realized_pnl', 24, 8);
            $table->boolean('uses_simulated_prices')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'snapshot_date', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolios');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('trades');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('market_prices');
        Schema::dropIfExists('markets');
    }
};
