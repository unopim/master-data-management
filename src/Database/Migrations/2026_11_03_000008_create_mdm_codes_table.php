<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_codes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('version_id');
            $table->string('code', 128);
            $table->string('parent_code', 128)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->json('attributes')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);

            $table->unique(['version_id', 'code'], 'mdm_codes_version_code_unique');
            $table->index(['version_id', 'is_active', 'sort'], 'mdm_codes_version_active_sort_index');

            $table->foreign('version_id', 'mdm_codes_version_foreign')
                ->references('id')
                ->on('mdm_code_list_versions')
                ->cascadeOnDelete();
        });

        Schema::create('mdm_code_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('code_id');
            $table->string('locale', 16);
            $table->string('label')->nullable();

            $table->unique(['code_id', 'locale'], 'mdm_ct_code_locale_unique');

            $table->foreign('code_id', 'mdm_ct_code_foreign')
                ->references('id')
                ->on('mdm_codes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_code_translations');
        Schema::dropIfExists('mdm_codes');
    }
};
