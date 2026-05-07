<x-app-layout>
    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-2xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-900 via-brand-900 to-amber-950 p-8 text-white shadow-xl ring-1 ring-white/10">
                <div class="relative">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-200/90">{{ __('admin.appearance.badge') }}</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight">{{ __('admin.appearance.title') }}</h1>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/80">{{ __('admin.appearance.subtitle') }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm hover:bg-brand-50">
                            {{ __('admin.appearance.back_dashboard') }}
                        </a>
                    </div>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    {{ session('error') }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('admin.appearance.current_heading') }}</h2>

                @if ($logoUrl)
                    <div class="mt-4 flex items-center gap-4 rounded-xl bg-slate-50 p-4">
                        <img src="{{ $logoUrl }}" alt="" class="h-16 w-auto max-w-[200px] object-contain">
                    </div>
                @else
                    <p class="mt-3 text-sm text-slate-600">{{ __('admin.appearance.none') }}</p>
                @endif

                <p class="mt-4 text-xs leading-relaxed text-slate-500">{{ __('admin.appearance.storage_note') }}</p>

                <form method="post" action="{{ route('admin.site-appearance.update') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="logo" :value="__('admin.appearance.field_logo')" />
                        <input
                            id="logo"
                            name="logo"
                            type="file"
                            accept=".png,.jpg,.jpeg,.webp,.svg,image/png,image/jpeg,image/webp,image/svg+xml"
                            class="mt-2 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-800 hover:file:bg-brand-100"
                        >
                        <p class="mt-1.5 text-xs text-slate-500">{{ __('admin.appearance.hint_format', ['max' => 2048]) }}</p>
                        <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                    </div>

                    @if ($logoUrl)
                        <div class="flex items-start gap-3 rounded-xl border border-amber-100 bg-amber-50/80 p-4">
                            <input id="remove_logo" name="remove_logo" type="checkbox" value="1" class="mt-1 rounded border-amber-300 text-brand-600 focus:ring-brand-500">
                            <label for="remove_logo" class="text-sm text-amber-950">{{ __('admin.appearance.remove_label') }}</label>
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-3">
                        <x-primary-button type="submit">{{ __('admin.appearance.save') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
