<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SeoMetaOverrides;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeoMetaSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.seo-meta-settings.edit', [
            'pages' => SeoMetaOverrides::pages(),
            'locales' => SeoMetaOverrides::locales(),
            'values' => SeoMetaOverrides::valuesForForm(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate(SeoMetaOverrides::validationRules());

        SeoMetaOverrides::saveFromInput($request->all());

        return redirect()
            ->route('admin.seo-meta-settings.edit')
            ->with('status', __('admin.seo_meta.saved'));
    }
}
