<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('entity_type_id');
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('operation', 16);
            $table->string('status', 16)->default('pending');
            $table->unsignedInteger('base_revision')->nullable();
            $table->json('before')->nullable();
            $table->json('after');
            $table->json('input');
            $table->string('origin', 32)->nullable();
            $table->unsignedInteger('requested_by')->nullable();
            $table->unsignedInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['status', 'entity_type_id'], 'mdm_change_requests_status_type_index');
            $table->index(['record_id', 'status'], 'mdm_change_requests_record_status_index');

            $table->foreign('entity_type_id', 'mdm_change_requests_type_foreign')
                ->references('id')
                ->on('mdm_entity_types')
                ->cascadeOnDelete();

            $table->foreign('record_id', 'mdm_change_requests_record_foreign')
                ->references('id')
                ->on('mdm_records')
                ->cascadeOnDelete();

            $table->foreign('requested_by', 'mdm_change_requests_requester_foreign')
                ->references('id')
                ->on('admins')
                ->nullOnDelete();

            $table->foreign('decided_by', 'mdm_change_requests_decider_foreign')
                ->references('id')
                ->on('admins')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_change_requests');
    }
};
