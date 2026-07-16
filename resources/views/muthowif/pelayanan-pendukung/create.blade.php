<x-app-layout>
    <x-ui.app-page>
        <x-page-container class="ui-stack-compact">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-slate-900">{{ __('layanan_pendukung.create_title') }}</h1>
                        <p class="mt-1 text-sm text-slate-600">{{ __('layanan_pendukung.manage_lead') }}</p>
                    </div>
                    <a href="{{ route('muthowif.pelayanan-pendukung.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">
                        ← {{ __('layanan_pendukung.back_to_list') }}
                    </a>
                </div>

                <form method="POST" action="{{ route('muthowif.pelayanan-pendukung.store') }}" class="mt-6 space-y-6">
                    @csrf
                    @include('muthowif.pelayanan-pendukung.partials.form', [
                        'package' => null,
                        'categories' => $categories,
                    ])
                    <div class="flex flex-wrap gap-3">
                        <x-submit-button class="rounded-xl bg-baytgo px-5 py-2.5 text-sm font-semibold text-white hover:bg-baytgo-800">
                            {{ __('layanan_pendukung.save_package') }}
                        </x-submit-button>
                        <a href="{{ route('muthowif.pelayanan-pendukung.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            {{ __('layanan_pendukung.cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </x-page-container>
    </x-ui.app-page>
</x-app-layout>
