<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mdm_access_grants', function (Blueprint $table): void {
            $table->unsignedBigInteger('merge_id')->nullable()->after('actions');

            $table->foreign('merge_id', 'mdm_access_grants_merge_foreign')
                ->references('id')
                ->on('mdm_record_merges')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mdm_access_grants', function (Blueprint $table): void {
            $table->dropForeign('mdm_access_grants_merge_foreign');
            $table->dropColumn('merge_id');
        });
    }
};
