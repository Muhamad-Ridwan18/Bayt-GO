<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    private const SUPPORTED = ['en', 'id'];

    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, self::SUPPORTED, true)) {
            abort(404);
        }

        $request->session()->put('locale', $locale);

        if ($request->user() !== null) {
            $request->user()->forceFill(['locale' => $locale])->save();
        }

        // `next` dipakai halaman yang punya URL per bahasa agar berpindah ke padanannya.
        foreach ([$request->query('next'), $request->headers->get('Referer')] as $target) {
            if ($this->isInternalUrl($target)) {
                return redirect()->to((string) $target);
            }
        }

        return redirect()->route('welcome');
    }

    private function isInternalUrl(mixed $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        // Path relatif selalu internal; URL absolut wajib satu origin dengan aplikasi.
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return $host !== null && $host === parse_url((string) config('app.url'), PHP_URL_HOST);
    }
}
