<?php

namespace Database\Seeders;

use App\Enums\MuthowifVerificationStatus;
use App\Enums\UserRole;
use App\Models\MuthowifProfile;
use App\Models\MuthowifService;
use App\Models\User;
use App\Services\MuthowifReferralCodeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MuthowifDummySeeder extends Seeder
{
    /**
     * 20 akun muthowif dummy (email dummy.muthowif.01 … 20 @baytgo.test, password: password).
     */
    public function run(): void
    {
        $faker = fake('id_ID');

        $parentProfileId = null;

        for ($i = 1; $i <= 20; $i++) {
            $suffix = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $email = "dummy.muthowif.{$suffix}@baytgo.test";
            $phone = sprintf('0899%08d', 1_000_000 + $i);

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $faker->name(),
                    'password' => Hash::make('password'),
                    'remember_token' => Str::random(10),
                    'role' => UserRole::Muthowif,
                    'phone' => $phone,
                    'address' => $faker->streetAddress(),
                    'customer_type' => null,
                    'ppui_number' => null,
                    'country' => 'ID',
                    'email_verified_at' => now(),
                    'phone_verified_at' => null,
                    'locale' => 'id',
                ],
            );

            $profile = MuthowifProfile::query()->updateOrCreate(
                ['user_id' => $user->getKey()],
                [
                    'phone' => $phone,
                    'address' => $faker->address(),
                    'nik' => sprintf('32010101%08d', $i),
                    'birth_date' => $faker->dateTimeBetween('-55 years', '-25 years')->format('Y-m-d'),
                    'passport_number' => sprintf('A%s%06d', $suffix, 100000 + $i),
                    'languages' => ['Indonesia', 'Arab'],
                    'educations' => ['Formal: '.$faker->randomElement(['STAI', 'UIN', 'Pesantren'])],
                    'work_experiences' => ['Pembimbing umrah & haji (dummy seed)'],
                    'reference_text' => 'Akun dummy untuk pengujian — '.$email,
                    'photo_path' => 'seed/dummy/muthowif-photo.png',
                    'ktp_image_path' => 'seed/dummy/muthowif-ktp.png',
                    'verification_status' => MuthowifVerificationStatus::Approved,
                    'verified_at' => now(),
                    'rejection_reason' => null,
                    'wallet_balance' => 0,
                ],
            );

            if ($i === 1) {
                $parentProfileId = (string) $profile->getKey();
            }

            app(MuthowifReferralCodeService::class)->ensureAssigned($profile->fresh());

            if ($i >= 2 && $i <= 6 && $parentProfileId !== null && (string) $profile->getKey() !== $parentProfileId) {
                $profile->update(['referred_by_muthowif_profile_id' => $parentProfileId]);
            }

            // Tanpa baris muthowif_services, profil tidak muncul di dropdown rekomendasi / marketplace card harga.
            [$groupService, $privateService] = MuthowifService::ensurePairForProfile($profile);
            $groupService->update([
                'daily_price' => 400_000,
                'min_pilgrims' => 1,
                'max_pilgrims' => 50,
            ]);
            $privateService->update([
                'daily_price' => 850_000,
                'min_pilgrims' => 1,
                'max_pilgrims' => 50,
            ]);
        }
    }
}
