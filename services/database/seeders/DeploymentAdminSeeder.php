<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DeploymentAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', '');
        $password = (string) env('ADMIN_PASSWORD', '');

        if ($email === '' || $password === '') {
            $this->command?->warn('ADMIN_EMAIL or ADMIN_PASSWORD is empty. Skipping admin bootstrap.');
            return;
        }

        $payload = [
            'name' => (string) env('ADMIN_NAME', 'Site Admin'),
            'password' => Hash::make($password),
        ];

        if (Schema::hasColumn('users', 'user_type')) {
            $payload['user_type'] = 'admin';
        }

        if (Schema::hasColumn('users', 'status')) {
            $payload['status'] = 1;
        }

        if (Schema::hasColumn('users', 'role_id')) {
            $payload['role_id'] = 1;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            $payload,
        );

        $this->command?->info("Deployment admin ready: {$email}");
    }
}