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
     * 10 akun muthowif dummy premium style
     * password: password
     */
    public function run(): void
    {
        $faker = fake('id_ID');

        $parentProfileId = null;

        $profiles = [
            [
                'name' => 'Muhammad Al Faris',
                'photo' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e',
            ],
            [
                'name' => 'Abdullah Kareem',
                'photo' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d',
            ],
            [
                'name' => 'Ahmad Zubair',
                'photo' => 'https://images.unsplash.com/photo-1504593811423-6dd665756598',
            ],
            [
                'name' => 'Faisal Rahman',
                'photo' => 'https://images.unsplash.com/photo-1492562080023-ab3db95bfbce',
            ],
            [
                'name' => 'Imran Yusuf',
                'photo' => 'https://images.unsplash.com/photo-1504257432389-52343af06ae3',
            ],
            [
                'name' => 'Khalid Salman',
                'photo' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7',
            ],
            [
                'name' => 'Tariq Hamzah',
                'photo' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d',
            ],
            [
                'name' => 'Bilal Rizki',
                'photo' => 'https://images.unsplash.com/photo-1546961329-78bef0414d7c',
            ],
            [
                'name' => 'Harun Syafiq',
                'photo' => 'https://images.unsplash.com/photo-1504257432389-52343af06ae3',
            ],
            [
                'name' => 'Saad Maulana',
                'photo' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e',
            ],
        ];

        foreach ($profiles as $index => $item) {

            $i = $index + 1;

            $name = $item['name'];

            $email = strtolower(str_replace(' ', '.', $name)) . '@gmail.com';

            $phone = $faker->unique()->numerify('0899########');

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'remember_token' => Str::random(10),
                    'role' => UserRole::Muthowif,
                    'phone' => $phone,
                    'address' => $faker->streetAddress(),
                    'customer_type' => null,
                    'ppui_number' => null,
                    'country' => 'ID',
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                    'locale' => 'id',
                ],
            );

            $profile = MuthowifProfile::query()->updateOrCreate(
                ['user_id' => $user->getKey()],
                [
                    'phone' => $phone,
                    'address' => $faker->address(),
                    'nik' => sprintf('32010101%08d', $i),

                    'birth_date' => $faker
                        ->dateTimeBetween('-55 years', '-25 years')
                        ->format('Y-m-d'),

                    'passport_number' => sprintf('A%08d', 100000 + $i),

                    'languages' => [
                        'Indonesia',
                        'Arab',
                        'English',
                    ],

                    'educations' => [
                        'Universitas Islam Madinah',
                    ],

                    'work_experiences' => [
                        $faker->randomElement([
                            '7 tahun membimbing jamaah umrah & haji.',
                            'Muthowif berpengalaman untuk private dan grup.',
                            'Berpengalaman mendampingi jamaah lansia.',
                            'Spesialis city tour Makkah & Madinah.',
                            'Aktif membimbing jamaah Indonesia sejak 2018.',
                        ]),
                    ],

                    'reference_text' => 'Muthowif profesional BaytGo',

                    'photo_path' => $item['photo'],

                    'ktp_image_path' => 'seed/dummy/muthowif-ktp.png',

                    'verification_status' => MuthowifVerificationStatus::Approved,

                    'verified_at' => now(),

                    'rejection_reason' => null,

                    'wallet_balance' => $faker->numberBetween(100, 1000),
                ],
            );

            if ($i === 1) {
                $parentProfileId = (string) $profile->getKey();
            }

            app(MuthowifReferralCodeService::class)
                ->ensureAssigned($profile->fresh());

            if (
                $i >= 2 &&
                $i <= 6 &&
                $parentProfileId !== null &&
                (string) $profile->getKey() !== $parentProfileId
            ) {
                $profile->update([
                    'referred_by_muthowif_profile_id' => $parentProfileId,
                ]);
            }

            [$groupService, $privateService]
                = MuthowifService::ensurePairForProfile($profile);

            $groupService->update([
                'daily_price' => $faker->randomElement([1, 2]),
                'min_pilgrims' => 5,
                'max_pilgrims' => 50,
                'name' => 'Grup',
                'description' => 'Pendampingan grup jamaah.',
                'same_hotel_price_per_day' => 1,
                'transport_price_flat' => 1,
            ]);

            $privateService->update([
                'daily_price' => $faker->randomElement([1, 2]),
                'min_pilgrims' => 1,
                'max_pilgrims' => 5,
                'name' => 'Private',
                'description' => 'Pendampingan private eksklusif.',
                'same_hotel_price_per_day' => 1,
                'transport_price_flat' => 1,
            ]);

            $privateService->addOns()->delete();

            $addons = [
                [
                    'name' => 'Fotografi & Dokumentasi',
                    'price' => 1,
                ],
                [
                    'name' => 'Driver Lokal',
                    'price' => 2,
                ],
                [
                    'name' => 'Pendampingan Lansia',
                    'price' => 1,
                ],
                [
                    'name' => 'Bahasa Inggris',
                    'price' => 1,
                ],
                [
                    'name' => 'Bahasa Arab',
                    'price' => 1,
                ],
                [
                    'name' => 'Kajian & Tausiyah',
                    'price' => 2,
                ],
                [
                    'name' => 'City Tour Madinah',
                    'price' => 1,
                ],
                [
                    'name' => 'Ziarah Taif',
                    'price' => 2,
                ],
                [
                    'name' => 'Handling Jamaah VIP',
                    'price' => 2,
                ],
                [
                    'name' => 'Pendamping Kursi Roda',
                    'price' => 1,
                ],
                [
                    'name' => 'Fast Response 24 Jam',
                    'price' => 1,
                ],
                [
                    'name' => 'Pembuatan Itinerary',
                    'price' => 1,
                ],
            ];

            foreach ($faker->randomElements($addons, rand(3, 5)) as $addon) {

                $privateService->addOns()->create([
                    'name' => $addon['name'],
                    'price' => $addon['price'],
                ]);
            }
        }
    }
}