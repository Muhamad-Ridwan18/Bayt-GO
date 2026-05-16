<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialSettingsController extends Controller
{
    /**
     * Tampilkan form pengaturan finansial.
     */
    public function edit(): View
    {
        $fallbackUsdRate = SiteSetting::getValue('fallback_usd_rate', config('app.currency.fallback_usd_rate', '16000'));
        
        return view('admin.settings.financial.edit', compact('fallbackUsdRate'));
    }

    /**
     * Perbarui pengaturan finansial di database.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'fallback_usd_rate' => ['required', 'numeric', 'min:1000'],
        ]);

        SiteSetting::putValue('fallback_usd_rate', $request->input('fallback_usd_rate'));

        return back()->with('status', 'Pengaturan Kurs Dolar berhasil diperbarui.');
    }
}
