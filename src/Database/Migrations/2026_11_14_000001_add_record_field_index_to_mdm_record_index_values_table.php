<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The record grid reads one field of a record (filter semi-joins, sort join) by record, field and locale; the new
 * index leads with record_id, so it also serves the foreign key and replaces the single-column index. It is added
 * before the old one is dropped because MySQL refuses to drop the only index backing a foreign key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mdm_record_index_values', function (Blueprint $table): void {
            $table->index(['record_id', 'field_code', 'locale'], 'mdm_index_values_record_field_index');
        });

        Schema::table('mdm_record_index_values', function (Blueprint $table): void {
            $table->dropIndex('mdm_index_values_record_index');
        });
    }

    public function down(): void
    {
        Schema::table('mdm_record_index_values', function (Blueprint $table): void {
            $table->index('record_id', 'mdm_index_values_record_index');
        });

        Schema::table('mdm_record_index_values', function (Blueprint $table): void {
            $table->dropIndex('mdm_index_values_record_field_index');
        });
    }
};
