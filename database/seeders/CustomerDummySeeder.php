<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerDummySeeder extends Seeder
{
    /**
     * 10 akun customer dummy (email dummy.customer.01 … 10 @baytgo.test, password: password).
     */
    public function run(): void
    {
        $faker = fake('id_ID');

        for ($i = 1; $i <= 10; $i++) {
            $suffix = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $email = "dummy.customer.{$suffix}@baytgo.test";
            $phone = sprintf('0812%08d', 1_000_000 + $i);

            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => 'Jamaah ' . $faker->firstName(),
                    'password' => Hash::make('password'),
                    'remember_token' => Str::random(10),
                    'role' => UserRole::Customer,
                    'phone' => $phone,
                    'address' => $faker->streetAddress(),
                    'customer_type' => $faker->randomElement([\App\Enums\CustomerType::Personal, \App\Enums\CustomerType::Company]),
                    'ppui_number' => null,
                    'country' => 'ID',
                    'email_verified_at' => now(),
                    'phone_verified_at' => null,
                    'locale' => 'id',
                ],
            );
        }
    }
}
