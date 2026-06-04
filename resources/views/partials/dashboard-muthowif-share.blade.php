@php
    /** @var \App\Models\MuthowifProfile $mp */
    $userInitial = mb_strtoupper(mb_substr(Auth::user()->name, 0, 1));
    $profileUrl = route('layanan.show', $mp);
@endphp

<div
    x-data="{ copied: false, copiedCode: false }"
    class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm"
>
    <div class="border-b border-slate-100 px-4 py-3 sm:px-5">
        <p class="text-sm font-bold text-slate-900">{{ __('dashboard_muthowif.share_profile_heading') }}</p>
        <p class="mt-0.5 text-xs text-slate-500">{{ __('dashboard_muthowif.share_profile_sub') }}</p>
    </div>

    <div class="space-y-3 px-4 py-3 sm:px-5">
        @if (filled($mp->referral_code))
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('dashboard_muthowif.referral_yours') }}</p>
                <div class="mt-1.5 flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2 ring-1 ring-slate-100">
                    <span class="min-w-0 flex-1 font-mono text-sm font-bold tracking-wider text-slate-900">{{ $mp->referral_code }}</span>
                    <button
                        type="button"
                        @click="navigator.clipboard.writeText(@js($mp->referral_code)); copiedCode = true; setTimeout(() => copiedCode = false, 2000)"
                        class="shrink-0 rounded-lg border border-slate-200 bg-white px-2 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        <span x-text="copiedCode ? '{{ __('dashboard_muthowif.share_copied') }}' : '{{ __('dashboard_muthowif.share_copy') }}'"></span>
                    </button>
                </div>
            </div>
        @endif
        <div class="flex min-w-0 items-center gap-2">
            <span class="min-w-0 flex-1 truncate rounded-lg bg-slate-50 px-2.5 py-1.5 font-mono text-[10px] text-slate-600 ring-1 ring-slate-100">{{ $profileUrl }}</span>
            <button
                type="button"
                @click="navigator.clipboard.writeText(@js($profileUrl)); copied = true; setTimeout(() => copied = false, 2000)"
                class="shrink-0 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-slate-700 hover:bg-slate-50"
            >
                <span x-text="copied ? '{{ __('dashboard_muthowif.share_copied') }}' : '{{ __('dashboard_muthowif.share_copy') }}'"></span>
            </button>
        </div>
    </div>

    <div class="border-t border-slate-100 px-4 py-3 sm:px-5">
        <p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('dashboard_muthowif.share_to_sosmed') }}</p>
        <div class="flex flex-wrap gap-2">
            <a href="https://wa.me/?text={{ urlencode(__('dashboard_muthowif.share_wa_text', ['name' => Auth::user()->name, 'url' => $profileUrl])) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-lg bg-[#25D366] px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-[#20bd5a]">WhatsApp</a>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($profileUrl) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-lg bg-[#1877F2] px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-[#0e6cd8]">Facebook</a>
            <a href="https://twitter.com/intent/tweet?url={{ urlencode($profileUrl) }}&text={{ urlencode(__('dashboard_muthowif.share_tweet_text', ['name' => Auth::user()->name])) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-slate-700">X</a>
        </div>
    </div>
</div>
