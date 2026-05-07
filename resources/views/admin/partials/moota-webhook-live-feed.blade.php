<?php
    $hint = ($feedHint ?? null) ?? __('admin.moota_webhooks.table_hint');
?>

<div
    class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden"
    x-data="mootaWebhookLiveDashboard(@js($rows), @js($realtimeEnabled))"
>
    <div class="flex items-center justify-between gap-4 px-4 sm:px-6 py-4 border-b border-slate-100 bg-slate-50/70">
        <p class="text-sm font-medium text-slate-700">{{ $hint }}</p>
        @if ($realtimeEnabled)
            <span class="inline-flex items-center gap-2 text-xs font-semibold text-emerald-800 rounded-full px-3 py-1 bg-emerald-50 ring-1 ring-emerald-200">
                <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-600"></span></span>
                Realtime · Echo/Reverb
            </span>
        @endif
    </div>

    {{-- Jangan pakai x-cloak di blok utama: jika Alpine gagal init, isi tidak pernah tampil. --}}
    <p class="px-6 py-10 text-center text-sm text-slate-500" x-show="rows.length === 0" x-transition.opacity>
        {{ __('admin.moota_webhooks.empty') }}
    </p>

    <div class="overflow-x-auto" x-show="rows.length > 0" x-transition.opacity>
        {{-- template x-for harus di dalam <tbody>: anak langsung <table> tidak valid HTML & browser memindahkan node sehingga Alpine tidak merender baris. --}}
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3 whitespace-nowrap">{{ __('admin.moota_webhooks.col_time') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap">{{ __('admin.moota_webhooks.col_ip') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap">{{ __('admin.moota_webhooks.col_signature') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap">{{ __('admin.moota_webhooks.col_mutation') }}</th>
                    <th class="px-4 py-3 min-w-[8rem]">{{ __('admin.moota_webhooks.col_parse') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap">{{ __('admin.moota_webhooks.col_hooks') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap text-right">{{ __('admin.moota_webhooks.col_detail') }}</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="row in rows" :key="row.id">
                    <tr class="border-b border-slate-100 bg-white hover:bg-slate-50/60 align-top">
                        <td class="px-4 py-3 text-slate-700 whitespace-nowrap tabular-nums" x-text="row.created_at"></td>
                        <td class="px-4 py-3 text-slate-800 whitespace-nowrap font-mono text-xs" x-text="row.source_ip || '—'"></td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <template x-if="row.signature_verified === true">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-emerald-50 text-emerald-900 ring-1 ring-emerald-200">{{ __('admin.moota_webhooks.sig_ok') }}</span>
                            </template>
                            <template x-if="row.signature_verified === false">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-red-50 text-red-900 ring-1 ring-red-200">{{ __('admin.moota_webhooks.sig_bad') }}</span>
                            </template>
                            <template x-if="row.signature_verified !== true && row.signature_verified !== false">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-slate-100 text-slate-700 ring-1 ring-slate-200">{{ __('admin.moota_webhooks.sig_not_checked') }}</span>
                            </template>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-700">
                            <template x-if="row.mutation_summary">
                                <div class="space-y-1 max-w-[15rem]">
                                    <p class="font-semibold tabular-nums">
                                        <span x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(Number(row.mutation_summary.amount ?? 0))"></span>
                                        <span class="text-slate-400 mx-1 font-normal">·</span>
                                        <span class="font-normal" x-text="String(row.mutation_summary.type ?? '—')"></span>
                                    </p>
                                    <p class="text-[11px] text-slate-500 font-mono truncate" x-show="row.mutation_summary.mutation_id" x-text="row.mutation_summary.mutation_id"></p>
                                    <p class="text-[11px] text-slate-500 line-clamp-2" x-show="Boolean(row.mutation_summary.note || row.mutation_summary.description)" x-text="[row.mutation_summary.note, row.mutation_summary.description].filter(Boolean).join(' · ')"></p>
                                </div>
                            </template>
                            <template x-if="!row.mutation_summary">
                                <span class="text-slate-400">{{ __('admin.moota_webhooks.no_mutation') }}</span>
                            </template>
                        </td>
                        <td
                            class="px-4 py-3 text-xs max-w-[14rem]"
                            :class="row.parse_error ? 'text-red-700 font-medium' : 'text-slate-400'"
                            x-text="row.parse_error || '—'"
                        ></td>
                        <td class="px-4 py-3 text-xs text-slate-600 font-mono break-all">
                            <span x-show="row.x_moota_webhook" x-text="row.x_moota_webhook"></span>
                            <span x-show="!row.x_moota_webhook" class="text-slate-400">—</span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button
                                type="button"
                                class="text-xs font-semibold text-brand-700 hover:text-brand-800"
                                x-on:click="toggleRow(row.id)"
                                x-bind:aria-expanded="expandedId === row.id"
                                x-text="expandedId === row.id ? @js(__('admin.moota_webhooks.collapse')) : @js(__('admin.moota_webhooks.expand'))"
                            ></button>
                        </td>
                    </tr>
                    <tr x-show="expandedId === row.id" class="bg-slate-50/95 border-t border-slate-50">
                        <td colspan="7" class="px-4 sm:px-6 py-4 align-top">
                            <p class="text-xs font-semibold uppercase text-slate-500 mb-2">{{ __('admin.moota_webhooks.expand_payload') }}</p>
                            <pre class="rounded-xl bg-slate-900 text-brand-50 p-4 text-xs overflow-x-auto max-h-[24rem]" x-show="row.payload_preview"><code x-text="row.payload_preview"></code></pre>
                            <p class="text-sm text-slate-500 italic py-4" x-show="!row.payload_preview">{{ __('admin.moota_webhooks.no_payload') }}</p>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
