<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->addSyncUid('products');
        $this->addSyncUid('variations');
        $this->addSyncUid('product_packages');
    }

    public function down(): void
    {
        $this->dropSyncUid('products');
        $this->dropSyncUid('variations');
        $this->dropSyncUid('product_packages');
    }

    private function addSyncUid(string $table): void
    {
        if (!Schema::hasTable($table) || Schema::hasColumn($table, 'sync_uid')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table): void {
            $blueprint->uuid('sync_uid')->nullable()->unique()->after('id');
        });
    }

    private function dropSyncUid(string $table): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'sync_uid')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table): void {
            $blueprint->dropColumn('sync_uid');
        });
    }
};