<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mdm_records', function (Blueprint $table): void {
            $table->unsignedTinyInteger('completeness')->nullable()->after('dq_score');

            $table->index(['entity_type_id', 'completeness'], 'mdm_records_type_completeness_index');
        });
    }

    public function down(): void
    {
        Schema::table('mdm_records', function (Blueprint $table): void {
            $table->dropIndex('mdm_records_type_completeness_index');

            $table->dropColumn('completeness');
        });
    }
};
