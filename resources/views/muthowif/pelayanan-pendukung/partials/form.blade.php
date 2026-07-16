@props([
    'package' => null,
    'categories',
])

@php
    $categoryValue = old('category', $package?->category?->value ?? 'other');
    $isActive = old('is_active', $package?->is_active ?? true);
    if ($isActive === '0' || $isActive === 0 || $isActive === false) {
        $isActive = false;
    } else {
        $isActive = (bool) $isActive;
    }
@endphp

<div class="space-y-4">
    <div>
        <x-input-label for="category" :value="__('layanan_pendukung.category')" />
        <select id="category" name="category" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            @foreach ($categories as $cat)
                <option value="{{ $cat->value }}" @selected($categoryValue === $cat->value)>{{ $cat->label() }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('category')" />
    </div>

    <div>
        <x-input-label for="name" :value="__('layanan_pendukung.package_name')" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full border-slate-300" required
                      :value="old('name', $package?->name)" />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="price" :value="__('layanan_pendukung.package_price')" />
        <x-text-input id="price" name="price" type="text" inputmode="numeric" class="mt-1 block w-full border-slate-300" required
                      :value="old('price', $package ? (string) (int) $package->price : '')" />
        <x-input-error class="mt-2" :messages="$errors->get('price')" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="min_pilgrims" :value="__('layanan_pendukung.min_pilgrims')" />
            <x-text-input id="min_pilgrims" name="min_pilgrims" type="number" min="1" class="mt-1 block w-full border-slate-300"
                          :value="old('min_pilgrims', $package?->min_pilgrims ?? 1)" />
            <x-input-error class="mt-2" :messages="$errors->get('min_pilgrims')" />
        </div>
        <div>
            <x-input-label for="max_pilgrims" :value="__('layanan_pendukung.max_pilgrims')" />
            <x-text-input id="max_pilgrims" name="max_pilgrims" type="number" min="1" class="mt-1 block w-full border-slate-300"
                          :value="old('max_pilgrims', $package?->max_pilgrims ?? 10)" />
            <x-input-error class="mt-2" :messages="$errors->get('max_pilgrims')" />
        </div>
    </div>

    <div>
        <x-input-label for="description" :value="__('layanan_pendukung.package_description')" />
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('description', $package?->description) }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('description')" />
    </div>

    <div class="flex items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input id="is_active" type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500"
               @checked($isActive)>
        <x-input-label for="is_active" :value="__('layanan_pendukung.is_active')" class="!mb-0" />
    </div>
</div>
