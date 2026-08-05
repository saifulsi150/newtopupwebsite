<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->addWebsiteColumns('users');
        $this->addWebsiteColumns('products');
        $this->addWebsiteColumns('orders');
        $this->addWebsiteColumns('transactions');
    }

    public function down(): void
    {
        $this->dropWebsiteColumns('users');
        $this->dropWebsiteColumns('products');
        $this->dropWebsiteColumns('orders');
        $this->dropWebsiteColumns('transactions');
    }

    private function addWebsiteColumns(string $table): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table): void {
            if (!Schema::hasColumn($table, 'website_name')) {
                $blueprint->string('website_name')->nullable()->index();
            }

            if (!Schema::hasColumn($table, 'source_site_url')) {
                $blueprint->string('source_site_url')->nullable()->index();
            }
        });
    }

    private function dropWebsiteColumns(string $table): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table): void {
            if (Schema::hasColumn($table, 'website_name')) {
                $blueprint->dropColumn('website_name');
            }

            if (Schema::hasColumn($table, 'source_site_url')) {
                $blueprint->dropColumn('source_site_url');
            }
        });
    }
};
