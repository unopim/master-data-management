<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_code_sequences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('entity_type_id')->unique('mdm_cs_type_unique');
            $table->unsignedBigInteger('last_value')->default(0);

            $table->foreign('entity_type_id', 'mdm_cs_type_foreign')
                ->references('id')
                ->on('mdm_entity_types')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_code_sequences');
    }
};
