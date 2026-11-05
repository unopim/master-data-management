<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_code_list_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('code_list_id');
            $table->unsignedInteger('version');
            $table->string('status', 16)->default('draft');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->unsignedInteger('published_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['code_list_id', 'version'], 'mdm_clv_list_version_unique');
            $table->index(['code_list_id', 'status', 'effective_from'], 'mdm_clv_list_status_from_index');

            $table->foreign('code_list_id', 'mdm_clv_list_foreign')
                ->references('id')
                ->on('mdm_code_lists')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_code_list_versions');
    }
};
