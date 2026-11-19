<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inspections module (E06, HU-03.03): versioned checklists, inspections and their answers.
     */
    public function up(): void
    {
        Schema::create('checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('version');
            $table->string('status', 20); // draft | published | retired
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique('version');
        });

        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_template_id')->constrained()->cascadeOnDelete();
            $table->string('category', 30);
            $table->string('text');
            $table->string('scope', 20); // property | unit
            $table->boolean('is_critical')->default(false);
            $table->boolean('requires_photo')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // initial | change | renewal
            $table->foreignId('checklist_template_id')->constrained()->restrictOnDelete();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('scheduled_for')->nullable();
            $table->string('status', 30)->index();
            $table->string('result', 30)->nullable();
            $table->text('observations')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->index(['inspector_id', 'status']);
        });

        Schema::create('inspection_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checklist_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('result', 20); // compliant | non_compliant | not_applicable
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        // One answer per item (and per unit for unit-scoped items). NULLS NOT DISTINCT: PostgreSQL 15+.
        DB::statement('CREATE UNIQUE INDEX inspection_answers_unique ON inspection_answers (inspection_id, checklist_item_id, unit_id) NULLS NOT DISTINCT');
        // At most one open inspection per property.
        DB::statement("CREATE UNIQUE INDEX inspections_one_open_per_property ON inspections (property_id) WHERE status IN ('pending_assignment', 'assigned', 'in_progress')");
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_answers');
        Schema::dropIfExists('inspections');
        Schema::dropIfExists('checklist_items');
        Schema::dropIfExists('checklist_templates');
    }
};
