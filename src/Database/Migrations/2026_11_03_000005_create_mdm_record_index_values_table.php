<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_record_index_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('record_id');
            $table->unsignedBigInteger('entity_type_id');
            $table->string('field_code', 64);
            $table->string('locale', 16)->nullable();
            $table->string('value_normalized', 255)->nullable();
            $table->decimal('value_numeric', 20, 6)->nullable();
            $table->date('value_date')->nullable();

            $table->index(['entity_type_id', 'field_code', 'value_normalized'], 'mdm_index_values_text_index');
            $table->index(['entity_type_id', 'field_code', 'value_numeric'], 'mdm_index_values_numeric_index');
            $table->index(['entity_type_id', 'field_code', 'value_date'], 'mdm_index_values_date_index');
            $table->index('record_id', 'mdm_index_values_record_index');

            $table->foreign('record_id', 'mdm_index_values_record_foreign')
                ->references('id')
                ->on('mdm_records')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_record_index_values');
    }
};
