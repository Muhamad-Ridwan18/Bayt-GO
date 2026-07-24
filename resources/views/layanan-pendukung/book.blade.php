@php
    [$minPilgrims, $maxPilgrims] = array_values($package->pilgrimBounds());
    $startsAtInput = $startsAtInput ?? '';
    $defaultStartsAt = old('starts_at', $startsAtInput !== ''
        ? $startsAtInput
        : now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i'));
    $catalogQuery = array_filter(['starts_at' => $startsAtInput !== '' ? $startsAtInput : null]);
    $showUrl = route('layanan-pendukung.show', array_merge(['supportPackage' => $package], $catalogQuery));
@endphp

<x-marketplace-layout :title="__('layanan_pendukung.book_now').' — '.$package->name">
    <div class="mx-auto max-w-xl ui-stack-compact">
        <a href="{{ $showUrl }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-800">
            ← {{ __('layanan_pendukung.back_to_catalog') }}
        </a>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h1 class="text-xl font-bold text-slate-900">{{ $package->name }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ __('layanan_pendukung.by_muthowif', ['name' => $profile->user->name]) }}</p>

            @guest
                <p class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    <a href="{{ route('login', ['next' => request()->getRequestUri()]) }}" class="font-semibold underline">{{ __('layanan_pendukung.login_to_book') }}</a>
                </p>
            @else
                <form method="POST" action="{{ route('bookings.support.store') }}" class="mt-6 space-y-5">
                    @csrf
                    <input type="hidden" name="support_package_id" value="{{ $package->id }}">

                    <div>
                        <x-input-label for="starts_at" :value="__('layanan_pendukung.starts_at')" />
                        <input id="starts_at" name="starts_at" type="datetime-local" required
                               value="{{ $defaultStartsAt }}"
                               min="{{ now()->format('Y-m-d\TH:i') }}"
                               class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <p class="mt-1 text-xs text-slate-500">{{ __('layanan_pendukung.starts_at_hint') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('starts_at')" />
                    </div>

                    <div>
                        <x-input-label for="pilgrim_count" :value="__('layanan_pendukung.pilgrim_count')" />
                        <x-text-input id="pilgrim_count" name="pilgrim_count" type="number" class="mt-1 block w-full"
                                      :value="old('pilgrim_count', $minPilgrims)" :min="$minPilgrims" :max="$maxPilgrims" required />
                        <x-input-error class="mt-2" :messages="$errors->get('pilgrim_count')" />
                    </div>

                    <div>
                        <x-input-label for="affiliate_code" value="Kode Affiliate (opsional)" />
                        <x-text-input id="affiliate_code" name="affiliate_code" type="text" class="mt-1 block w-full border-slate-300 font-mono uppercase"
                                      :value="old('affiliate_code', \App\Support\AffiliateReferralCapture::code())" maxlength="32" autocomplete="off" placeholder="Contoh: RIDWAN" />
                        <p class="mt-1 text-xs text-slate-500">Masukkan kode affiliate jika Anda datang dari referral.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('affiliate_code')" />
                    </div>

                    <x-input-error :messages="$errors->get('support_package_id')" />

                    <x-submit-button class="w-full rounded-xl bg-baytgo px-5 py-3 text-sm font-semibold text-white shadow-md hover:bg-baytgo-800">
                        {{ __('layanan_pendukung.submit_booking') }}
                    </x-submit-button>
                </form>
            @endguest
        </section>
    </div>
</x-marketplace-layout>
