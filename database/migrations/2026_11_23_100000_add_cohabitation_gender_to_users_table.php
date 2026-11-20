<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RN-32: optional gender for coexistence purposes, asked only when a space restricts it
     * and never shown to anyone else.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cohabitation_gender', 10)->nullable()->after('birth_date');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('cohabitation_gender');
        });
    }
};
