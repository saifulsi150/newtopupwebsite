<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->addSyncColumns('users');
        $this->addSyncColumns('products');
        $this->addSyncColumns('orders');
        $this->addSyncColumns('transactions');
    }

    public function down(): void
    {
        $this->dropSyncColumns('users');
        $this->dropSyncColumns('products');
        $this->dropSyncColumns('orders');
        $this->dropSyncColumns('transactions');
    }

    private function addSyncColumns(string $table): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table): void {
            if (!Schema::hasColumn($table, 'sync_status')) {
                $blueprint->string('sync_status')->nullable()->index();
            }

            if (!Schema::hasColumn($table, 'synced_at')) {
                $blueprint->timestamp('synced_at')->nullable()->index();
            }
        });
    }

    private function dropSyncColumns(string $table): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table): void {
            if (Schema::hasColumn($table, 'sync_status')) {
                $blueprint->dropColumn('sync_status');
            }

            if (Schema::hasColumn($table, 'synced_at')) {
                $blueprint->dropColumn('synced_at');
            }
        });
    }
};
