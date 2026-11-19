<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Verification module: one profile per user and kind (client/owner) and its documents (E02).
     */
    public function up(): void
    {
        Schema::create('verification_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('status', 20);
            $table->string('community_type', 30)->nullable();
            $table->foreignId('institution_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('stay_ends_on')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->date('verified_until')->nullable();
            $table->timestamp('expiry_reminder_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'kind']);
            $table->index(['status', 'submitted_at']);
            $table->index(['status', 'verified_until']);
        });

        Schema::create('verification_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_profile_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('status', 20);
            $table->string('disk', 30);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_documents');
        Schema::dropIfExists('verification_profiles');
    }
};
