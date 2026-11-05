<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mdm_fields', function (Blueprint $table): void {
            $table->foreign('code_list_id', 'mdm_fields_code_list_foreign')
                ->references('id')
                ->on('mdm_code_lists')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mdm_fields', function (Blueprint $table): void {
            $table->dropForeign('mdm_fields_code_list_foreign');
        });
    }
};
