<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_connections', function (Blueprint $table) {
            $table->id();
            $table->string('source_site_url');
            $table->string('target_site_url');
            $table->string('api_key');
            $table->string('secret_key');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('responded_by')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['source_site_url', 'target_site_url']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_connections');
    }
};