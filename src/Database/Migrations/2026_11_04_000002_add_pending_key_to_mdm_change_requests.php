<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mdm_change_requests', function (Blueprint $table): void {
            $table->string('pending_key', 64)->nullable();

            $table->unique('pending_key', 'mdm_change_requests_pending_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('mdm_change_requests', function (Blueprint $table): void {
            $table->dropUnique('mdm_change_requests_pending_key_unique');

            $table->dropColumn('pending_key');
        });
    }
};
