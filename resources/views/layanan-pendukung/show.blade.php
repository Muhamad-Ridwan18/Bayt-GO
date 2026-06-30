@php
    use App\Support\IndonesianNumber;

    $price = (int) round((float) $package->price);
    [$minPilgrims, $maxPilgrims] = array_values($package->pilgrimBounds());
@endphp

<x-marketplace-layout :title="$package->name.' | '.__('layanan_pendukung.page_title')" :meta-description="$package->description">
    <div class="ui-stack-compact">
        <a href="{{ route('layanan-pendukung.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-800">
            ← {{ __('layanan_pendukung.back_to_catalog') }}
        </a>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <p class="text-xs font-bold uppercase tracking-wider text-baytgo">{{ __('layanan_pendukung.package_detail') }}</p>
                @if ($package->category)
                    <span class="mt-2 inline-flex rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-800 ring-1 ring-brand-200/80">
                        {{ $package->category->label() }}
                    </span>
                @endif
                <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $package->name }}</h1>
                @if (filled($package->description))
                    <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $package->description }}</p>
                @endif
                <dl class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('layanan_pendukung.price_label') }}</dt>
                        <dd class="mt-1 text-xl font-bold text-brand-700">Rp {{ IndonesianNumber::formatThousands((string) $price) }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('layanan_pendukung.pilgrim_count') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $minPilgrims }} – {{ $maxPilgrims }}</dd>
                    </div>
                </dl>
            </section>

            <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('layanan_pendukung.muthowif_profile') }}</p>
                <p class="mt-2 text-lg font-bold text-slate-900">{{ $profile->user->name }}</p>
                <a href="{{ route('layanan.show', $profile) }}" class="mt-3 inline-flex text-sm font-semibold text-brand-700 hover:text-brand-800">
                    {{ __('layanan_pendukung.view_muthowif') }} →
                </a>
                <a href="{{ route('layanan-pendukung.book', $package) }}" class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-baytgo px-4 py-3 text-sm font-semibold text-white transition hover:bg-baytgo-800">
                    {{ __('layanan_pendukung.book_now') }}
                </a>
            </aside>
        </div>
    </div>
</x-marketplace-layout>
