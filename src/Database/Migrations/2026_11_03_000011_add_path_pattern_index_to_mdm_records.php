<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PostgreSQL serves a `LIKE 'prefix%'` scan from a btree index only with a pattern operator class under a
 * non-C collation; the MySQL family already uses mdm_records_path_index for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $grammar = DB::getQueryGrammar();

        DB::statement(sprintf(
            'CREATE INDEX %s ON %s (%s varchar_pattern_ops)',
            $grammar->wrap(DB::getTablePrefix().'mdm_records_path_pattern_index'),
            $grammar->wrapTable('mdm_records'),
            $grammar->wrap('path'),
        ));
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(sprintf('DROP INDEX IF EXISTS %s', DB::getQueryGrammar()->wrap(DB::getTablePrefix().'mdm_records_path_pattern_index')));
    }
};
