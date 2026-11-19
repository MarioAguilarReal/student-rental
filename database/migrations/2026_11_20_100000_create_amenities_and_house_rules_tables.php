<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalog module: amenities and house rules (HU-03.02, RN-33).
     */
    public function up(): void
    {
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 40);
            $table->string('scope', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('house_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_rules');
        Schema::dropIfExists('amenities');
    }
};
