<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('direction', 20);
            $table->string('entity', 50)->nullable();
            $table->string('record_key', 191)->nullable();
            $table->string('source_site_url', 255)->nullable();
            $table->string('target_site_url', 255)->nullable();
            $table->string('status', 20)->default('failed');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['direction', 'status']);
            $table->index(['entity', 'record_key']);
            $table->index(['source_site_url']);
            $table->index(['target_site_url']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
