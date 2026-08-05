<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('auto_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('package_name');
            $table->string('package_tagline')->index();
            $table->unsignedInteger('provider_package_id')->default(1);
            $table->string('provider')->default('vnbazer');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_packages');
    }
};
