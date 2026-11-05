<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_icons', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('path');
            $table->timestamps();
        });

        Schema::table('mdm_entity_types', function (Blueprint $table): void {
            $table->foreignId('icon_id')->nullable()->after('icon')->constrained('mdm_icons')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mdm_entity_types', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('icon_id');
        });

        Schema::dropIfExists('mdm_icons');
    }
};
