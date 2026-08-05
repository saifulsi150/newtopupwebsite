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
        // Fix account_info_to column type from VARCHAR to JSON
        if (Schema::hasColumn('orders', 'account_info_to')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->json('account_info_to')->nullable()->change();
            });
        }

        // Fix account_info_original column type from VARCHAR to JSON
        if (Schema::hasColumn('orders', 'account_info_original')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->json('account_info_original')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'account_info_to')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('account_info_to')->nullable()->change();
            });
        }

        if (Schema::hasColumn('orders', 'account_info_original')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('account_info_original')->nullable()->change();
            });
        }
    }
};
