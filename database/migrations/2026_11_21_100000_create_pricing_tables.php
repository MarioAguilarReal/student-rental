<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pricing module (E05): one rate plan per unit and period (RN-40), the unit's deposit
     * (RN-44) and availability blocks (RN-26). Money in minor units (cents).
     */
    public function up(): void
    {
        Schema::create('rate_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('period', 20);
            $table->bigInteger('price_minor');
            $table->char('currency', 3)->default('MXN');
            $table->unsignedSmallInteger('min_periods')->default(1);
            $table->unsignedSmallInteger('max_periods');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // RN-40: at most one plan per unit and period; disabling keeps the row.
            $table->unique(['unit_id', 'period']);
        });

        Schema::create('unit_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->unique()->constrained()->cascadeOnDelete();
            $table->bigInteger('amount_minor');
            $table->char('currency', 3)->default('MXN');
            $table->timestamps();
        });

        Schema::create('availability_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on'); // exclusive, like rentals: [starts_on, ends_on)
            $table->string('reason', 20);
            $table->string('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['unit_id', 'starts_on']);
        });

        DB::statement('ALTER TABLE rate_plans ADD CONSTRAINT rate_plans_price_positive CHECK (price_minor > 0)');
        DB::statement('ALTER TABLE rate_plans ADD CONSTRAINT rate_plans_periods_range CHECK (min_periods >= 1 AND max_periods >= min_periods)');
        DB::statement('ALTER TABLE unit_deposits ADD CONSTRAINT unit_deposits_not_negative CHECK (amount_minor >= 0)');
        DB::statement('ALTER TABLE availability_blocks ADD CONSTRAINT availability_blocks_range CHECK (ends_on > starts_on)');
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_blocks');
        Schema::dropIfExists('unit_deposits');
        Schema::dropIfExists('rate_plans');
    }
};
