<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('not_submitted')->index();
            $table->string('legal_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->char('nationality', 2)->nullable();
            $table->string('id_type', 30)->nullable();
            // Encrypted at the model layer; text to hold the ciphertext.
            $table->text('id_number')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->char('country', 2)->nullable();
            // Reason shown to the user when rejected or when more information is requested.
            $table->text('user_facing_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('kyc_profile_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('disk', 30);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->char('sha256', 64);
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kyc_profile_id', 'type']);
        });

        Schema::create('kyc_review_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kyc_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users');
            $table->text('note');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_review_notes');
        Schema::dropIfExists('kyc_documents');
        Schema::dropIfExists('kyc_profiles');
    }
};
