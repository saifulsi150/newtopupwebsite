<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE orders SET status = 'complete' WHERE status = 'completed'");
        DB::statement("UPDATE orders SET status = 'running' WHERE status = 'processing'");
        DB::statement("UPDATE orders SET status = 'looking' WHERE status = 'auto-processing'");
        DB::statement("UPDATE orders SET status = 'pending' WHERE status IS NULL OR TRIM(status) = ''");

        DB::statement("
            ALTER TABLE orders
            MODIFY status ENUM('pending','complete','cancel','looking','running')
            NOT NULL DEFAULT 'pending'
        ");
    }

    public function down(): void
    {
        DB::statement("UPDATE orders SET status = 'completed' WHERE status = 'complete'");
        DB::statement("UPDATE orders SET status = 'processing' WHERE status = 'running'");
        DB::statement("UPDATE orders SET status = 'auto-processing' WHERE status = 'looking'");
        DB::statement("UPDATE orders SET status = 'processing' WHERE status IS NULL OR TRIM(status) = ''");

        DB::statement("
            ALTER TABLE orders
            MODIFY status ENUM('completed','pending','processing','auto-processing','cancel')
            NOT NULL DEFAULT 'processing'
        ");
    }
};
