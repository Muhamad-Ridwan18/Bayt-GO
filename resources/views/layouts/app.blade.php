<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-slate-50">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @if(!blank($header ?? null))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                @if (session('status'))
                    @php
                        $statusKey = session('status');
                        $statusMessage = match ($statusKey) {
                            'profile-updated', 'password-updated' => __('profile.saved'),
                            'public-profile-updated' => __('profile_public.saved'),
                            'verification-link-sent' => __('profile.verification.sent'),
                            default => $statusKey,
                        };
                    @endphp
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                            {{ $statusMessage }}
                        </div>
                    </div>
                @endif
                @if (session('error'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                            {{ session('error') }}
                        </div>
                    </div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
