<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mdm_entity_types', function (Blueprint $table): void {
            $table->timestamp('quality_scanned_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('mdm_entity_types', function (Blueprint $table): void {
            $table->dropColumn('quality_scanned_at');
        });
    }
};
