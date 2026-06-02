@php
    $title = $landing['title'];
    $description = $landing['subtitle'];
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => $title,
        'description' => $description,
        'url' => route('seo.landing', ['keyword' => $keyword]),
    ];
@endphp

<x-layouts.marketing-public :title="$title" :meta-description="$description" :schema="$schema" active-nav="layanan">
    <section class="bg-gradient-to-b from-welcomeCanvas to-white pb-16 pt-10">
        <x-page-container>
            <div class="text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-baytgo/90">Muthowif</p>
                <h1 class="mt-4 text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">{{ $landing['title'] }}</h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg leading-8 text-slate-600">{{ $landing['subtitle'] }}</p>
            </div>

            <div class="mt-10 grid gap-4 md:grid-cols-2">
                @forelse ($services as $service)
                    <x-marketplace.profile-card :profile="$service" />
                @empty
                    <div class="rounded-3xl border border-slate-200 bg-white p-8 text-center">
                        <p class="text-lg font-semibold text-slate-900">Tidak ada muthowif tersedia saat ini.</p>
                        <p class="mt-3 text-sm text-slate-600">Silakan kembali beberapa saat lagi atau gunakan fitur pencarian untuk menemukan muthowif yang sesuai.</p>
                        <a href="{{ route('layanan.index') }}" class="mt-6 inline-flex rounded-full bg-baytgo px-6 py-3 text-sm font-bold text-white shadow-md shadow-baytgo/25">Telusuri Semua Layanan</a>
                    </div>
                @endforelse
            </div>

            <div class="mt-16 rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Mengapa memilih Muthowif dari Bayt-GO?</h2>
                <ul class="mt-4 grid gap-3 sm:grid-cols-3">
                    <li class="rounded-3xl bg-slate-50 p-5 text-sm text-slate-700">Verified profile lengkap dengan ulasan jamaah.</li>
                    <li class="rounded-3xl bg-slate-50 p-5 text-sm text-slate-700">Komunikasi langsung dengan muthowif sebelum memesan.</li>
                    <li class="rounded-3xl bg-slate-50 p-5 text-sm text-slate-700">Pencarian cepat berdasarkan kota dan bahasa.</li>
                </ul>
            </x-page-container>
        </div>
    </section>
</x-layouts.marketing-public>
