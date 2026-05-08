<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    private const SUPPORTED = ['en', 'id', 'ar'];

    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, self::SUPPORTED, true)) {
            abort(404);
        }

        $request->session()->put('locale', $locale);

        if ($request->user() !== null) {
            $request->user()->forceFill(['locale' => $locale])->save();
        }

        $referer = $request->headers->get('Referer');
        $appUrl = rtrim((string) config('app.url'), '/');

        if (is_string($referer)
            && $referer !== ''
            && filter_var($referer, FILTER_VALIDATE_URL) !== false
            && ($appUrl === '' || str_starts_with($referer, $appUrl))) {
            return redirect()->to($referer);
        }

        return redirect()->route('welcome');
    }
}
