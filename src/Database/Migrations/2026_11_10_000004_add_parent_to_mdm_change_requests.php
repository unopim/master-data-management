<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The parent a held create proposes, so the approvals inbox can scope requests that have no record yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mdm_change_requests', function (Blueprint $table): void {
            $table->unsignedBigInteger('parent_id')->nullable()->after('record_id');

            $table->foreign('parent_id', 'mdm_change_requests_parent_foreign')
                ->references('id')
                ->on('mdm_records')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mdm_change_requests', function (Blueprint $table): void {
            $table->dropForeign('mdm_change_requests_parent_foreign');
            $table->dropColumn('parent_id');
        });
    }
};
