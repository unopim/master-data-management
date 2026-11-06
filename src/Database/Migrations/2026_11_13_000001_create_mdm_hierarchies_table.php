<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `primary_entity_type_id` holds the entity type id only on the primary hierarchy, so its unique index allows at
 * most one primary per entity type on every engine without a partial index. PostgreSQL also gets a pattern
 * operator class index, since it serves a `LIKE 'prefix%'` subtree scan only from one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_hierarchies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('entity_type_id');
            $table->string('code', 64);
            $table->boolean('is_primary')->default(false);
            $table->unsignedBigInteger('primary_entity_type_id')->nullable();
            $table->timestamps();

            $table->unique(['entity_type_id', 'code'], 'mdm_hierarchies_type_code_unique');
            $table->unique('primary_entity_type_id', 'mdm_hierarchies_primary_unique');

            $table->foreign('entity_type_id', 'mdm_hierarchies_type_foreign')
                ->references('id')
                ->on('mdm_entity_types')
                ->cascadeOnDelete();
        });

        Schema::create('mdm_hierarchy_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('hierarchy_id');
            $table->string('locale', 16);
            $table->string('label')->nullable();

            $table->unique(['hierarchy_id', 'locale'], 'mdm_ht_hierarchy_locale_unique');

            $table->foreign('hierarchy_id', 'mdm_ht_hierarchy_foreign')
                ->references('id')
                ->on('mdm_hierarchies')
                ->cascadeOnDelete();
        });

        Schema::create('mdm_hierarchy_nodes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('hierarchy_id');
            $table->unsignedBigInteger('record_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('path')->nullable();
            $table->unsignedSmallInteger('depth')->default(0);
            $table->timestamps();

            $table->unique(['hierarchy_id', 'record_id'], 'mdm_hierarchy_nodes_record_unique');
            $table->index(['hierarchy_id', 'parent_id'], 'mdm_hierarchy_nodes_parent_index');
            $table->index(['hierarchy_id', 'path'], 'mdm_hierarchy_nodes_path_index');
            $table->index('record_id', 'mdm_hierarchy_nodes_record_index');

            $table->foreign('hierarchy_id', 'mdm_hierarchy_nodes_hierarchy_foreign')
                ->references('id')
                ->on('mdm_hierarchies')
                ->cascadeOnDelete();

            $table->foreign('record_id', 'mdm_hierarchy_nodes_record_foreign')
                ->references('id')
                ->on('mdm_records')
                ->cascadeOnDelete();
        });

        if (DB::getDriverName() === 'pgsql') {
            $grammar = DB::getQueryGrammar();

            DB::statement(sprintf(
                'CREATE INDEX %s ON %s (%s, %s varchar_pattern_ops)',
                $grammar->wrap(DB::getTablePrefix().'mdm_hierarchy_nodes_path_pattern_index'),
                $grammar->wrapTable('mdm_hierarchy_nodes'),
                $grammar->wrap('hierarchy_id'),
                $grammar->wrap('path'),
            ));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_hierarchy_nodes');
        Schema::dropIfExists('mdm_hierarchy_translations');
        Schema::dropIfExists('mdm_hierarchies');
    }
};
