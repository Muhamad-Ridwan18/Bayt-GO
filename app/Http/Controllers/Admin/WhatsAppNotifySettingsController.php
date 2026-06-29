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
            'whatsappConfigured' => (string) config('services.fonnte.token') !== '',
            'groups' => WhatsAppNotifySettings::groups(),
            'toggles' => WhatsAppNotifySettings::toggles(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'admin_numbers' => ['nullable', 'string', 'max:2000'],
        ]);

        WhatsAppNotifySettings::saveFromInput($request->all());

        return redirect()
            ->route('admin.whatsapp-notify-settings.edit')
            ->with('status', __('admin.whatsapp_notify.settings_saved'));
    }
}
