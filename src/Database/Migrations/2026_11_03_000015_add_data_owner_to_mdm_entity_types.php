<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mdm_entity_types', function (Blueprint $table): void {
            $table->unsignedInteger('data_owner_id')->nullable()->after('approval_required');

            $table->foreign('data_owner_id', 'mdm_entity_types_data_owner_foreign')
                ->references('id')
                ->on('admins')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mdm_entity_types', function (Blueprint $table): void {
            $table->dropForeign('mdm_entity_types_data_owner_foreign');

            $table->dropColumn('data_owner_id');
        });
    }
};
