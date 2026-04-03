<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Akun admin (email format Gmail — ganti di .env dengan alamat Gmail Anda).
     */
    public function run(): void
    {
        $email = strtolower(trim((string) env('ADMIN_EMAIL', 'baytgo.admin@gmail.com')));

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrator BaytGo'),
                'password' => Hash::make((string) env('ADMIN_PASSWORD', 'password')),
                'role' => UserRole::Admin,
                'phone' => null,
                'address' => null,
                'customer_type' => null,
                'ppui_number' => null,
                'email_verified_at' => now(),
                'phone_verified_at' => null,
            ]
        );
    }
}
