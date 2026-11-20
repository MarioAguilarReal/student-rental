<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rentals module (E08). Commercial values are copied from the plan when requested (RN-45).
     * Dates are a half-open range [starts_on, ends_on). See docs/spec/07-arquitectura.md §6.
     */
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->nullable()->unique();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained('users')->restrictOnDelete();
            $table->string('period', 20);
            $table->unsignedSmallInteger('periods');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->bigInteger('price_minor');
            $table->bigInteger('total_minor');
            $table->bigInteger('deposit_minor');
            $table->char('currency', 3)->default('MXN');
            $table->string('management_mode', 20);
            $table->unsignedInteger('commission_bp');
            $table->string('status', 20);
            $table->text('client_message')->nullable();
            $table->timestamp('rules_accepted_at');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'starts_on', 'ends_on']);
            $table->index(['property_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index(['status', 'expires_at']);
        });

        DB::statement('ALTER TABLE rentals ADD CONSTRAINT rentals_range CHECK (ends_on > starts_on)');
        DB::statement('ALTER TABLE rentals ADD CONSTRAINT rentals_amounts_not_negative CHECK (price_minor > 0 AND total_minor > 0 AND deposit_minor >= 0)');

        // RN-25, second line of defense: two blocking rentals of the same unit can never overlap.
        DB::statement(<<<'SQL'
            ALTER TABLE rentals ADD CONSTRAINT rentals_no_overlap
            EXCLUDE USING gist (unit_id WITH =, daterange(starts_on, ends_on, '[)') WITH &&)
            WHERE (status IN ('approved', 'confirmed', 'active'))
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
