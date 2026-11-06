<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rewrite the legacy single-choice `both` platform mode as the multi-select `pim,mdm`.
     */
    public function up(): void
    {
        DB::table('core_config')
            ->where('code', 'general.platform.settings.modules')
            ->where('value', 'both')
            ->update(['value' => 'pim,mdm']);
    }

    public function down(): void
    {
        DB::table('core_config')
            ->where('code', 'general.platform.settings.modules')
            ->where('value', 'pim,mdm')
            ->update(['value' => 'both']);
    }
};
