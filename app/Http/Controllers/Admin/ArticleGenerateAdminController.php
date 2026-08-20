<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Throwable;

class ArticleGenerateAdminController extends Controller
{
    public function create(): View
    {
        $url = trim((string) config('services.n8n_articles.webhook_url', ''));

        return view('admin.articles.generate', [
            'webhookConfigured' => $url !== '',
            'defaults' => [
                'niche' => 'Umroh',
                'keywords' => '',
                'target_audience' => 'jamaah umroh',
                'language' => ['Indonesia', 'English', 'Arabic'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $url = trim((string) config('services.n8n_articles.webhook_url', ''));
        if ($url === '') {
            return back()
                ->withInput()
                ->with('error', __('admin.article_generate.webhook_missing'));
        }

        $validated = $request->validate([
            'niche' => ['required', 'string', 'max:120'],
            'keywords' => ['nullable', 'string', 'max:2000'],
            'target_audience' => ['required', 'string', 'max:255'],
            'language' => ['required', 'array', 'min:1'],
            'language.*' => ['in:Indonesia,English,Arabic'],
        ]);

        $payload = [
            'niche' => $validated['niche'],
            'keywords' => trim((string) ($validated['keywords'] ?? '')),
            'target_audience' => $validated['target_audience'],
            'language' => array_values($validated['language']),
        ];

        try {
            $response = Http::timeout(60)
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', __('admin.article_generate.request_failed'));
        }

        if (! $response->successful()) {
            $n8nMessage = $response->json('message');
            $n8nHint = $response->json('hint');
            $detail = collect([$n8nMessage, $n8nHint])
                ->filter(fn ($part) => is_string($part) && trim($part) !== '')
                ->implode(' ');

            return back()
                ->withInput()
                ->with('error', $detail !== ''
                    ? $detail
                    : __('admin.article_generate.http_error', ['status' => $response->status()]));
        }

        $message = $response->json('message');
        if (! is_string($message) || trim($message) === '') {
            $message = __('admin.article_generate.started');
        }

        return redirect()
            ->route('admin.articles.generate')
            ->with('status', $message);
    }
}
