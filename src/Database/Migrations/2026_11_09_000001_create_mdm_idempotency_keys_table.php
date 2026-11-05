<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_idempotency_keys', function (Blueprint $table): void {
            $table->id();
            $table->string('api_client_id', 100);
            $table->string('key', 255);
            $table->char('request_hash', 64);
            $table->unsignedSmallInteger('status_code');
            $table->json('response')->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->unique(['api_client_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_idempotency_keys');
    }
};
