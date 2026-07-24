@php
    use App\Enums\SupportPackageCategory;
    use App\Support\IndonesianNumber;
    use Illuminate\Support\Str;

    $categoryBadge = function (?SupportPackageCategory $category): string {
        return match ($category) {
            SupportPackageCategory::Tawaf => 'bg-emerald-50 text-emerald-800 ring-emerald-200/80',
            SupportPackageCategory::Umrah => 'bg-sky-50 text-sky-900 ring-sky-200/80',
            SupportPackageCategory::Ziarah => 'bg-amber-50 text-amber-950 ring-amber-200/80',
            SupportPackageCategory::Mobility => 'bg-violet-50 text-violet-900 ring-violet-200/80',
            default => 'bg-slate-100 text-slate-700 ring-slate-200/80',
        };
    };

    $activeCount = $packages->where('is_active', true)->count();
    $categoryFilter = $categoryFilter ?? null;
    $createUrl = $categoryFilter
        ? route('muthowif.pelayanan-pendukung.create', ['category' => $categoryFilter->value])
        : route('muthowif.pelayanan-pendukung.create');
    $hubTitle = match ($categoryFilter) {
        SupportPackageCategory::Mobility => __('nav.manage_svc_wheelchair'),
        SupportPackageCategory::Umrah => __('nav.manage_svc_prayer'),
        SupportPackageCategory::Other => __('nav.manage_svc_photo'),
        SupportPackageCategory::Ziarah => __('nav.manage_svc_raudhah'),
        default => __('layanan_pendukung.list_title'),
    };
@endphp

<x-app-layout>
    <x-ui.app-page compact>
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_40%_at_50%_-10%,rgba(15,42,37,0.07),transparent)]"></div>
        <x-page-container class="relative ui-stack-compact">
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-emerald-950 to-baytgo p-5 text-white shadow-lg shadow-baytgo/25 ring-1 ring-white/10 sm:rounded-3xl sm:p-6">
                <div class="pointer-events-none absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>
                <div class="pointer-events-none absolute -right-12 top-0 h-40 w-40 rounded-full bg-emerald-400/20 blur-3xl"></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20" aria-hidden="true">
                            <svg class="h-6 w-6 text-emerald-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-100/90">{{ __('nav.manage_services') }}</p>
                            <h1 class="mt-1 text-xl font-bold tracking-tight text-white sm:text-2xl">{{ $hubTitle }}</h1>
                            <p class="mt-2 max-w-xl text-sm leading-relaxed text-emerald-50/90">{{ __('layanan_pendukung.manage_lead') }}</p>
                            <a href="{{ route('muthowif.kelola-layanan') }}" class="mt-3 inline-flex text-xs font-semibold text-emerald-100/90 underline decoration-emerald-200/40 underline-offset-2 hover:text-white">← {{ __('nav.manage_services') }}</a>
                            @if ($packages->isNotEmpty())
                                <dl class="mt-4 flex flex-wrap gap-3">
                                    <div class="rounded-xl border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-emerald-100/80">{{ __('layanan_pendukung.stat_total') }}</dt>
                                        <dd class="text-lg font-bold text-white">{{ $packages->count() }}</dd>
                                    </div>
                                    <div class="rounded-xl border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-emerald-100/80">{{ __('layanan_pendukung.status_active') }}</dt>
                                        <dd class="text-lg font-bold text-white">{{ $activeCount }}</dd>
                                    </div>
                                </dl>
                            @endif
                        </div>
                    </div>
                    <a href="{{ $createUrl }}" class="inline-flex shrink-0 items-center gap-2 self-start rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-baytgo shadow-sm transition hover:bg-emerald-50">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                        {{ __('layanan_pendukung.add_package') }}
                    </a>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 shadow-sm" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($packages->isEmpty())
                <div class="overflow-hidden rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm ring-1 ring-slate-100/80 sm:rounded-3xl">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-baytgo ring-1 ring-emerald-200/80" aria-hidden="true">
                        <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                    <p class="mt-4 text-lg font-bold text-slate-900">{{ __('layanan_pendukung.list_empty') }}</p>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">{{ __('layanan_pendukung.list_empty_lead') }}</p>
                    <a href="{{ $createUrl }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-baytgo px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-baytgo-800">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                        {{ __('layanan_pendukung.add_package') }}
                    </a>
                </div>
            @else
                <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($packages as $package)
                        <li class="group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80 transition duration-300 hover:-translate-y-0.5 hover:border-baytgo/25 hover:shadow-lg hover:shadow-baytgo/5">
                            <div @class([
                                'h-1.5 w-full',
                                $package->is_active ? 'bg-gradient-to-r from-baytgo to-emerald-400' : 'bg-slate-200',
                            ])></div>
                            <div class="flex flex-1 flex-col p-5">
                                <div class="flex items-start justify-between gap-2">
                                    @if ($package->category)
                                        <span @class(['inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1', $categoryBadge($package->category)])>
                                            {{ $package->category->label() }}
                                        </span>
                                    @else
                                        <span></span>
                                    @endif
                                    @if ($package->is_active)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-800 ring-1 ring-emerald-200/80">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            {{ __('layanan_pendukung.status_active') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600 ring-1 ring-slate-200/80">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                            {{ __('layanan_pendukung.status_inactive') }}
                                        </span>
                                    @endif
                                </div>

                                <h2 class="mt-3 line-clamp-2 text-base font-bold text-slate-900 group-hover:text-baytgo sm:text-lg">{{ $package->name }}</h2>

                                @if (filled($package->description))
                                    <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-slate-600">{{ Str::limit(trim(strip_tags($package->description)), 90) }}</p>
                                @endif

                                <div class="mt-4 grid grid-cols-2 gap-2">
                                    <div class="rounded-xl bg-slate-50 px-3 py-2.5 ring-1 ring-slate-100">
                                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('layanan_pendukung.price_label') }}</p>
                                        <p class="mt-0.5 text-sm font-bold text-baytgo">Rp {{ IndonesianNumber::formatThousands((string) (int) $package->price) }}</p>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 px-3 py-2.5 ring-1 ring-slate-100">
                                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('layanan_pendukung.pilgrim_count') }}</p>
                                        <p class="mt-0.5 text-sm font-bold text-slate-800">{{ $package->min_pilgrims }}–{{ $package->max_pilgrims }}</p>
                                    </div>
                                </div>

                                <div class="mt-auto flex gap-2 border-t border-slate-100 pt-4">
                                    <a href="{{ route('muthowif.pelayanan-pendukung.edit', $package) }}" class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-baytgo px-3 py-2.5 text-sm font-semibold text-white shadow-sm shadow-baytgo/15 transition hover:bg-baytgo-800">
                                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.433 13.917l1.262-3.155A4 4 0 017.58 9.42l6.92-6.918a2.121 2.121 0 013 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 01-.65-.65z" /><path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0010 3H4.75A2.75 2.75 0 002 5.75v9.5A2.75 2.75 0 004.75 18h9.5A2.75 2.75 0 0017 15.25V10a.75.75 0 00-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5z" /></svg>
                                        {{ __('layanan_pendukung.edit_package') }}
                                    </a>
                                    <form method="POST" action="{{ route('muthowif.pelayanan-pendukung.destroy', $package) }}" onsubmit="return confirm(@json(__('layanan_pendukung.delete_confirm')));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-full items-center justify-center rounded-xl border border-red-200 bg-white px-3 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50" title="{{ __('layanan_pendukung.delete_package') }}">
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.226-.038 1.027 12.317A2.75 2.75 0 007.86 20h4.28a2.75 2.75 0 002.742-2.748l1.027-12.317.226.038a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.784 0 1.565.023 2.34.068v.343a41.56 41.56 0 00-4.68 0V4.068A41.4 41.4 0 0110 4zM8.58 7.72a.75.75 0 00-1.5.06l.6 9a.75.75 0 101.5-.06l-.6-9zm5.34.06a.75.75 0 10-1.5-.06l-.6 9a.75.75 0 001.5.06l.6-9z" clip-rule="evenodd" /></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-page-container>
    </x-ui.app-page>
</x-app-layout>
