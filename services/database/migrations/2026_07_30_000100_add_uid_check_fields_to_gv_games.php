<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('gv_games')) {
            return;
        }

        Schema::table('gv_games', function (Blueprint $table) {
            if (!Schema::hasColumn('gv_games', 'uid_check_enabled')) {
                $table->boolean('uid_check_enabled')->default(false)->after('category');
            }

            if (!Schema::hasColumn('gv_games', 'uid_check_api_url')) {
                $table->string('uid_check_api_url', 1000)->nullable()->after('uid_check_enabled');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('gv_games')) {
            return;
        }

        Schema::table('gv_games', function (Blueprint $table) {
            if (Schema::hasColumn('gv_games', 'uid_check_api_url')) {
                $table->dropColumn('uid_check_api_url');
            }

            if (Schema::hasColumn('gv_games', 'uid_check_enabled')) {
                $table->dropColumn('uid_check_enabled');
            }
        });
    }
};
