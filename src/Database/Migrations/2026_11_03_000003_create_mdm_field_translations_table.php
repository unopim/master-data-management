<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_field_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('field_id');
            $table->string('locale', 16);
            $table->string('label')->nullable();
            $table->text('help')->nullable();

            $table->unique(['field_id', 'locale'], 'mdm_ft_field_locale_unique');

            $table->foreign('field_id', 'mdm_ft_field_foreign')
                ->references('id')
                ->on('mdm_fields')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_field_translations');
    }
};
