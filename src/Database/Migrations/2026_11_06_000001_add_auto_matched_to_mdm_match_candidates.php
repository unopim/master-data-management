<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mdm_match_candidates', function (Blueprint $table): void {
            $table->boolean('auto_matched')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('mdm_match_candidates', function (Blueprint $table): void {
            $table->dropColumn('auto_matched');
        });
    }
};
