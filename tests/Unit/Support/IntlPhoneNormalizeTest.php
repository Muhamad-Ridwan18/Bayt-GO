<?php

namespace Tests\Unit\Support;

use App\Support\IntlPhone;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IntlPhoneNormalizeTest extends TestCase
{
    public function test_normalize_accepts_indonesian_08_and_plus_62_formats(): void
    {
        $expected = '6281298765432';

        foreach (['081298765432', '+6281298765432', '6281298765432', '+62 812 9876 5432'] as $input) {
            $this->assertSame($expected, IntlPhone::normalize($input), "Failed for: {$input}");
        }
    }

    public function test_storage_lookup_variants_include_08_and_plus_62(): void
    {
        $variants = IntlPhone::storageLookupVariants('6281298765432', '+6281298765432');

        $this->assertContains('6281298765432', $variants);
        $this->assertContains('081298765432', $variants);
        $this->assertContains('+6281298765432', $variants);
        $this->assertContains('81298765432', $variants);
    }

    #[DataProvider('muthowifPhoneInputProvider')]
    public function test_normalize_each_input_format(string $input, string $expected): void
    {
        $this->assertSame($expected, IntlPhone::normalize($input));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function muthowifPhoneInputProvider(): array
    {
        return [
            'local 08' => ['081298765432', '6281298765432'],
            'plus 62' => ['+6281298765432', '6281298765432'],
            '62 without plus' => ['6281298765432', '6281298765432'],
            'plus 62 with spaces' => ['+62 812 9876 5432', '6281298765432'],
        ];
    }
}
