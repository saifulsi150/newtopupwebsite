<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'dynamic_fields')) {
                // Store dynamic field configurations as JSON
                $col = $table->json('dynamic_fields')->nullable();
                if (Schema::hasColumn('products', 'input')) {
                    $col->after('input');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('dynamic_fields');
        });
    }
};
