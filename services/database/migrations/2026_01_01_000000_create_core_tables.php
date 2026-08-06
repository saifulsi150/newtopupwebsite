<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Base migration: creates all core tables that existed before the migration system.
// Safe to run on both fresh and existing databases — all blocks check hasTable/hasColumn.
return new class extends Migration
{
    public function up(): void
    {
        // Laravel built-ins
        if (!Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $t) {
                $t->string('key')->primary();
                $t->mediumText('value');
                $t->integer('expiration');
            });
        }
        if (!Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $t) {
                $t->string('key')->primary();
                $t->string('owner');
                $t->integer('expiration');
            });
        }
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $t) {
                $t->string('email')->primary();
                $t->string('token');
                $t->timestamp('created_at')->nullable();
            });
        }
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $t) {
                $t->string('id')->primary();
                $t->foreignId('user_id')->nullable()->index();
                $t->string('ip_address', 45)->nullable();
                $t->text('user_agent')->nullable();
                $t->longText('payload');
                $t->integer('last_activity')->index();
            });
        }

        // Core app tables
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $t) {
                $t->id();
                $t->string('title');
                $t->string('slot');
                $t->string('status');
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('sliders')) {
            Schema::create('sliders', function (Blueprint $t) {
                $t->id();
                $t->string('url')->nullable();
                $t->integer('order_column')->default(0);
                $t->boolean('status')->default(true);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $t) {
                $t->id();
                $t->string('group');
                $t->string('name');
                $t->boolean('locked')->default(false);
                $t->json('payload');
                $t->timestamps();
                $t->unique(['group', 'name']);
            });
        }

        if (!Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $t) {
                $t->id();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('email')->unique();
                $t->string('phone')->nullable();
                $t->string('password');
                $t->text('picture')->nullable();
                $t->string('user_type')->default('user');
                $t->string('balance')->default('0');
                $t->string('coins')->default('0');
                $t->string('total_order')->default('0');
                $t->string('total_spent')->default('0');
                $t->rememberToken();
                $t->boolean('is_reseller')->default(false);
                $t->boolean('status')->default(true);
                $t->string('referral_code', 8)->nullable()->unique();
                $t->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete();
                $t->integer('total_refer')->default(0);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $t) {
                $t->id();
                $t->text('title');
                $t->string('slug')->unique();
                $t->foreignId('categorie_id')->constrained('categories')->cascadeOnDelete()->cascadeOnUpdate();
                $t->text('content');
                $t->enum('type', ['INGAME', 'IDCODE', 'VOUCHER', 'SUBSCRIPTION']);
                $t->integer('percentage')->default(0)->nullable();
                $t->integer('uid_checker')->default(0)->nullable();
                $t->text('image');
                $t->integer('slot')->default(0);
                $t->string('input')->nullable();
                $t->boolean('status')->default(true);
                $t->string('external_id')->nullable()->unique();
                $t->string('source')->default('local');
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('variations')) {
            Schema::create('variations', function (Blueprint $t) {
                $t->id();
                $t->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $t->string('title');
                $t->decimal('price', 16, 2)->default(0);
                $t->decimal('gift_coins', 16, 2)->default(0);
                $t->integer('stock')->default(0);
                $t->tinyInteger('automatic')->default(0);
                $t->string('provider')->nullable();
                $t->string('provider_product_id')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
                $t->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $t->foreignId('variation_id')->nullable()->constrained('variations')->nullOnDelete();
                $t->decimal('amount', 16, 2)->default(0);
                $t->text('delivery_message')->nullable();
                $t->json('account_info')->nullable();
                $t->json('provider_data')->nullable();
                $t->string('voucher_code')->nullable();
                $t->string('track_id', 25)->nullable();
                $t->integer('quantity')->default(1);
                $t->tinyInteger('attempts')->default(0);
                $t->enum('status', ['completed', 'pending', 'processing', 'auto-processing', 'cancel'])->default('processing');
                $t->string('external_ref')->nullable()->index();
                $t->text('admin_note')->nullable();
                $t->string('source')->default('local');
                $t->softDeletes();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Drop in reverse FK order
        Schema::dropIfExists('orders');
        Schema::dropIfExists('variations');
        Schema::dropIfExists('products');
        Schema::dropIfExists('users');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('sliders');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
    }
};
