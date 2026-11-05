<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mdm_entity_types', function (Blueprint $table): void {
            $table->boolean('is_scoped')->default(false)->after('is_hierarchical');
        });
    }

    public function down(): void
    {
        Schema::table('mdm_entity_types', function (Blueprint $table): void {
            $table->dropColumn('is_scoped');
        });
    }
};
