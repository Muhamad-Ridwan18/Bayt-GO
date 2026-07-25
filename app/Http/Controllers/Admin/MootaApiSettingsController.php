<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Moota\MootaApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MootaApiSettingsController extends Controller
{
    public function edit(MootaApiClient $client): View
    {
        return view('admin.moota-api-settings.edit', [
            'envTokenSet' => $client->hasEnvAccessToken(),
            'dbTokenSet' => ! $client->hasEnvAccessToken() && $client->hasPersistedAccessToken(),
            'bankAccountIds' => $client->bankAccountIds(),
            'apiEmail' => (string) config('services.moota.api_email', ''),
            'apiBaseUrl' => $client->baseUrl(),
        ]);
    }

    public function update(Request $request, MootaApiClient $client): RedirectResponse
    {
        $validated = $request->validate([
            'access_token' => ['nullable', 'string', 'max:4000'],
            'clear_token' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('clear_token')) {
            $client->clearPersistedAccessToken();

            return redirect()
                ->route('admin.moota-api-settings.edit')
                ->with('status', __('admin.moota_api.token_cleared'));
        }

        $token = trim((string) ($validated['access_token'] ?? ''));
        if ($token !== '') {
            $client->persistManualAccessToken($token);

            return redirect()
                ->route('admin.moota-api-settings.edit')
                ->with('status', __('admin.moota_api.token_saved'));
        }

        return redirect()
            ->route('admin.moota-api-settings.edit')
            ->with('status', __('admin.moota_api.token_unchanged'));
    }
}
