<?php

namespace Tests\Unit\Services;

use App\Services\FonnteService;
use App\Support\IntlPhone;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FonnteServiceTest extends TestCase
{
    public function test_official_api_sends_e164_target_with_country_code_bypass(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'status' => true,
                'detail' => 'success! message in queue',
            ], 200),
        ]);

        $dial = IntlPhone::fonnteDial('+905528672142');
        $this->assertNotNull($dial);

        app(FonnteService::class)->sendTextWithGateway(
            'test-token',
            'https://api.fonnte.com/send',
            null,
            '62',
            $dial['target'],
            'test',
            $dial['country_calling_code'],
        );

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.fonnte.com/send'
                && $request->data()['target'] === '905528672142'
                && $request->data()['countryCode'] === '0';
        });
    }

    public function test_official_api_keeps_indonesian_e164_with_bypass(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'status' => true,
                'detail' => 'success! message in queue',
            ], 200),
        ]);

        $dial = IntlPhone::fonnteDial('+6281298765432');
        $this->assertNotNull($dial);

        app(FonnteService::class)->sendTextWithGateway(
            'test-token',
            'https://api.fonnte.com/send',
            null,
            '62',
            $dial['target'],
            'test',
            $dial['country_calling_code'],
        );

        Http::assertSent(function ($request): bool {
            return $request->data()['target'] === '6281298765432'
                && $request->data()['countryCode'] === '0';
        });
    }
}
