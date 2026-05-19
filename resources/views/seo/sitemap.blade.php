{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Halaman Utama -->
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Halaman Direktori Layanan -->
    <url>
        <loc>{{ route('layanan.index') }}</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    <!-- Profil Muthowif (Jasa Tour Guide Umroh & Haji) -->
    @foreach ($muthowifs as $m)
        <url>
            <loc>{{ route('layanan.show', $m) }}</loc>
            <lastmod>{{ $m->verified_at?->toAtomString() ?? now()->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.8</priority>
        </url>
    @endforeach

    <!-- Halaman Direktori Artikel -->
    <url>
        <loc>{{ route('articles.index') }}</loc>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- Halaman Artikel -->
    @foreach ($articles as $a)
        <url>
            <loc>{{ route('articles.show', $a->slug) }}</loc>
            <lastmod>{{ $a->published_at?->toAtomString() }}</lastmod>
            <changefreq>monthly</changefreq>
            <priority>0.6</priority>
        </url>
    @endforeach
</urlset>
