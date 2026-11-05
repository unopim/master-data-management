<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mdm_change_requests', function (Blueprint $table): void {
            $table->json('context')->nullable()->after('origin');
        });
    }

    public function down(): void
    {
        Schema::table('mdm_change_requests', function (Blueprint $table): void {
            $table->dropColumn('context');
        });
    }
};
