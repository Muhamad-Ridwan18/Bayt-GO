@php
    use App\Enums\SupportPackageCategory;
    use App\Support\IndonesianNumber;
    use Illuminate\Support\Str;

    $profile = $package->muthowifProfile;
    $user = $profile?->user;
    $price = (int) round((float) $package->price);
    $name = $user?->name ?? '—';
    $initials = collect(preg_split('/\s+/', trim($name)) ?: [])
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    if ($initials === '') {
        $initials = '—';
    }

    $reviewsCount = (int) ($profile->booking_reviews_count ?? 0);
    $avgRating = $profile->average_rating !== null
        ? number_format((float) $profile->average_rating, 1)
        : null;
    $completedCount = (int) ($profile->completed_bookings_count ?? 0);
    $workLocation = $profile?->workLocationLabel();
    $pilgrimLabel = $package->min_pilgrims === $package->max_pilgrims
        ? __('layanan_pendukung.meta_pilgrims_exact', ['count' => (int) $package->min_pilgrims])
        : __('layanan_pendukung.meta_pilgrims_range', [
            'min' => (int) $package->min_pilgrims,
            'max' => (int) $package->max_pilgrims,
        ]);

    $categoryBadge = match ($package->category) {
        SupportPackageCategory::Tawaf => 'bg-emerald-50 text-emerald-800 ring-emerald-200/80',
        SupportPackageCategory::Umrah => 'bg-sky-50 text-sky-900 ring-sky-200/80',
        SupportPackageCategory::Ziarah => 'bg-amber-50 text-amber-950 ring-amber-200/80',
        SupportPackageCategory::Mobility => 'bg-violet-50 text-violet-900 ring-violet-200/80',
        default => 'bg-slate-100 text-slate-700 ring-slate-200/80',
    };

    $fallbackSvg = 'data:image/svg+xml,'.rawurlencode(
        '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"><rect fill="#ecfdf5" width="64" height="64"/><text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" font-size="20" font-family="sans-serif" fill="#0f2a25">'
        .htmlspecialchars($initials, ENT_XML1 | ENT_QUOTES, 'UTF-8')
        .'</text></svg>'
    );
@endphp

<li class="h-full list-none">
    <article class="group/card flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100/80 transition duration-300 hover:border-baytgo/25 hover:shadow-lg hover:shadow-baytgo/5 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            @if ($package->category)
                <span @class(['inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1', $categoryBadge])>
                    {{ $package->category->label() }}
                </span>
            @else
                <span></span>
            @endif
        </div>

        <h2 class="mt-3 line-clamp-2 text-base font-bold text-slate-900 sm:text-lg">
            <a href="{{ route('layanan-pendukung.show', $package) }}" class="transition hover:text-baytgo focus:outline-none focus-visible:ring-2 focus-visible:ring-baytgo rounded">
                {{ $package->name }}
            </a>
        </h2>

        <div class="mt-3 flex items-start gap-3">
            <div class="relative h-11 w-11 shrink-0 overflow-hidden rounded-full bg-emerald-50 ring-2 ring-white shadow-sm">
                @if ($profile)
                    <img
                        src="{{ $profile->photoUrl() }}"
                        alt=""
                        class="h-full w-full object-cover"
                        loading="lazy"
                        decoding="async"
                        onerror="this.onerror=null; this.src={!! json_encode($fallbackSvg) !!}"
                    />
                @else
                    <span class="flex h-full w-full items-center justify-center text-xs font-bold text-baytgo">{{ $initials }}</span>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-1.5">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $name }}</p>
                    <span class="inline-flex items-center gap-0.5 rounded-full bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-800 ring-1 ring-emerald-200/80">
                        <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                        {{ __('marketplace.card.badge_verified') }}
                    </span>
                </div>
                <div class="mt-1 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-slate-600">
                    @if ($avgRating !== null)
                        <span class="inline-flex items-center gap-0.5 font-semibold text-amber-800">
                            <svg class="h-3.5 w-3.5 text-amber-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            {{ $avgRating }}
                            @if ($reviewsCount > 0)
                                <span class="font-medium text-slate-500">{{ __('marketplace.card.reviews_count', ['count' => $reviewsCount]) }}</span>
                            @endif
                        </span>
                    @endif
                    @if ($completedCount > 0)
                        <span class="text-slate-500">{{ __('layanan_pendukung.completed_services', ['count' => $completedCount]) }}</span>
                    @endif
                </div>
            </div>
        </div>

        @if (filled($package->description))
            <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-slate-600">{{ Str::limit(trim(strip_tags($package->description)), 110) }}</p>
        @endif

        <ul class="mt-4 flex flex-wrap gap-x-3 gap-y-2 text-[11px] font-medium text-slate-600 sm:text-xs">
            <li class="inline-flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5 shrink-0 text-baytgo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zM6 8a2 2 0 11-4 0 2 2 0 014 0zM1.49 15.326a.78.78 0 01-.358-.442 3 3 0 014.308-3.516 6.484 6.484 0 00-1.905 3.959c-.023.222-.014.442.025.654a4.97 4.97 0 01-2.07-.655zM16.44 15.98a4.97 4.97 0 002.07-.654.78.78 0 00.357-.442 3 3 0 00-4.308-3.517 6.484 6.484 0 011.907 3.96 2.32 2.32 0 01-.026.654zM18 8a2 2 0 11-4 0 2 2 0 014 0zM5.304 16.19a.844.844 0 01-.277-.71 5 5 0 019.947 0 .843.843 0 01-.277.71A6.975 6.975 0 0110 18a6.974 6.974 0 01-4.696-1.81z" /></svg>
                {{ $pilgrimLabel }}
            </li>
            @if (filled($workLocation))
                <li class="inline-flex max-w-full items-center gap-1.5">
                    <svg class="h-3.5 w-3.5 shrink-0 text-baytgo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.288-.15.715-.369 1.245-.667 1.032-.6 2.405-1.474 3.79-2.65 1.385-1.176 2.618-2.54 3.39-3.96a10.78 10.78 0 002.133-5.85V6.75A2.25 2.25 0 0013.5 4.5h-7A2.25 2.25 0 004.5 6.75v.823c.001 1.812.317 3.569.92 5.176 1.003 2.63 2.79 4.893 4.87 6.174zM10 10.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" /></svg>
                    <span class="truncate">{{ $workLocation }}</span>
                </li>
            @endif
            <li class="inline-flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5 shrink-0 text-baytgo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" /></svg>
                {{ __('layanan_pendukung.meta_flat') }}
            </li>
        </ul>

        <div class="mt-auto flex items-end justify-between gap-3 border-t border-slate-100 pt-4">
            <div>
                <p class="text-[11px] font-medium text-slate-500">{{ __('layanan_pendukung.from_price') }}</p>
                <p class="text-lg font-bold text-slate-900">
                    Rp {{ IndonesianNumber::formatThousands((string) $price) }}
                </p>
                <p class="text-[11px] text-slate-500">{{ __('layanan_pendukung.flat_price') }}</p>
            </div>
            <a href="{{ route('layanan-pendukung.show', $package) }}" class="inline-flex shrink-0 items-center gap-1 rounded-xl bg-baytgo px-3.5 py-2.5 text-xs font-semibold text-white shadow-sm shadow-baytgo/20 transition hover:bg-baytgo-800 sm:text-sm">
                {{ __('layanan_pendukung.view_detail') }}
                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
            </a>
        </div>
    </article>
</li>
