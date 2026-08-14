<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@example.com');
        $password = (string) env('ADMIN_PASSWORD', 'admin123456');
        $name = (string) env('ADMIN_NAME', 'Site Admin');

        Admin::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_active' => true,
            ]
        );

        // Also ensure fallback ghostbazar admin exists
        Admin::query()->updateOrCreate(
            ['email' => 'admin@ghostbazar.online'],
            [
                'name' => 'GhostBazar Admin',
                'password' => Hash::make('Admin@12345'),
                'is_active' => true,
            ]
        );

        $this->command?->info("Filament Admin ready: {$email}");
    }
}
