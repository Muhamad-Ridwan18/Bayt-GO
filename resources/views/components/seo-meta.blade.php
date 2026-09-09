@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'type' => 'website',
    'schema' => null,
    'canonical' => null,
    'alternates' => null,
])

@php
    $siteName = config('app.name', 'Bayt-GO');
    
    // Optimasi judul dengan keyword utama jika tidak ada judul spesifik
    $defaultTitle = __('seo.default_title');
    $fullTitle = $title ? "$title | $siteName" : $defaultTitle;
    
    // Deskripsi meta dengan keyword utama
    $fallbackDesc = __('seo.default_description');
    $metaDesc = \Illuminate\Support\Str::limit(strip_tags($description ?? $fallbackDesc), 155, '');
    
    $currentUrl = $canonical ?? url()->current();
    // Default OG image (gunakan logo atau ilustrasi premium)
    $metaImage = $image ?? asset('images/og-default.jpg');
@endphp

<!-- Meta Tags Dasar -->
<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $metaDesc }}">
<meta name="robots" content="index, follow">
<link rel="canonical" href="{{ $currentUrl }}">

{{-- Hanya halaman yang benar-benar punya versi per bahasa yang mengirim alternates. --}}
@if (filled($alternates))
    @foreach ($alternates as $altLocale => $altUrl)
        <link rel="alternate" hreflang="{{ $altLocale }}" href="{{ $altUrl }}">
    @endforeach
    @if (isset($alternates['id']))
        <link rel="alternate" hreflang="x-default" href="{{ $alternates['id'] }}">
    @endif
@endif
<!-- Open Graph / Facebook / WhatsApp -->
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $title ?? $fullTitle }}">
<meta property="og:description" content="{{ $metaDesc }}">
<meta property="og:url" content="{{ $currentUrl }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:image" content="{{ $metaImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">

<!-- Twitter Cards -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title ?? $fullTitle }}">
<meta name="twitter:description" content="{{ $metaDesc }}">
<meta name="twitter:image" content="{{ $metaImage }}">

<!-- Tag JSON-LD Schema -->
@if ($schema)
    @php
        $schemaPayload = is_array($schema) && array_is_list($schema) ? $schema : [$schema];
    @endphp
    @foreach ($schemaPayload as $schemaItem)
        <script type="application/ld+json">
            {!! json_encode($schemaItem, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
        </script>
    @endforeach
@endif
