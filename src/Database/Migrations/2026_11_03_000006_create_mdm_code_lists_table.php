<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_code_lists', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique('mdm_cl_code_unique');
            $table->string('owner')->nullable();
            $table->boolean('is_versioned')->default(true);
            $table->timestamps();
        });

        Schema::create('mdm_code_list_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('code_list_id');
            $table->string('locale', 16);
            $table->string('name')->nullable();
            $table->text('description')->nullable();

            $table->unique(['code_list_id', 'locale'], 'mdm_clt_list_locale_unique');

            $table->foreign('code_list_id', 'mdm_clt_list_foreign')
                ->references('id')
                ->on('mdm_code_lists')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_code_list_translations');
        Schema::dropIfExists('mdm_code_lists');
    }
};
