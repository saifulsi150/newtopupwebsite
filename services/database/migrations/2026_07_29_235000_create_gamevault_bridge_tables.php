<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gv_games', function (Blueprint $table) {
            $table->string('id', 80)->primary();
            $table->string('name', 120);
            $table->string('logo', 500)->nullable();
            $table->string('banner', 500)->nullable();
            $table->string('placeholder', 150)->nullable();
            $table->string('category', 40)->default('uid-topup');
            $table->timestamps();
        });

        Schema::create('gv_packages', function (Blueprint $table) {
            $table->string('id', 80)->primary();
            $table->string('game_id', 80);
            $table->string('name', 120);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('original_price', 12, 2)->nullable();
            $table->string('badge', 40)->nullable();
            $table->timestamps();
            $table->foreign('game_id')->references('id')->on('gv_games')->cascadeOnDelete();
        });

        Schema::create('gv_site_settings', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->text('notice_banner')->nullable();
            $table->text('marquee_text')->nullable();
            $table->string('bkash_number', 30)->nullable();
            $table->string('nagad_number', 30)->nullable();
            $table->string('rocket_number', 30)->nullable();
            $table->string('whatsapp_number', 30)->nullable();
            $table->string('tutorial_link', 255)->nullable();
            $table->string('telegram_link', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('gv_banners', function (Blueprint $table) {
            $table->string('id', 80)->primary();
            $table->string('title', 120)->nullable();
            $table->string('subtitle', 180)->nullable();
            $table->string('url', 500)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('gv_vouchers', function (Blueprint $table) {
            $table->string('id', 80)->primary();
            $table->string('code', 80)->unique();
            $table->decimal('discount', 10, 2)->default(0);
            $table->integer('max_uses')->default(50);
            $table->integer('used')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('gv_users', function (Blueprint $table) {
            $table->string('uid', 150)->primary();
            $table->string('email', 150)->unique();
            $table->string('name', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->decimal('wallet_balance', 12, 2)->default(0);
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        Schema::create('gv_transactions', function (Blueprint $table) {
            $table->string('id', 120)->primary();
            $table->string('user_id', 150);
            $table->decimal('amount', 12, 2);
            $table->string('type', 20);
            $table->string('method', 20)->default('Wallet');
            $table->string('status', 20)->default('pending');
            $table->string('trx_id', 120)->nullable();
            $table->string('game_id', 120)->nullable();
            $table->string('package_name', 150)->nullable();
            $table->string('player_id', 150)->nullable();
            $table->string('timestamp', 120)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('gv_comments', function (Blueprint $table) {
            $table->string('id', 80)->primary();
            $table->string('username', 120);
            $table->string('email', 150)->nullable();
            $table->string('product', 150)->nullable();
            $table->text('text');
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('status', 20)->default('pending');
            $table->string('timestamp', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('gv_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 120);
            $table->text('message');
            $table->string('timestamp', 120)->nullable();
            $table->timestamps();
        });

        Schema::table('gv_transactions', function (Blueprint $table) {
            $table->foreign('user_id')->references('uid')->on('gv_users')->cascadeOnDelete();
        });

        DB::table('gv_site_settings')->insert([
            'id' => 'global',
            'notice_banner' => 'Welcome to Gaming Topup Hub',
            'marquee_text' => 'Instant game topup and voucher delivery',
            'whatsapp_number' => '8801756515340',
            'tutorial_link' => 'https://youtu.be/zD1F3e_jNMo',
            'telegram_link' => 'https://t.me/admimapp',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('gv_notifications');
        Schema::dropIfExists('gv_comments');
        Schema::dropIfExists('gv_transactions');
        Schema::dropIfExists('gv_users');
        Schema::dropIfExists('gv_vouchers');
        Schema::dropIfExists('gv_banners');
        Schema::dropIfExists('gv_site_settings');
        Schema::dropIfExists('gv_packages');
        Schema::dropIfExists('gv_games');
    }
};
