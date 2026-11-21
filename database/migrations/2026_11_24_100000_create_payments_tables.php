<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payments module (Iteración 7): payment attempts against the gateway (HU-09.01),
     * the double-entry ledger (RN-73, RN-75) and the simulated gateway's scenario (RN-72).
     */
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charge_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained('users')->restrictOnDelete();
            $table->string('gateway', 20);
            $table->string('reference', 100)->nullable()->unique();
            $table->bigInteger('amount_minor');
            $table->char('currency', 3)->default('MXN');
            $table->string('status', 20);
            $table->jsonb('response')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['charge_id', 'status']);
        });

        // Never two payments in flight for the same charge, whatever the race.
        DB::statement("CREATE UNIQUE INDEX payment_attempts_one_processing ON payment_attempts (charge_id) WHERE status = 'processing'");
        DB::statement('ALTER TABLE payment_attempts ADD CONSTRAINT payment_attempts_amount CHECK (amount_minor > 0)');

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('transaction_id');
            $table->string('account', 30);
            $table->foreignId('holder_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('rental_id')->constrained()->restrictOnDelete();
            $table->foreignId('charge_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('direction', 6);
            $table->bigInteger('amount_minor');
            $table->char('currency', 3)->default('MXN');
            $table->timestamp('created_at')->useCurrent();

            $table->index('transaction_id');
            $table->index(['account', 'holder_id']);
            $table->index('rental_id');
        });

        DB::statement('ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entries_amount CHECK (amount_minor > 0)');
        DB::statement("ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entries_direction CHECK (direction IN ('debit', 'credit'))");

        // RN-75: entries are never edited nor deleted; corrections are new adjusting entries.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION ledger_entries_immutable() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'Ledger entries are immutable (RN-75).';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER ledger_entries_no_update_or_delete
                BEFORE UPDATE OR DELETE ON ledger_entries
                FOR EACH ROW EXECUTE FUNCTION ledger_entries_immutable();
            SQL);

        Schema::create('payment_simulation_settings', function (Blueprint $table) {
            $table->id();
            $table->string('outcome', 20);
            $table->unsignedSmallInteger('delay_seconds')->default(30);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_simulation_settings');
        Schema::dropIfExists('ledger_entries');
        DB::unprepared('DROP FUNCTION IF EXISTS ledger_entries_immutable()');
        Schema::dropIfExists('payment_attempts');
    }
};
