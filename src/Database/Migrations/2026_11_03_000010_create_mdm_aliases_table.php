<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_aliases', function (Blueprint $table): void {
            $table->id();
            $table->string('scope_type', 16);
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('term');
            $table->string('normalized_term');
            $table->string('target');
            $table->string('locale', 16)->nullable();
            $table->unsignedBigInteger('source_system_id')->nullable();
            $table->timestamps();

            $table->index(['scope_type', 'scope_id', 'normalized_term'], 'mdm_aliases_scope_term_index');
            $table->index('normalized_term', 'mdm_aliases_term_index');
            $table->index('source_system_id', 'mdm_aliases_source_system_index');
            $table->unique(['scope_type', 'scope_id', 'normalized_term', 'source_system_id'], 'mdm_aliases_scope_term_source_unique');

            $table->foreign('source_system_id', 'mdm_aliases_source_system_foreign')
                ->references('id')
                ->on('mdm_source_systems')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_aliases');
    }
};
