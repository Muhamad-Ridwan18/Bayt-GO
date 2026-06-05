<?php

namespace App\View\Components;

use App\Support\WelcomeLanding;
use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    public function __construct(
        public string $variant = 'default',
        public bool $wide = false,
    ) {}

    public function render(): View
    {
        return view('layouts.guest', [
            'heroImage' => WelcomeLanding::resolvedHeroImageUrl(),
        ]);
    }
}
