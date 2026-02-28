<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command->warn('ADMIN_EMAIL and ADMIN_PASSWORD must be set in .env — skipping admin seeder.');

            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            ['name' => 'Admin', 'password' => $password],
        );

        $this->command->info("Admin user seeded: {$email}");
    }
}
