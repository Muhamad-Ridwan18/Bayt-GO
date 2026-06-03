<?php

namespace Tests\Unit\Support;

use App\Enums\MuthowifVerificationStatus;
use App\Enums\UserRole;
use App\Models\MuthowifProfile;
use App\Models\User;
use App\Services\PasswordResetOtpService;
use App\Support\IntlPhone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IntlPhoneLookupTest extends TestCase
{
    use RefreshDatabase;

    private const STORED = '081298765432';

    #[DataProvider('muthowifPhoneInputProvider')]
    public function test_muthowif_profile_find_by_phone_accepts_08_and_plus_62_input(string $input): void
    {
        $user = $this->createMuthowifWithStoredPhone();

        $normalized = IntlPhone::normalize($input);
        $this->assertNotNull($normalized);

        $profile = MuthowifProfile::findByPhone($normalized, $input);

        $this->assertNotNull($profile);
        $this->assertSame($user->id, $profile->user_id);
    }

    #[DataProvider('muthowifPhoneInputProvider')]
    public function test_password_reset_resolve_user_accepts_08_and_plus_62(string $input): void
    {
        $user = $this->createMuthowifWithStoredPhone();

        $normalized = IntlPhone::normalize($input);
        $resolved = app(PasswordResetOtpService::class)->resolveUserByPhone((string) $normalized, $input);

        $this->assertNotNull($resolved);
        $this->assertTrue($resolved->is($user));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function muthowifPhoneInputProvider(): array
    {
        return [
            'local 08' => ['081298765432'],
            'plus 62' => ['+6281298765432'],
            '62 without plus' => ['6281298765432'],
            'plus 62 with spaces' => ['+62 812 9876 5432'],
        ];
    }

    private function createMuthowifWithStoredPhone(): User
    {
        $user = User::factory()->create([
            'role' => UserRole::Muthowif,
            'phone' => null,
        ]);

        MuthowifProfile::create([
            'user_id' => $user->id,
            'phone' => self::STORED,
            'address' => 'Alamat uji',
            'nik' => '3201010101010001',
            'birth_date' => '1990-01-01',
            'photo_path' => 'muthowif_documents/test/photo.jpg',
            'ktp_image_path' => 'muthowif_documents/test/ktp.jpg',
            'verification_status' => MuthowifVerificationStatus::Approved,
        ]);

        return $user;
    }
}
