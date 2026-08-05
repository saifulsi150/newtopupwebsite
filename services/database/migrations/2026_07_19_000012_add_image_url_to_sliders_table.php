<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('sliders')) {
            return;
        }

        Schema::table('sliders', function (Blueprint $table): void {
            if (!Schema::hasColumn('sliders', 'image_url')) {
                $table->string('image_url')->nullable()->after('url');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sliders')) {
            return;
        }

        if (Schema::hasColumn('sliders', 'image_url')) {
            Schema::table('sliders', function (Blueprint $table): void {
                $table->dropColumn('image_url');
            });
        }
    }
};
