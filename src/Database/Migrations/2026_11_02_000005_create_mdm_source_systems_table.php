<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_source_systems', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('type', 32)->default('other');
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->string('contact_owner')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->boolean('external_id_is_personal')->default(false);
            $table->timestamps();

            $table->index('type', 'mdm_source_systems_type_index');
            $table->index('is_active', 'mdm_source_systems_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_source_systems');
    }
};
