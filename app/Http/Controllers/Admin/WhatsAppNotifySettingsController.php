<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\WhatsAppNotifySettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
        $request->validate([
            'admin_numbers' => ['nullable', 'string', 'max:2000'],
            'gateway_token' => ['nullable', 'string', 'max:255'],
            'gateway_api_url' => ['nullable', 'string', 'max:500', 'url'],
            'gateway_session_id' => ['nullable', 'string', 'max:64'],
            'gateway_country_code' => ['nullable', 'string', 'max:4', 'regex:/^\d+$/'],
            'gateway_media_public_url' => ['nullable', 'string', 'max:500'],
        ]);

        WhatsAppNotifySettings::saveFromInput($request->all());

        return redirect()
            ->route('admin.whatsapp-notify-settings.edit')
            ->with('status', __('admin.whatsapp_notify.settings_saved'));
    }
}
