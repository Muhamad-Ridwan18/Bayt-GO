@php
    use App\Support\IndonesianNumber;

    $profile = $package->muthowifProfile;
    $price = (int) round((float) $package->price);
@endphp

<li class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
    <div class="flex flex-1 flex-col p-5">
        @if ($package->category)
            <span class="mb-2 inline-flex w-fit rounded-full bg-brand-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-brand-800 ring-1 ring-brand-200/80">
                {{ $package->category->label() }}
            </span>
        @endif
        <h2 class="text-lg font-bold text-slate-900">{{ $package->name }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ __('layanan_pendukung.by_muthowif', ['name' => $profile?->user?->name ?? '—']) }}</p>
        @if (filled($package->description))
            <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-slate-600">{{ $package->description }}</p>
        @endif
        <p class="mt-4 text-sm text-slate-600">
            {{ __('layanan_pendukung.from_price') }}
            <span class="text-lg font-bold text-brand-700">Rp {{ IndonesianNumber::formatThousands((string) $price) }}</span>
            <span class="text-slate-500">{{ __('layanan_pendukung.flat_price') }}</span>
        </p>
    </div>
    <div class="border-t border-slate-100 bg-slate-50/60 p-4">
        <a href="{{ route('layanan-pendukung.show', $package) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-baytgo px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-baytgo-800">
            {{ __('layanan_pendukung.book_now') }}
        </a>
    </div>
</li>
