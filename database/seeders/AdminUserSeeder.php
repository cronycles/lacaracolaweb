<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email    = env('ADMIN_EMAIL', 'admin@lacaracola.it');
        $password = env('ADMIN_PASSWORD');

        if (! $password) {
            $this->command->error('ADMIN_PASSWORD non impostata in .env — seeder annullato.');
            return;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name'     => 'Admin',
                'password' => Hash::make($password),
            ]
        );

        $this->command->info("Utente admin: {$user->email}");
    }
}
