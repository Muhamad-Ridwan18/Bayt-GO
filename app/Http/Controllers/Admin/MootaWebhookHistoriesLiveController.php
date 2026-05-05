<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MootaWebhookHistory;
use Illuminate\View\View;

final class MootaWebhookHistoriesLiveController extends Controller
{
    /**
     * Produksi — daftar realtime (admin).
     */
    public function live(): View
    {
        return $this->historyFeedView('admin.moota_webhooks_live');
    }

    /**
     * Sandbox / uji integrasi — panduan konfigurasi + feed realtime yang sama.
     */
    public function testing(): View
    {
        return $this->historyFeedView('admin.moota_webhook_testing');
    }

    private function historyFeedView(string $view): View
    {
        $rows = MootaWebhookHistory::query()
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (MootaWebhookHistory $h) => $h->toRealtimeSnapshot())
            ->values()
            ->all();

        $realtimeEnabled = config('broadcasting.default') !== 'null';

        return view($view, compact('rows', 'realtimeEnabled'));
    }
}
