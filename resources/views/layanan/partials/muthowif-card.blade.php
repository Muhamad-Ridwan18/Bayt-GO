@props([
    'profile',
    'group' => null,
    'private' => null,
    'listQueryString' => '',
])

@php
    use App\Enums\MuthowifServiceType;
    use App\Support\IndonesianNumber;
    use Illuminate\Support\Str;

    $href = route('layanan.show', $profile).($listQueryString !== '' ? '?'.$listQueryString : '');
    $initial = mb_substr($profile->user->name, 0, 1);
    $fallbackSvg = 'data:image/svg+xml,'.rawurlencode(
        '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128"><rect fill="#e2e8f0" width="128" height="128"/><text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" font-size="48" fill="#475569">'
        .htmlspecialchars($initial, ENT_XML1 | ENT_QUOTES, 'UTF-8')
        .'</text></svg>'
    );

    if ($group && filled($group->description)) {
        $bio = Str::limit(trim(strip_tags($group->description)), 130);
    } elseif ($private && filled($private->description)) {
        $bio = Str::limit(trim(strip_tags($private->description)), 130);
    } else {
        $langs = $profile->languagesForDisplay();
        $bio = count($langs) > 0
            ? __('marketplace.card.bio_comm').implode(' · ', array_slice($langs, 0, 4)).(count($langs) > 4 ? '…' : '')
            : __('marketplace.card.bio_fallback');
    }

    $prices = [];
    if ($group && $group->daily_price !== null) {
        $prices[] = (int) $group->daily_price;
    }
    if ($private && $private->daily_price !== null) {
        $prices[] = (int) $private->daily_price;
    }
    $minPrice = count($prices) > 0 ? min($prices) : null;
    $confirmed = (int) ($profile->confirmed_bookings_count ?? 0);
    $reviewsCount = (int) ($profile->booking_reviews_count ?? 0);
    $avgRating = $profile->average_rating !== null ? round((float) $profile->average_rating, 1) : null;
@endphp

<li class="h-full">
    <a
        href="{{ $href }}"
        class="group relative flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-md shadow-slate-900/5 ring-1 ring-slate-100/80 transition duration-300 hover:-translate-y-1 hover:border-brand-200/90 hover:shadow-xl hover:shadow-brand-900/10 hover:ring-brand-100/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
        aria-label="{{ __('marketplace.card.view_profile_aria', ['name' => $profile->user->name]) }}"
    >
        <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-400 via-brand-500 to-amber-400 opacity-0 transition group-hover:opacity-100" aria-hidden="true"></span>

        <div class="relative flex gap-4 p-5 sm:p-6">
            <div class="relative shrink-0">
                <img
                    src="{{ route('layanan.photo', $profile) }}"
                    alt=""
                    width="96"
                    height="96"
                    class="h-24 w-24 rounded-2xl bg-slate-100 object-cover shadow-lg ring-2 ring-white sm:h-[5.5rem] sm:w-[5.5rem]"
                    loading="lazy"
                    onerror='this.onerror=null; this.src={!! json_encode($fallbackSvg) !!}'
                >
                <span class="absolute -bottom-1 -right-1 flex items-center gap-0.5 rounded-full bg-emerald-500 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white shadow-md ring-2 ring-white" title="{{ __('marketplace.card.verified_title') }}">
                    <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                    {{ __('marketplace.card.verified_badge') }}
                </span>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-2">
                    <h2 class="text-lg font-bold leading-snug text-slate-900 transition-colors group-hover:text-brand-800">{{ $profile->user->name }}</h2>
                </div>
                <p class="mt-1.5 line-clamp-3 text-sm leading-relaxed text-slate-600">{{ $bio }}</p>
                @if ($profile->languagesForDisplay() !== [])
                    <p class="mt-2 flex flex-wrap gap-1.5">
                        @foreach (array_slice($profile->languagesForDisplay(), 0, 4) as $lang)
                            <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">{{ $lang }}</span>
                        @endforeach
                    </p>
                @endif
            </div>
        </div>

        <div class="mt-auto border-t border-slate-100 bg-gradient-to-br from-slate-50/90 to-white px-5 py-4 sm:px-6">
            <div class="flex flex-wrap items-center gap-2">
                @if ($group)
                    <span class="inline-flex items-center rounded-lg bg-brand-100 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-brand-900">{{ MuthowifServiceType::Group->label() }}</span>
                @endif
                @if ($private)
                    <span class="inline-flex items-center rounded-lg bg-amber-100 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-amber-950">{{ MuthowifServiceType::PrivateJamaah->label() }}</span>
                @endif
                @if (! $group && ! $private)
                    <span class="text-xs text-slate-500">{{ __('marketplace.card.package_unset') }}</span>
                @endif
            </div>
            <div class="mt-3 flex flex-wrap items-end justify-between gap-3">
                <div>
                    @if ($minPrice !== null)
                        <p class="text-xs font-medium text-slate-500">{{ __('marketplace.card.from') }}</p>
                        <p class="text-lg font-bold text-brand-700">Rp {{ IndonesianNumber::formatThousands((string) $minPrice) }}<span class="text-sm font-semibold text-slate-500">{{ __('common.per_day') }}</span></p>
                    @else
                        <p class="text-sm text-slate-500">{{ __('marketplace.card.price_contact') }}</p>
                    @endif
                    @if ($confirmed > 0)
                        <p class="mt-1 text-xs text-slate-500">{{ __('marketplace.card.bookings_confirmed', ['count' => $confirmed]) }}</p>
                    @else
                        <p class="mt-1 text-xs text-slate-500">{{ __('marketplace.card.new_marketplace') }}</p>
                    @endif
                    @if ($reviewsCount > 0 && $avgRating !== null)
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="flex gap-0.5" aria-hidden="true">
                                @for ($s = 1; $s <= 5; $s++)
                                    <svg class="h-3.5 w-3.5 {{ $s <= (int) round($avgRating) ? 'text-amber-400 drop-shadow-sm' : 'text-slate-200' }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                @endfor
                            </span>
                            <span class="text-xs font-semibold text-slate-700">{{ __('marketplace.card.reviews_line', ['rating' => $avgRating, 'count' => $reviewsCount]) }}</span>
                        </div>
                    @endif
                </div>
                <span class="inline-flex shrink-0 items-center gap-1 rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 px-3 py-2 text-sm font-semibold text-white shadow-md shadow-brand-600/20 transition group-hover:from-brand-500 group-hover:to-brand-600">
                    {{ __('marketplace.card.view_profile') }}
                    <svg class="h-4 w-4 transition group-hover:translate-x-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                </span>
            </div>
        </div>
    </a>
</li>
