<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payments module: the charge schedule generated when a rental is approved (RN-70).
     * Rent and deposit are kept apart for the ledger (RN-73). Amounts never change (RN-75).
     */
    public function up(): void
    {
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('number');
            $table->string('concept', 20);
            $table->bigInteger('rent_minor');
            $table->bigInteger('deposit_minor')->default(0);
            $table->char('currency', 3)->default('MXN');
            $table->date('due_on');
            $table->string('status', 20);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['rental_id', 'number']);
            $table->index(['status', 'due_on']);
        });

        DB::statement('ALTER TABLE charges ADD CONSTRAINT charges_amounts CHECK (rent_minor > 0 AND deposit_minor >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('charges');
    }
};
