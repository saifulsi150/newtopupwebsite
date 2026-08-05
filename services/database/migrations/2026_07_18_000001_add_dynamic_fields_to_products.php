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
            // Store dynamic field configurations as JSON
            // Format: [{"label": "Enter your UID", "key": "player_id"}, ...]
            $table->json('dynamic_fields')->nullable()->after('input');
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
