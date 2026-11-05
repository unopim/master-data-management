<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mdm_entity_types', function (Blueprint $table): void {
            $table->unsignedInteger('sort_order')->default(0);

            $table->index(['sort_order', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('mdm_entity_types', function (Blueprint $table): void {
            $table->dropIndex(['sort_order', 'code']);
            $table->dropColumn('sort_order');
        });
    }
};
