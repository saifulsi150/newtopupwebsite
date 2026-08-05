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
        Schema::table('orders', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('orders', 'account_info_to')) {
                $columns[] = 'account_info_to';
            }

            if (Schema::hasColumn('orders', 'account_info_original')) {
                $columns[] = 'account_info_original';
            }

            if (Schema::hasColumn('orders', 'order_id_to')) {
                $columns[] = 'order_id_to';
            }

            if (Schema::hasColumn('orders', 'external_order_id')) {
                $columns[] = 'external_order_id';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'account_info_to')) {
                $table->json('account_info_to')->nullable()->after('account_info');
            }

            if (!Schema::hasColumn('orders', 'account_info_original')) {
                $table->json('account_info_original')->nullable()->after('account_info_to');
            }

            if (!Schema::hasColumn('orders', 'order_id_to')) {
                $table->string('order_id_to')->nullable()->after('account_info_original');
            }

            if (!Schema::hasColumn('orders', 'external_order_id')) {
                $table->string('external_order_id')->nullable()->after('order_id_to');
            }
        });
    }
};
