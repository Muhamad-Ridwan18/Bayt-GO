<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Support\SiteBrand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteAppearanceController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.site-appearance.edit', [
            'logoUrl' => SiteBrand::logoPublicUrl(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['nullable', 'file', 'mimes:png,jpeg,jpg,webp,svg', 'max:2048'],
        ]);

        if ($request->boolean('remove_logo')) {
            SiteBrand::forgetLogoFile();

            return redirect()->route('admin.site-appearance.edit')
                ->with('status', __('admin.appearance.removed'));
        }

        if ($request->hasFile('logo')) {
            SiteBrand::forgetLogoFile();
            $path = $request->file('logo')->store('site', 'public');
            SiteSetting::putValue(SiteBrand::SETTING_LOGO_PATH, $path);

            return redirect()->route('admin.site-appearance.edit')
                ->with('status', __('admin.appearance.updated'));
        }

        return redirect()->route('admin.site-appearance.edit')
            ->with('status', __('admin.appearance.unchanged'));
    }
}
