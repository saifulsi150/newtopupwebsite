<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('auto_topup_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('auto_package_id')->nullable()->constrained('auto_packages')->nullOnDelete();
            $table->string('provider')->default('vnbazer');
            $table->string('endpoint')->nullable();
            $table->string('forward_status')->default('pending')->index();
            $table->string('remote_status')->nullable();
            $table->string('remote_order_id')->nullable();
            $table->timestamp('forwarded_at')->nullable();
            $table->timestamp('callback_received_at')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('callback_payload')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['provider', 'remote_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_topup_orders');
    }
};
