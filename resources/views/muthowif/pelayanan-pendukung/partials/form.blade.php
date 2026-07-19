@props([
    'package' => null,
    'categories',
    'prefillCategory' => null,
])

@php
    use App\Enums\SupportPackageCategory;

    $categoryValue = old('category', $package?->category?->value ?? ($prefillCategory?->value ?? 'other'));
    $categoryEnum = SupportPackageCategory::tryFrom((string) $categoryValue);
    $categoryLocked = $package !== null || $prefillCategory !== null;
    $isActive = old('is_active', $package?->is_active ?? true);
    if ($isActive === '0' || $isActive === 0 || $isActive === false) {
        $isActive = false;
    } else {
        $isActive = (bool) $isActive;
    }
@endphp

<div class="space-y-5">
    <div class="rounded-2xl border border-slate-200/90 bg-slate-50/70 p-4 ring-1 ring-slate-100/80 sm:p-5">
        <p class="text-xs font-bold uppercase tracking-wider text-baytgo">{{ __('layanan_pendukung.form_section_basic') }}</p>
        <div class="mt-4 space-y-4">
            <div>
                <x-input-label for="category" :value="__('layanan_pendukung.category')" />
                @if ($categoryLocked)
                    <input type="hidden" name="category" value="{{ $categoryValue }}">
                    <div id="category" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm font-medium text-slate-700">
                        {{ $categoryEnum?->label() ?? $categoryValue }}
                    </div>
                @else
                    <select id="category" name="category" required class="mt-1.5 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-baytgo focus:ring-baytgo">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->value }}" @selected($categoryValue === $cat->value)>{{ $cat->label() }}</option>
                        @endforeach
                    </select>
                @endif
                <x-input-error class="mt-2" :messages="$errors->get('category')" />
            </div>

            <div>
                <x-input-label for="name" :value="__('layanan_pendukung.package_name')" />
                <x-text-input id="name" name="name" type="text" class="mt-1.5 block w-full border-slate-300 bg-white focus:border-baytgo focus:ring-baytgo" required
                              :value="old('name', $package?->name)" :placeholder="__('layanan_pendukung.package_name_placeholder')" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="description" :value="__('layanan_pendukung.package_description')" />
                <textarea id="description" name="description" rows="4" class="mt-1.5 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-baytgo focus:ring-baytgo" placeholder="{{ __('layanan_pendukung.package_description_placeholder') }}">{{ old('description', $package?->description) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('description')" />
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200/90 bg-slate-50/70 p-4 ring-1 ring-slate-100/80 sm:p-5">
        <p class="text-xs font-bold uppercase tracking-wider text-baytgo">{{ __('layanan_pendukung.form_section_pricing') }}</p>
        <div class="mt-4 space-y-4">
            <div>
                <x-input-label for="price" :value="__('layanan_pendukung.package_price')" />
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm font-semibold text-slate-500">Rp</span>
                    <x-text-input id="price" name="price" type="text" inputmode="numeric" class="block w-full border-slate-300 bg-white pl-10 focus:border-baytgo focus:ring-baytgo" required
                                  :value="old('price', $package ? (string) (int) $package->price : '')" placeholder="350000" />
                </div>
                <p class="mt-1.5 text-xs text-slate-500">{{ __('layanan_pendukung.price_hint') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('price')" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="min_pilgrims" :value="__('layanan_pendukung.min_pilgrims')" />
                    <x-text-input id="min_pilgrims" name="min_pilgrims" type="number" min="1" class="mt-1.5 block w-full border-slate-300 bg-white focus:border-baytgo focus:ring-baytgo"
                                  :value="old('min_pilgrims', $package?->min_pilgrims ?? 1)" />
                    <x-input-error class="mt-2" :messages="$errors->get('min_pilgrims')" />
                </div>
                <div>
                    <x-input-label for="max_pilgrims" :value="__('layanan_pendukung.max_pilgrims')" />
                    <x-text-input id="max_pilgrims" name="max_pilgrims" type="number" min="1" class="mt-1.5 block w-full border-slate-300 bg-white focus:border-baytgo focus:ring-baytgo"
                                  :value="old('max_pilgrims', $package?->max_pilgrims ?? 10)" />
                    <x-input-error class="mt-2" :messages="$errors->get('max_pilgrims')" />
                </div>
            </div>
        </div>
    </div>

    <label for="is_active" class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-100/80 transition hover:border-baytgo/30 sm:p-5">
        <div class="min-w-0">
            <p class="text-sm font-bold text-slate-900">{{ __('layanan_pendukung.is_active') }}</p>
            <p class="mt-0.5 text-xs text-slate-500">{{ __('layanan_pendukung.is_active_hint') }}</p>
        </div>
        <input type="hidden" name="is_active" value="0">
        <input id="is_active" type="checkbox" name="is_active" value="1" class="h-5 w-5 rounded border-slate-300 text-baytgo shadow-sm focus:ring-baytgo"
               @checked($isActive)>
    </label>
</div>
