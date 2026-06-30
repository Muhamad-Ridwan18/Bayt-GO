<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppTextJob;
use App\Support\IntlPhone;
use App\Support\WhatsAppNotifySettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class WhatsAppNotifySettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.whatsapp-notify-settings.edit', [
            'toggleValues' => WhatsAppNotifySettings::toggleValuesForForm(),
            'adminNumbers' => WhatsAppNotifySettings::adminNumbersForForm(),
            'gateway' => WhatsAppNotifySettings::gatewayValuesForForm(),
            'whatsappConfigured' => WhatsAppNotifySettings::hasToken(),
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
        $request->validate($this->gatewayValidationRules());

        $gatewayConfig = WhatsAppNotifySettings::gatewayFromInput($request->all());
        if ($gatewayConfig['token'] === '') {
            return response()->json([
                'message' => __('admin.whatsapp_notify.test_token_missing'),
            ], 422);
        }

        $numbers = WhatsAppNotifySettings::adminNumbersFromInput($request->all());
        if ($numbers === []) {
            return response()->json([
                'message' => __('admin.whatsapp_notify.test_numbers_missing'),
            ], 422);
        }

        $message = __('admin.whatsapp_notify.test_message', [
            'app' => config('app.name', 'BaytGo'),
            'time' => now()->timezone(config('app.timezone', 'Asia/Jakarta'))->format('d M Y H:i'),
            'url' => $gatewayConfig['api_url'],
            'session' => $gatewayConfig['session_id'] ?? '—',
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
            ]),
            'results' => $results,
        ]);
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
            'gateway_media_public_url' => ['nullable', 'string', 'max:500'],
        ];
    }
}
