<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\WelcomePageCache;
use Illuminate\View\View;

final class WelcomeController extends Controller
{
    public function __invoke(): View
    {
        return view('welcome', WelcomePageCache::data());
    }
}
