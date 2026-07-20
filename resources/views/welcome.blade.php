<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <x-seo-meta
            :title="$page->seo['title']"
            :description="$page->seo['description']"
            :schema="$page->seo['schema']"
        />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50/80 font-welcome text-slate-800 antialiased selection:bg-gold-light selection:text-baytgo-950">
        <div class="flex min-h-screen flex-col">
            <x-marketing-public-header active="welcome" />

            <main class="flex-1">
                <x-home.feed :page="$page" />
            </main>

            @include('partials.site-footer')
        </div>
    </body>
</html>
