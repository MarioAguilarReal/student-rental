<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Properties module: properties, their unit tree and catalog links (E04).
     */
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('management_mode', 20);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('property_type', 30);
            $table->string('street')->nullable();
            $table->string('exterior_number', 20)->nullable();
            $table->string('interior_number', 20)->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('country', 2)->default('MX');
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->string('status', 30)->index();
            $table->string('pause_reason', 30)->nullable();
            $table->date('inspection_valid_until')->nullable();
            $table->string('gender_policy', 20)->default('mixed');
            $table->jsonb('allowed_communities')->default('[]');
            $table->timestamp('submitted_for_inspection_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'status']);
            $table->index(['latitude', 'longitude']);
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('units')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('capacity');
            $table->boolean('is_rentable')->default(true);
            $table->string('gender_policy', 20)->nullable();
            $table->jsonb('allowed_communities')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('amenity_property', function (Blueprint $table) {
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->primary(['property_id', 'amenity_id']);
        });

        Schema::create('amenity_unit', function (Blueprint $table) {
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->primary(['unit_id', 'amenity_id']);
        });

        Schema::create('house_rule_property', function (Blueprint $table) {
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('house_rule_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_allowed');
            $table->string('note')->nullable();
            $table->primary(['property_id', 'house_rule_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_rule_property');
        Schema::dropIfExists('amenity_unit');
        Schema::dropIfExists('amenity_property');
        Schema::dropIfExists('units');
        Schema::dropIfExists('properties');
    }
};
