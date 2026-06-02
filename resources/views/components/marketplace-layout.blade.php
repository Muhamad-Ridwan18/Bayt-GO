@props(['title' => null, 'metaDescription' => null, 'schema' => null, 'wide' => true])
@php
    $contactRaw = (string) (config('app.contact_whatsapp') ?: config('app.contact_phone'));
    $contactDigits = preg_replace('/\D+/', '', $contactRaw ?? '') ?? '';
    $contactLink = $contactDigits !== '' ? 'https://wa.me/'.$contactDigits : null;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="overflow-x-hidden">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        
        <x-seo-meta :title="$title" :description="$metaDescription" :schema="$schema" />

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen overflow-x-hidden bg-gradient-to-b from-slate-50 via-white to-amber-50/40 font-sans antialiased text-slate-800 selection:bg-brand-200/60">
        <div class="flex min-h-screen min-w-0 flex-col">
            @auth
                @include('layouts.navigation')
            @else
                <x-marketing-public-header active="layanan" />
            @endauth

            <main class="w-full min-w-0 flex-1 py-8 sm:py-12">
                <x-page-container class="min-w-0">
                    {{ $slot }}
                </x-page-container>
            </main>

            <footer class="mt-auto w-full border-t border-slate-200/80 bg-gradient-to-b from-white to-slate-50/90">
                <x-page-container class="flex min-w-0 flex-col gap-3 py-8 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                    <span class="font-medium">&copy; {{ date('Y') }} {{ config('app.name') }}</span>
                    @if ($contactLink)
                        <a href="{{ $contactLink }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-700 transition hover:text-brand-800">
                            {{ __('marketplace.footer_contact', ['contact' => $contactRaw]) }}
                        </a>
                    @endif
                </x-page-container>
            </footer>
        </div>
    </body>
</html>
