<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalog module: institutions and their campuses (HU-03.01, RN-02).
     */
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('acronym', 30)->nullable();
            $table->jsonb('email_domains')->default('[]');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('campuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 9, 6);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campuses');
        Schema::dropIfExists('institutions');
    }
};
