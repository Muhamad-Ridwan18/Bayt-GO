@php
    use App\Support\IndonesianNumber;
@endphp

<x-app-layout>
    <x-ui.app-page>
        <x-page-container class="ui-stack-compact">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-slate-900">{{ __('layanan_pendukung.list_title') }}</h1>
                        <p class="mt-1 text-sm text-slate-600">{{ __('layanan_pendukung.manage_lead') }}</p>
                    </div>
                    <a href="{{ route('muthowif.pelayanan-pendukung.create') }}" class="inline-flex items-center justify-center rounded-xl bg-baytgo px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-baytgo-800">
                        {{ __('layanan_pendukung.add_package') }}
                    </a>
                </div>

                @if (session('status'))
                    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($packages->isEmpty())
                    <div class="mt-6 rounded-xl border border-dashed border-slate-300 bg-slate-50/80 px-6 py-12 text-center">
                        <p class="text-base font-semibold text-slate-900">{{ __('layanan_pendukung.list_empty') }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ __('layanan_pendukung.list_empty_lead') }}</p>
                        <a href="{{ route('muthowif.pelayanan-pendukung.create') }}" class="mt-4 inline-flex rounded-xl bg-baytgo px-4 py-2.5 text-sm font-semibold text-white hover:bg-baytgo-800">
                            {{ __('layanan_pendukung.add_package') }}
                        </a>
                    </div>
                @else
                    <ul class="mt-6 space-y-3">
                        @foreach ($packages as $package)
                            <li class="flex flex-col gap-3 rounded-xl border border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="truncate text-base font-bold text-slate-900">{{ $package->name }}</h2>
                                        @if ($package->category)
                                            <span class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-800 ring-1 ring-emerald-200/80">
                                                {{ $package->category->label() }}
                                            </span>
                                        @endif
                                        @if ($package->is_active)
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-800">{{ __('layanan_pendukung.status_active') }}</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-600">{{ __('layanan_pendukung.status_inactive') }}</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm font-semibold text-baytgo">
                                        Rp {{ IndonesianNumber::formatThousands((string) (int) $package->price) }}
                                        <span class="font-medium text-slate-500">{{ __('layanan_pendukung.flat_price') }}</span>
                                    </p>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        {{ __('layanan_pendukung.meta_pilgrims_range', ['min' => $package->min_pilgrims, 'max' => $package->max_pilgrims]) }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('muthowif.pelayanan-pendukung.edit', $package) }}" class="inline-flex rounded-xl border border-slate-200 px-3.5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                        {{ __('layanan_pendukung.edit_package') }}
                                    </a>
                                    <form method="POST" action="{{ route('muthowif.pelayanan-pendukung.destroy', $package) }}" onsubmit="return confirm(@json(__('layanan_pendukung.delete_confirm')));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex rounded-xl border border-red-200 px-3.5 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                                            {{ __('layanan_pendukung.delete_package') }}
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </x-page-container>
    </x-ui.app-page>
</x-app-layout>
