<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Landing page for consolidated admin navigation (navbar “Settings”).
 */
class AdminSettingsHubController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.settings.index');
    }
}
