<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payments module: commission by management mode with validity (RN-50, HU-03.04).
     */
    public function up(): void
    {
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->string('management_mode', 20);
            $table->unsignedSmallInteger('percentage_bp'); // basis points: 1000 = 10 %
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->index(['management_mode', 'valid_from']);
        });

        DB::statement('ALTER TABLE commission_rules ADD CONSTRAINT commission_rules_percentage CHECK (percentage_bp BETWEEN 0 AND 10000)');
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};
