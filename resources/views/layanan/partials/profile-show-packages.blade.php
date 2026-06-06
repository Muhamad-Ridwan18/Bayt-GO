@php
    use App\Enums\MuthowifServiceType;
    use App\Support\IndonesianNumber;

    $packageFeatures = function ($service): array {
        if (! $service) {
            return [];
        }
        $items = [__('marketplace.show.feature_guidance')];
        if (($service->same_hotel_price_per_day ?? null) !== null && (float) $service->same_hotel_price_per_day > 0) {
            $items[] = __('marketplace.show.feature_hotel');
        }
        if (($service->transport_price_flat ?? null) !== null && (float) $service->transport_price_flat > 0) {
            $items[] = __('marketplace.show.feature_transport');
        }
        if (filled($service->description)) {
            $items[] = __('marketplace.show.feature_description');
        }

        return $items;
    };

    $groupBookUrl = $group ? route('layanan.book', array_merge(['publicProfile' => $profile], $bookQueryParams, ['service_type' => 'group'])) : null;
    $privateBookUrl = $private ? route('layanan.book', array_merge(['publicProfile' => $profile], $bookQueryParams, ['service_type' => 'private'])) : null;
@endphp

<section class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm ring-1 ring-slate-100/80 sm:p-8">
@if ($group || $private)
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700" aria-hidden="true">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 3.75A2.75 2.75 0 018.75 1h2.5A2.75 2.75 0 0114 3.75v.443c.572.055 1.14.122 1.706.2C17.42 4.643 18.75 6.094 18.75 7.875v9.375A2.25 2.25 0 0116.5 19.5h-13A2.25 2.25 0 011.25 17.25V7.875c0-1.781 1.331-3.232 3.044-3.482.566-.078 1.134-.145 1.706-.2V3.75zm6.75 0v.443a48.73 48.73 0 00-6.75 0v-.443a1.25 1.25 0 011.25-1.25h2.5a1.25 1.25 0 011.25 1.25zM4.5 8.25v8.625a.75.75 0 00.75.75h9.75a.75.75 0 00.75-.75V8.25a.75.75 0 00-.75-.75h-9.75a.75.75 0 00-.75.75z" clip-rule="evenodd" /></svg>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ __('marketplace.show.packages_heading') }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ __('marketplace.show.packages_sub') }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-5 lg:grid-cols-2">
            @if ($group)
                @php $groupFeatures = $packageFeatures($group); @endphp
                <article class="flex flex-col overflow-hidden rounded-2xl border-2 border-brand-200 bg-gradient-to-b from-brand-50/80 to-white shadow-sm">
                    <div class="border-b border-brand-100/80 bg-brand-700 px-5 py-3">
                        <p class="text-xs font-bold uppercase tracking-wider text-white/90">{{ MuthowifServiceType::Group->label() }}</p>
                    </div>
                    <div class="flex flex-1 flex-col p-5 sm:p-6">
                        <p class="text-2xl font-bold text-brand-800">
                            @if ($group->daily_price !== null)
                                Rp {{ IndonesianNumber::formatThousands((string) (int) $group->daily_price) }}<span class="text-base font-semibold text-slate-500">{{ __('marketplace.show.per_day') }}</span>
                            @else
                                <span class="text-lg text-slate-500">{{ __('marketplace.card.price_contact') }}</span>
                            @endif
                        </p>
                        @if ($group->min_pilgrims && $group->max_pilgrims)
                            <p class="mt-1 text-sm text-slate-600">{{ __('marketplace.show.pilgrim_range', ['min' => $group->min_pilgrims, 'max' => $group->max_pilgrims]) }}</p>
                        @endif
                        <ul class="mt-4 flex-1 space-y-2">
                            @foreach ($groupFeatures as $feature)
                                <li class="flex items-start gap-2 text-sm text-slate-700">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </article>
            @endif

            @if ($private)
                @php $privateFeatures = $packageFeatures($private); @endphp
                <article class="flex flex-col overflow-hidden rounded-2xl border-2 border-gold/40 bg-gradient-to-b from-amber-50/90 to-white shadow-sm">
                    <div class="border-b border-gold/30 bg-gold px-5 py-3">
                        <p class="text-xs font-bold uppercase tracking-wider text-white">{{ MuthowifServiceType::PrivateJamaah->label() }}</p>
                    </div>
                    <div class="flex flex-1 flex-col p-5 sm:p-6">
                        <p class="text-2xl font-bold text-amber-950">
                            @if ($private->daily_price !== null)
                                Rp {{ IndonesianNumber::formatThousands((string) (int) $private->daily_price) }}<span class="text-base font-semibold text-slate-500">{{ __('marketplace.show.per_day') }}</span>
                            @else
                                <span class="text-lg text-slate-500">{{ __('marketplace.card.price_contact') }}</span>
                            @endif
                        </p>
                        @if ($private->min_pilgrims && $private->max_pilgrims)
                            <p class="mt-1 text-sm text-slate-600">{{ __('marketplace.show.pilgrim_range', ['min' => $private->min_pilgrims, 'max' => $private->max_pilgrims]) }}</p>
                        @endif
                        <ul class="mt-4 flex-1 space-y-2">
                            @foreach ($privateFeatures as $feature)
                                <li class="flex items-start gap-2 text-sm text-slate-700">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-gold" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </article>
            @endif
        </div>
@else
        <p class="mt-4 text-sm text-slate-600">{{ __('marketplace.card.package_unset') }}</p>
@endif
</section>
