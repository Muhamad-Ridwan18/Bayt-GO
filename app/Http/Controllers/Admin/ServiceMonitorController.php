<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminServiceMonitorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ServiceMonitorController extends Controller
{
    public function __construct(
        private readonly AdminServiceMonitorService $monitor,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $feed = $this->monitor->feed($request);
        $realtimeEnabled = config('broadcasting.default') !== 'null';

        return view('admin.service-monitor.index', [
            ...$feed,
            'realtimeEnabled' => $realtimeEnabled,
        ]);
    }

    public function fragment(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $feed = $this->monitor->feed($request);

        return view('admin.service-monitor.partials.feed', $feed);
    }
}
