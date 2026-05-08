<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Support\SiteBrand;
use App\Support\WelcomeLanding;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteAppearanceController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.site-appearance.edit', [
            'logoUrl' => SiteBrand::logoPublicUrl(),
            'welcomeHeroPreviewUrl' => WelcomeLanding::resolvedHeroImageUrl(),
            'welcomeHeroExternalUrl' => WelcomeLanding::externalUrlRaw() ?? '',
            'welcomeHeroHasUpload' => WelcomeLanding::uploadedHeroPublicUrl() !== null,
            'welcomePositionsBase' => SiteSetting::getValue(WelcomeLanding::SETTING_OBJECT_BASE) ?? '',
            'welcomePositionsSm' => SiteSetting::getValue(WelcomeLanding::SETTING_OBJECT_SM) ?? '',
            'welcomePositionsLg' => SiteSetting::getValue(WelcomeLanding::SETTING_OBJECT_LG) ?? '',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validUrlIfPresent = static function (?string $value): bool {
            if ($value === null || trim($value) === '') {
                return true;
            }

            return (bool) filter_var(trim($value), FILTER_VALIDATE_URL);
        };

        $request->validate([
            'logo' => ['nullable', 'file', 'mimes:png,jpeg,jpg,webp,svg', 'max:2048'],
            'welcome_hero_image' => ['nullable', 'file', 'mimes:png,jpeg,jpg,webp', 'max:5120'],
            'welcome_hero_external_url' => [
                'nullable',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail) use ($validUrlIfPresent): void {
                    if (! $validUrlIfPresent(is_string($value) ? $value : null)) {
                        $fail(__('validation.url'));
                    }
                },
            ],
            'welcome_hero_object_position_base' => ['nullable', 'string', 'max:48'],
            'welcome_hero_object_position_sm' => ['nullable', 'string', 'max:48'],
            'welcome_hero_object_position_lg' => ['nullable', 'string', 'max:48'],
        ]);

        if ($request->boolean('remove_logo')) {
            SiteBrand::forgetLogoFile();
        } elseif ($request->hasFile('logo')) {
            SiteBrand::forgetLogoFile();
            $path = $request->file('logo')->store('site', 'public');
            SiteSetting::putValue(SiteBrand::SETTING_LOGO_PATH, $path);
        }

        if ($request->boolean('remove_welcome_hero_custom')) {
            WelcomeLanding::forgetCustomHero();
        } elseif ($request->hasFile('welcome_hero_image')) {
            WelcomeLanding::storeUploadedHero($request->file('welcome_hero_image'));
        }

        WelcomeLanding::saveExternalUrl($request->input('welcome_hero_external_url'));
        WelcomeLanding::saveObjectPositions([
            'base' => $request->input('welcome_hero_object_position_base'),
            'sm' => $request->input('welcome_hero_object_position_sm'),
            'lg' => $request->input('welcome_hero_object_position_lg'),
        ]);

        return redirect()->route('admin.site-appearance.edit')
            ->with('status', __('admin.appearance.settings_saved'));
    }
}
