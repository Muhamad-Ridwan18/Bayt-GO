<?php

namespace Tests\Unit\Support;

use App\Support\PublicBioText;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicBioTextTest extends TestCase
{
    #[Test]
    public function it_strips_whatsapp_and_phone_numbers_from_bio(): void
    {
        $input = <<<'TXT'
✨ Terbuka untuk Kolaborasi Mutawif

Bagi travel, LA, maupun rekan-rekan jamaah umrah mandiri yang ingin bekerja sama atau tertarik dengan layanan mutawif kami, silakan menghubungi:

📞 WhatsApp: +62851-9047-0991
Atau dm via Instagram : taufiq_ilham_d.y

Jazakumullahu khairan 🤝
TXT;

        $out = PublicBioText::withoutContactNumbers($input);

        $this->assertNotNull($out);
        $this->assertStringNotContainsString('62851', $out);
        $this->assertStringNotContainsString('WhatsApp', $out);
        $this->assertStringContainsString('Instagram : taufiq_ilham_d.y', $out);
        $this->assertStringContainsString('Jazakumullahu khairan', $out);
    }

    #[Test]
    public function it_strips_local_phone_formats(): void
    {
        $out = PublicBioText::withoutContactNumbers("Hubungi saya di 0851 9047 0991 ya");

        $this->assertSame('Hubungi saya di  ya', $out);
        $this->assertStringNotContainsString('0851', (string) $out);
    }
}
