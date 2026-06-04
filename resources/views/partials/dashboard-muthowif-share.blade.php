@php
    /** @var \App\Models\MuthowifProfile $mp */
    $userInitial = mb_strtoupper(mb_substr(Auth::user()->name, 0, 1));
@endphp

<div
    x-data="{ copied: false, copiedCode: false }"
    class="overflow-hidden rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50/90 to-white shadow-sm ring-1 ring-emerald-100/60"
>
    <div class="border-b border-emerald-100/70 px-5 py-4 sm:px-6">
        <p class="text-sm font-bold text-emerald-950">{{ __('dashboard_muthowif.share_profile_heading') }}</p>
        <p class="mt-1 text-xs text-emerald-800/85">{{ __('dashboard_muthowif.share_profile_sub') }}</p>
    </div>

    <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
        <div class="relative h-12 w-12 shrink-0">
            <img
                src="{{ route('layanan.photo', $mp) }}"
                alt=""
                class="h-full w-full rounded-full border-2 border-white object-cover shadow ring-2 ring-emerald-100"
                onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');"
            />
            <span class="hidden flex h-full w-full items-center justify-center rounded-full bg-emerald-600 text-lg font-bold text-white">{{ $userInitial }}</span>
        </div>
        <div class="min-w-0 flex-1">
            @if (filled($mp->referral_code))
                <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-800">{{ __('dashboard_muthowif.referral_code_heading') }}</p>
                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                    <span class="font-mono text-lg font-bold tracking-wide text-emerald-950">{{ $mp->referral_code }}</span>
                    <button
                        type="button"
                        @click="navigator.clipboard.writeText(@js($mp->referral_code)); copiedCode = true; setTimeout(() => copiedCode = false, 2000)"
                        class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-700"
                    >
                        <span x-text="copiedCode ? '{{ __('dashboard_muthowif.share_copied') }}' : '{{ __('dashboard_muthowif.share_copy') }}'"></span>
                    </button>
                </div>
            @endif
            <div class="mt-2 flex min-w-0 items-center gap-2">
                <span class="min-w-0 flex-1 truncate rounded-lg bg-white/90 px-3 py-1.5 font-mono text-[10px] text-emerald-900 ring-1 ring-emerald-200/80">
                    {{ route('layanan.show', $mp) }}
                </span>
                <button
                    type="button"
                    @click="navigator.clipboard.writeText(@js(route('layanan.show', $mp))); copied = true; setTimeout(() => copied = false, 2000)"
                    class="shrink-0 rounded-lg border border-emerald-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-50"
                >
                    <span x-text="copied ? '{{ __('dashboard_muthowif.share_copied') }}' : '{{ __('dashboard_muthowif.share_copy') }}'"></span>
                </button>
            </div>
        </div>
    </div>

    <div class="border-t border-emerald-100/70 px-5 py-3 sm:px-6">
        <p class="mb-2 text-xs font-semibold text-emerald-800">{{ __('dashboard_muthowif.share_to_sosmed') }}</p>
        <div class="flex flex-wrap gap-2">
            <a href="https://wa.me/?text={{ urlencode(__('dashboard_muthowif.share_wa_text', ['name' => Auth::user()->name, 'url' => route('layanan.show', $mp)])) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl bg-[#25D366] px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[#20bd5a]">WhatsApp</a>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('layanan.show', $mp)) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl bg-[#1877F2] px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[#0e6cd8]">Facebook</a>
            <a href="https://twitter.com/intent/tweet?url={{ urlencode(route('layanan.show', $mp)) }}&text={{ urlencode(__('dashboard_muthowif.share_tweet_text', ['name' => Auth::user()->name])) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-slate-700">X</a>
        </div>
    </div>
</div>
