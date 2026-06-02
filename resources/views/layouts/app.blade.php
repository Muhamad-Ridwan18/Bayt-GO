<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicons -->
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-slate-50">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @if(!blank($header ?? null))
                <header class="bg-white shadow">
                    <x-page-container class="py-6">
                        {{ $header }}
                    </x-page-container>
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
                    <x-page-container class="pt-4">
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                            {{ $statusMessage }}
                        </div>
                    </x-page-container>
                @endif
                @if (session('error'))
                    <x-page-container class="pt-4">
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                            {{ session('error') }}
                        </div>
                    </x-page-container>
                @endif
                {{ $slot }}
            </main>
        </div>
        @auth
            @include('partials.global-chat')
        @endauth
        @stack('scripts')
    </body>
</html>
