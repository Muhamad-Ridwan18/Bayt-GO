<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dokumentasi webhook Moota — {{ config('app.name', 'BaytGo') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-slate-800 bg-slate-50 min-h-screen">
@php
    $headersSample = [
        'X-MOOTA-USER' => 'lkjqwle',
        'X-MOOTA-WEBHOOK' => 'oiuoiuqwe',
        'User-Agent' => 'MootaBot/1.5',
        'Signature' => 'oiuqwknlasdkuovasl;dkjzx..',
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];
    $headersPretty = json_encode($headersSample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $bodyPath = resource_path('samples/moota-webhook-body.json');
    $bodyPretty = '';
    if (is_readable($bodyPath)) {
        $decoded = json_decode((string) file_get_contents($bodyPath), true);
        $bodyPretty = is_array($decoded)
            ? (string) json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';
    }
@endphp
<div class="min-h-screen flex flex-col">
    <header class="sticky top-0 z-20 border-b border-white/10 bg-slate-900/95 backdrop-blur-md text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between gap-4">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 group">
                <x-site-logo variant="docs" />
                <span class="text-base font-bold tracking-tight">Bayt<span class="text-brand-300">Go</span></span>
            </a>
            <a href="{{ route('layanan.index') }}" class="text-sm font-medium text-white/90 hover:text-white">{{ __('layanan.find_muthowif') }}</a>
        </div>
    </header>

    <main class="flex-1 max-w-4xl mx-auto px-4 sm:px-6 py-10 w-full space-y-10">
        <div class="space-y-2">
            <p class="text-sm font-semibold text-brand-700">Referensi singkat · internal</p>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Dokumentasi webhook Moota</h1>
            <p class="text-slate-600 leading-relaxed">
                Ringkasan format permintaan HTTP yang dikirim Moota ke aplikasi Anda. Verifikasi wajib memakai header <strong>Signature</strong> (HMAC-SHA256) dan secret yang Anda definisikan saat membuat webhook di Moota.
            </p>
            <p class="text-sm text-slate-500">
                Panduan resmi:&nbsp;<a href="https://moota.co/guide/webhook/" class="text-brand-700 font-medium underline decoration-brand-700/40 hover:decoration-brand-700" target="_blank" rel="noopener noreferrer">moota.co/guide/webhook/</a>
            </p>
        </div>

        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-sm ring-1 ring-slate-900/5 space-y-3">
            <h2 class="text-lg font-bold text-slate-900">Endpoint BaytGo</h2>
            <p class="text-slate-600 text-sm leading-relaxed">Metode <code class="text-sm bg-slate-100 px-1.5 py-0.5 rounded-md font-mono text-brand-900">POST</code>, dilindungi whitelist IP (<code class="text-xs bg-slate-100 px-1 rounded">AllowMootaWebhookIp</code>) — daftar IP di konfigurasi <code class="text-xs bg-slate-100 px-1 rounded">MOOTA_WEBHOOK_IPS</code>.</p>
            <div class="rounded-2xl bg-slate-900 text-brand-50 px-4 py-3 font-mono text-sm break-all">{{ $webhookUrl }}</div>
        </section>

        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-sm ring-1 ring-slate-900/5 space-y-4">
            <h2 class="text-lg font-bold text-slate-900">Contoh header yang dikirim Moota</h2>
            <p class="text-slate-600 text-sm">Nilai contoh bersifat placeholder; pengujian menggunakan <a href="https://moota.co" class="text-brand-700 font-medium underline underline-offset-2 hover:text-brand-800" target="_blank" rel="noopener noreferrer">portal sandbox</a> Moota.</p>
            <pre class="overflow-x-auto rounded-2xl bg-slate-900 text-brand-50 p-5 text-xs sm:text-sm leading-relaxed shadow-inner"><code>{{ $headersPretty }}</code></pre>
        </section>

        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-sm ring-1 ring-slate-900/5 space-y-4">
            <h2 class="text-lg font-bold text-slate-900">Isi tubuh permintaan (request body)</h2>
            <p class="text-slate-600 text-sm leading-relaxed">Array JSON satu atau beberapa mutasi bank. Konten bisa berbeda per bank / versi hook; struktur ini mengikuti contoh dokumentasi resmi.</p>
            @if ($bodyPretty !== '')
                <pre class="overflow-x-auto max-h-[32rem] rounded-2xl bg-slate-900 text-brand-50 p-5 text-xs sm:text-sm leading-relaxed shadow-inner"><code>{{ $bodyPretty }}</code></pre>
            @else
                <p class="text-amber-800 text-sm rounded-xl bg-amber-50 border border-amber-200 px-4 py-3">Berkas contoh hilang atau tidak bisa dibaca: <span class="font-mono">{{ $bodyPath }}</span></p>
            @endif
        </section>

        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-sm ring-1 ring-slate-900/5 space-y-4">
            <h2 class="text-lg font-bold text-slate-900">Verifikasi tanda tangan (Signature)</h2>
            <p class="text-slate-600 text-sm leading-relaxed">
                Setiap permintaan memakai metode POST dan menyertakan <strong>Signature</strong> yang dihitung dari isi tubuh HTTP mentah secara persis sama dengan yang digunakan Moota. Secret harus sama dengan yang Anda set saat mendefinisikan webhook (mis. <code class="text-xs bg-slate-100 px-1 rounded">signUsingSecret</code> dalam konfigurasi hook di sisi Anda / generator Moota).
            </p>
            <pre class="overflow-x-auto rounded-2xl bg-slate-100 text-slate-900 p-5 text-xs sm:text-sm leading-relaxed border border-slate-200"><code>// payload = tubuh HTTP mentah sebagai string (exact bytes dari POST body)
// secret = rahasia Anda yang Anda set di pembuatan webhook
$signature = hash_hmac('sha256', $payload_json_from_response, $secret);</code></pre>
            <p class="text-slate-600 text-sm">Bandingkan hasil tersebut dengan header <strong>Signature</strong> yang dikirim Moota. Gunakan perbandingan tegas (mis. <code class="text-xs bg-slate-100 px-1 rounded">hash_equals</code> di PHP) agar tidak rentan timing attack.</p>
            <pre class="overflow-x-auto rounded-2xl bg-slate-900 text-brand-50 p-5 text-xs sm:text-sm leading-relaxed shadow-inner"><code>// Laravel: verifikasi (set MOOTA_WEBHOOK_SIGNING_SECRET di .env Anda)
$rawBody = $request-&gt;getContent();
$secret = (string) config('services.moota.signing_secret', '');
if ($secret === '') {
    abort(503, 'Moota signing secret not configured');
}
$provided = (string) $request-&gt;header('Signature', '');
$expected = hash_hmac('sha256', $rawBody, $secret);

if (! hash_equals($expected, $provided)) {
    abort(403);
}</code></pre>
            <p class="text-xs text-slate-500">
                Pengujian: gunakan sandbox Moota; pastikan <code class="text-[0.6875rem] bg-slate-100 px-1 rounded">secret</code> dan serialisasi tubuh sama persis pada permintaan uji dengan produksi.
            </p>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white mt-auto py-8 text-center text-sm text-slate-500">
        <a href="{{ url('/') }}" class="text-brand-700 font-medium hover:text-brand-800">← Beranda</a>
        <span class="mx-2 text-slate-300">·</span>
        <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
    </footer>
</div>
</body>
</html>
