<?php

namespace Database\Seeders;

use App\Enums\MuthowifVerificationStatus;
use App\Enums\UserRole;
use App\Models\MuthowifProfile;
use App\Models\MuthowifService;
use App\Models\User;
use App\Services\MuthowifReferralCodeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MuthowifDummySeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'password';

    /**
     * Data dibuat semi-realistis untuk kebutuhan marketplace/demo.
     */
    private array $dummyProfiles = [
        [
            'name' => 'Ahmad Fauzi',
            'city' => 'Bandung',
            'languages' => ['Indonesia', 'Arab'],
            'specialist' => 'Muthowif Jamaah Lansia',
            'experience' => 12,
            'education' => 'LIPIA Jakarta',
            'photo' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e',
        ],
        [
            'name' => 'Muhammad Rizki',
            'city' => 'Jakarta',
            'languages' => ['Indonesia', 'Arab', 'Inggris'],
            'specialist' => 'Tour Leader Umrah VIP',
            'experience' => 8,
            'education' => 'UIN Syarif Hidayatullah',
            'photo' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d',
        ],
        [
            'name' => 'Abdul Rahman',
            'city' => 'Bekasi',
            'languages' => ['Indonesia', 'Arab'],
            'specialist' => 'Pembimbing Ibadah Haji',
            'experience' => 15,
            'education' => 'Pesantren Gontor',
            'photo' => 'https://images.unsplash.com/photo-1504593811423-6dd665756598',
        ],
        [
            'name' => 'Hilman Syah',
            'city' => 'Depok',
            'languages' => ['Indonesia', 'Arab', 'Turki'],
            'specialist' => 'Guide City Tour Madinah',
            'experience' => 6,
            'education' => 'Universitas Al-Azhar Kairo',
            'photo' => 'https://images.unsplash.com/photo-1504257432389-52343af06ae3',
        ],
        [
            'name' => 'Zainal Arifin',
            'city' => 'Surabaya',
            'languages' => ['Indonesia', 'Arab'],
            'specialist' => 'Pendamping Jamaah Keluarga',
            'experience' => 9,
            'education' => 'UIN Sunan Ampel',
            'photo' => 'https://images.unsplash.com/photo-1492562080023-ab3db95bfbce',
        ],
        [
            'name' => 'Ridwan Hakim',
            'city' => 'Bogor',
            'languages' => ['Indonesia', 'Arab', 'Inggris'],
            'specialist' => 'Private Mutowwif',
            'experience' => 10,
            'education' => 'STAI Madinah',
            'photo' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d',
        ],
        [
            'name' => 'Fikri Maulana',
            'city' => 'Cirebon',
            'languages' => ['Indonesia', 'Arab'],
            'specialist' => 'Pembimbing Jamaah First Timer',
            'experience' => 7,
            'education' => 'Pesantren Sidogiri',
            'photo' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7',
        ],
        [
            'name' => 'Yusuf Hamdan',
            'city' => 'Yogyakarta',
            'languages' => ['Indonesia', 'Arab', 'Inggris'],
            'specialist' => 'Muthowif Premium',
            'experience' => 14,
            'education' => 'Universitas Islam Madinah',
            'photo' => 'https://images.unsplash.com/photo-1502767089025-6572583495b0',
        ],
        [
            'name' => 'Lukman Hakim',
            'city' => 'Semarang',
            'languages' => ['Indonesia', 'Arab'],
            'specialist' => 'Pembimbing Ziarah',
            'experience' => 5,
            'education' => 'UIN Walisongo',
            'photo' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e',
        ],
        [
            'name' => 'Imam Nawawi',
            'city' => 'Tasikmalaya',
            'languages' => ['Indonesia', 'Arab'],
            'specialist' => 'Pendamping Jamaah Lansia',
            'experience' => 11,
            'education' => 'Pesantren Darussalam',
            'photo' => 'https://images.unsplash.com/photo-1499996860823-5214fcc65f8f',
        ],
    ];

    public function run(): void
    {
        DB::transaction(function () {

            $parentProfileId = null;

            foreach ($this->dummyProfiles as $index => $data) {

                $number = $index + 1;

                $suffix = str_pad((string) $number, 2, '0', STR_PAD_LEFT);

                $email = 'muthowif.' . $suffix . '@gmail.com';

                $phone = '0899' . rand(10000000, 99999999);

                $user = User::query()->updateOrCreate(
                    [
                        'email' => $email,
                    ],
                    [
                        'name' => $data['name'],
                        'password' => Hash::make(self::DEFAULT_PASSWORD),
                        'remember_token' => Str::random(10),

                        'role' => UserRole::Muthowif,

                        'phone' => $phone,
                        'address' => $data['city'] . ', Indonesia',

                        'country' => 'ID',
                        'locale' => 'id',

                        'email_verified_at' => now(),
                        'phone_verified_at' => now(),
                    ]
                );

                $profile = MuthowifProfile::query()->updateOrCreate(
                    [
                        'user_id' => $user->getKey(),
                    ],
                    [
                        'phone' => $phone,

                        'address' =>
                            $data['city'] .
                            ', Indonesia',

                        'nik' => fake()->numerify('3276############'),

                        'birth_date' => now()
                            ->subYears(rand(28, 50))
                            ->format('Y-m-d'),

                        'passport_number' =>
                            strtoupper(Str::random(2)) .
                            rand(1000000, 9999999),

                        'languages' => $data['languages'],

                        'educations' => [
                            $data['education'],
                        ],

                        'work_experiences' => [
                            $data['experience'] .
                                ' tahun pengalaman membimbing umrah & haji',
                        ],

                        'reference_text' =>
                            $data['specialist'] .
                            ' berpengalaman dalam pendampingan jamaah.',

                        /**
                         * Gunakan URL langsung Unsplash
                         * Cocok untuk demo / staging
                         */
                        'photo_path' => $data['photo'] . '?w=600&q=80',

                        'ktp_image_path' => 'seed/dummy/ktp-example.png',

                        'verification_status' =>
                            MuthowifVerificationStatus::Approved,

                        'verified_at' => now(),

                        'wallet_balance' => rand(500000, 5000000),
                    ]
                );

                if ($number === 1) {
                    $parentProfileId = $profile->getKey();
                }

                app(MuthowifReferralCodeService::class)
                    ->ensureAssigned($profile->fresh());

                /**
                 * Simulasi referral network
                 */
                if (
                    $number >= 2 &&
                    $number <= 5 &&
                    $parentProfileId
                ) {
                    $profile->update([
                        'referred_by_muthowif_profile_id' =>
                            $parentProfileId,
                    ]);
                }

                /**
                 * Service marketplace
                 */
                [$groupService, $privateService] =
                    MuthowifService::ensurePairForProfile($profile);

                $groupService->update([
                    'daily_price' => rand(350000, 650000),
                    'min_pilgrims' => 5,
                    'max_pilgrims' => 45,
                ]);

                $privateService->update([
                    'daily_price' => rand(900000, 1800000),
                    'min_pilgrims' => 1,
                    'max_pilgrims' => 8,
                ]);

                $this->command?->info(
                    "Seeded realistic muthowif: {$data['name']}"
                );
            }
        });
    }
}