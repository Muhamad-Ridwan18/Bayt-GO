<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WhatsAppGateway;
use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppTextJob;
use App\Support\IntlPhone;
use App\Support\WhatsAppNotifySettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class WhatsAppNotifySettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.whatsapp-notify-settings.edit', [
            'toggleValues' => WhatsAppNotifySettings::toggleValuesForForm(),
            'adminNumbers' => WhatsAppNotifySettings::adminNumbersForForm(),
            'transactionalGateway' => WhatsAppNotifySettings::transactionalGatewayValuesForForm(),
            'bulkGateway' => WhatsAppNotifySettings::bulkGatewayValuesForForm(),
            'whatsappTransactionalConfigured' => WhatsAppNotifySettings::hasToken(WhatsAppGateway::Transactional),
            'whatsappBulkConfigured' => WhatsAppNotifySettings::hasToken(WhatsAppGateway::Bulk),
            'groups' => WhatsAppNotifySettings::groups(),
            'toggles' => WhatsAppNotifySettings::toggles(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate($this->gatewayValidationRules());

        WhatsAppNotifySettings::saveFromInput($request->all());

        return redirect()
            ->route('admin.whatsapp-notify-settings.edit')
            ->with('status', __('admin.whatsapp_notify.settings_saved'));
    }

    public function test(Request $request): JsonResponse
    {
        $request->validate(array_merge($this->gatewayValidationRules(), [
            'test_gateway' => ['required', Rule::enum(WhatsAppGateway::class)],
        ]));

        $gateway = WhatsAppGateway::from($request->string('test_gateway')->toString());
        $gatewayConfig = WhatsAppNotifySettings::gatewayFromInput($request->all(), $gateway);
        if ($gatewayConfig['token'] === '') {
            return response()->json([
                'message' => __('admin.whatsapp_notify.test_token_missing'),
            ], 422);
        }

        $mismatch = $this->gatewayCredentialMismatch($gateway, $gatewayConfig);
        if ($mismatch !== null) {
            return response()->json(['message' => $mismatch], 422);
        }

        $numbers = WhatsAppNotifySettings::adminNumbersFromInput($request->all());
        if ($numbers === []) {
            return response()->json([
                'message' => __('admin.whatsapp_notify.test_numbers_missing'),
            ], 422);
        }

        $gatewayLabel = $gateway === WhatsAppGateway::Bulk
            ? __('admin.whatsapp_notify.gateway_bulk_label')
            : __('admin.whatsapp_notify.gateway_transactional_label');

        $message = __('admin.whatsapp_notify.test_message', [
            'app' => config('app.name', 'BaytGo'),
            'time' => now()->timezone(config('app.timezone', 'Asia/Jakarta'))->format('d M Y H:i'),
            'url' => $gatewayConfig['api_url'],
            'session' => $gatewayConfig['session_id'] ?? '—',
            'gateway' => $gatewayLabel,
        ]);

        $results = [];
        $sent = 0;

        foreach ($numbers as $phone) {
            $dial = IntlPhone::fonnteDial($phone);
            if ($dial === null) {
                $results[] = [
                    'phone' => $phone,
                    'ok' => false,
                    'error' => __('admin.whatsapp_notify.test_invalid_number'),
                ];

                continue;
            }

            try {
                SendWhatsAppTextJob::dispatchSync(
                    $dial['target'],
                    $message,
                    $dial['country_calling_code'],
                    [],
                    $gateway,
                    $gatewayConfig['token'],
                    $gatewayConfig['api_url'],
                    $gatewayConfig['session_id'],
                    $gatewayConfig['country_code'],
                    true,
                );
                $sent++;
                $results[] = ['phone' => $phone, 'ok' => true];
            } catch (Throwable $e) {
                $results[] = [
                    'phone' => $phone,
                    'ok' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        if ($sent === 0) {
            return response()->json([
                'message' => __('admin.whatsapp_notify.test_all_failed'),
                'results' => $results,
            ], 422);
        }

        return response()->json([
            'message' => __('admin.whatsapp_notify.test_success', [
                'sent' => $sent,
                'total' => count($numbers),
                'gateway' => $gatewayLabel,
            ]),
            'results' => $results,
        ]);
    }

    /**
     * @param  array{token: string, api_url: string, session_id: ?string, country_code: string}  $gatewayConfig
     */
    private function gatewayCredentialMismatch(WhatsAppGateway $gateway, array $gatewayConfig): ?string
    {
        $url = strtolower($gatewayConfig['api_url']);
        $token = strtolower($gatewayConfig['token']);
        $isFonnteUrl = str_contains($url, 'api.fonnte.com');
        $isWsmToken = str_starts_with($token, 'wsm_');

        if ($gateway === WhatsAppGateway::Bulk && $isFonnteUrl) {
            return __('admin.whatsapp_notify.test_bulk_fonnte_url_mismatch');
        }

        if ($gateway === WhatsAppGateway::Bulk && ! $isWsmToken && ! $isFonnteUrl) {
            return null;
        }

        if ($gateway === WhatsAppGateway::Bulk && $isWsmToken && $isFonnteUrl) {
            return __('admin.whatsapp_notify.test_bulk_fonnte_url_mismatch');
        }

        if ($gateway === WhatsAppGateway::Transactional && $isWsmToken && ! $isFonnteUrl) {
            return __('admin.whatsapp_notify.test_transactional_wsm_token_mismatch');
        }

        return null;
    }

    /**
     * @return array<string, list<string>>
     */
    private function gatewayValidationRules(): array
    {
        return [
            'admin_numbers' => ['nullable', 'string', 'max:2000'],
            'gateway_token' => ['nullable', 'string', 'max:255'],
            'gateway_api_url' => ['nullable', 'string', 'max:500', 'url'],
            'gateway_session_id' => ['nullable', 'string', 'max:64'],
            'gateway_country_code' => ['nullable', 'string', 'max:4', 'regex:/^\d+$/'],
            'bulk_gateway_token' => ['nullable', 'string', 'max:255'],
            'bulk_gateway_api_url' => ['nullable', 'string', 'max:500', 'url'],
            'bulk_gateway_session_id' => ['nullable', 'string', 'max:64'],
            'bulk_gateway_country_code' => ['nullable', 'string', 'max:4', 'regex:/^\d+$/'],
            'bulk_gateway_media_public_url' => ['nullable', 'string', 'max:500'],
        ];
    }
}
