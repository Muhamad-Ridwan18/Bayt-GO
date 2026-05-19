@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'type' => 'website',
    'schema' => null,
])

@php
    $siteName = config('app.name', 'Bayt-GO');
    
    // Optimasi judul dengan keyword utama jika tidak ada judul spesifik
    $defaultTitle = "Bayt-GO — Jasa Tour Guide Ibadah Umroh & Haji | Muthowif Terpercaya";
    $fullTitle = $title ? "$title | $siteName" : $defaultTitle;
    
    // Deskripsi meta dengan keyword utama
    $fallbackDesc = "Temukan layanan Muthowif terbaik dan jasa tour guide ibadah Umroh & Haji terpercaya di Bayt-GO. Bandingkan profil, rating ulasan, harga, dan pesan langsung asisten ibadah Anda demi kenyamanan maksimal.";
    $metaDesc = \Illuminate\Support\Str::limit(strip_tags($description ?? $fallbackDesc), 155, '');
    
    $currentUrl = request()->url();
    // Default OG image (gunakan logo atau ilustrasi premium)
    $metaImage = $image ?? asset('images/og-default.jpg');
@endphp

<!-- Meta Tags Dasar -->
<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $metaDesc }}">
<link rel="canonical" href="{{ $currentUrl }}">

<!-- Alternatif Bahasa (Multibahasa SEO) -->
@foreach(['id', 'en', 'ar'] as $lang)
    <link rel="alternate" hreflang="{{ $lang }}" href="{{ route('locale.switch', ['locale' => $lang]) }}?next={{ urlencode(request()->getPathInfo()) }}">
@endforeach
<link rel="alternate" hreflang="x-default" href="{{ url('/') }}">

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
    <script type="application/ld+json">
        {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endif
