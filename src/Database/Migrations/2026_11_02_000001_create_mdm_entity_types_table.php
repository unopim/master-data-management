<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_entity_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('icon', 64)->nullable();
            $table->string('label_field_code', 64)->nullable();
            $table->boolean('is_hierarchical')->default(false);
            $table->json('allowed_parent_type_ids')->nullable();
            $table->boolean('approval_required')->default(false);
            $table->string('mastering_style', 32)->default('consolidation');
            $table->string('code_strategy', 32)->default('pattern');
            $table->string('code_pattern', 128)->default('{TYPE}-{seq:6}');
            $table->unsignedInteger('schema_version')->default(0);
            $table->string('status', 32)->default('draft');
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('mastering_style');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_entity_types');
    }
};
