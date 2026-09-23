<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Deliberately no "expected return" / "profit rate" column: plans never promise returns.
        Schema::create('investment_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('currency', 10);
            $table->decimal('min_amount', 24, 8);
            $table->decimal('max_amount', 24, 8)->nullable();
            $table->unsignedInteger('duration_days');
            $table->string('fee_type', 20)->default('percentage');
            $table->decimal('fee_value', 24, 8)->default(0);
            $table->string('risk_level', 20);
            $table->text('risk_disclosure');
            $table->string('status', 20)->default('draft')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('investment_plan_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 24, 8);
            $table->decimal('fee', 24, 8)->default(0);
            $table->string('currency', 10);
            $table->string('status', 20)->default('active');
            // Only ever set from actual performance of the underlying holdings.
            $table->decimal('current_value', 24, 8)->nullable();
            $table->foreignId('wallet_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('matures_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 20);
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('market_id')->nullable()->constrained()->nullOnDelete();
            $table->string('currency', 10)->nullable();
            $table->decimal('fixed_amount', 24, 8)->default(0);
            $table->decimal('percentage', 8, 4)->default(0);
            $table->decimal('min_fee', 24, 8)->nullable();
            $table->decimal('max_fee', 24, 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fees');
        Schema::dropIfExists('investments');
        Schema::dropIfExists('investment_plans');
    }
};
