<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 20)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 30);
            $table->string('subject');
            $table->string('status', 20)->default('open');
            $table->string('priority', 10)->default('normal');
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'last_reply_at']);
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('body');
            // Staff-only notes are never shown to the customer.
            $table->boolean('is_internal')->default(false);
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->timestamps();
        });

        // Append-only: the AuditLog model refuses updates and deletes.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 80)->index();
            $table->nullableMorphs('auditable');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->json('value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_tickets');
    }
};
