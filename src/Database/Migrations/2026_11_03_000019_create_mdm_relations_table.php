<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_relations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('relation_type_id');
            $table->unsignedBigInteger('from_record_id');
            $table->unsignedBigInteger('to_record_id');
            $table->json('attributes')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->index(['from_record_id', 'relation_type_id'], 'mdm_relations_from_type_index');
            $table->index(['to_record_id', 'relation_type_id'], 'mdm_relations_to_type_index');
            $table->index('relation_type_id', 'mdm_relations_type_index');

            $table->foreign('relation_type_id', 'mdm_relations_type_foreign')
                ->references('id')
                ->on('mdm_relation_types')
                ->cascadeOnDelete();

            $table->foreign('from_record_id', 'mdm_relations_from_record_foreign')
                ->references('id')
                ->on('mdm_records')
                ->cascadeOnDelete();

            $table->foreign('to_record_id', 'mdm_relations_to_record_foreign')
                ->references('id')
                ->on('mdm_records')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_relations');
    }
};
