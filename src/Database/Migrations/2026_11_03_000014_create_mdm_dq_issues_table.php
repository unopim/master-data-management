<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_dq_issues', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('entity_type_id');
            $table->unsignedBigInteger('record_id');
            $table->unsignedBigInteger('related_record_id')->nullable();
            $table->string('rule', 64);
            $table->string('rule_key', 191);
            $table->string('severity', 16);
            $table->string('status', 16)->default('open');
            $table->json('details')->nullable();
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedInteger('resolved_by')->nullable();
            $table->string('resolution', 32)->nullable();
            $table->timestamps();

            $table->unique(['record_id', 'rule', 'rule_key'], 'mdm_dq_issues_record_rule_key_unique');
            $table->index(['entity_type_id', 'status'], 'mdm_dq_issues_type_status_index');
            $table->index('related_record_id', 'mdm_dq_issues_related_record_index');

            $table->foreign('entity_type_id', 'mdm_dq_issues_type_foreign')
                ->references('id')
                ->on('mdm_entity_types')
                ->cascadeOnDelete();

            $table->foreign('record_id', 'mdm_dq_issues_record_foreign')
                ->references('id')
                ->on('mdm_records')
                ->cascadeOnDelete();

            $table->foreign('related_record_id', 'mdm_dq_issues_related_record_foreign')
                ->references('id')
                ->on('mdm_records')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_dq_issues');
    }
};
