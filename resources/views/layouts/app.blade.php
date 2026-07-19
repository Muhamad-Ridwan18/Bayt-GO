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
    <body class="font-sans antialiased @auth{{ Auth::user()->isCustomer() ? 'customer-mobile-nav' : '' }}@endauth">
        <div class="flex min-h-screen flex-col bg-slate-50">
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
            <main class="ui-app-main flex-1 @auth{{ Auth::user()->isCustomer() ? 'pb-10 lg:pb-12' : '' }}@endauth">
                {{ $slot }}
            </main>

            @auth
                @if (Auth::user()->isCustomer() && ! request()->routeIs('chat.index'))
                    @include('partials.site-footer')
                @endif
            @endauth
        </div>
        <x-customer-bottom-nav />
        <x-ui.toast-stack />
        @auth
            @unless (request()->routeIs('chat.index'))
                @include('partials.global-chat')
            @endunless
        @endauth
        @stack('scripts')
    </body>
</html>
