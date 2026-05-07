@php
    $hint = ($feedHint ?? null) ?? __('admin.moota_webhooks.table_hint');
    $payloadSourceLabels = [
        'parsed_json' => __('admin.moota_webhooks.source_parsed_json'),
        'raw_json_pretty' => __('admin.moota_webhooks.source_raw_json_pretty'),
        'raw_text' => __('admin.moota_webhooks.source_raw_text'),
        'empty' => __('admin.moota_webhooks.source_empty'),
    ];
@endphp

<div
    class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden"
    x-data="mootaWebhookLiveDashboard(@js($rows), @js($realtimeEnabled), @js($payloadSourceLabels))"
>
    <div class="flex flex-col gap-2 px-4 sm:px-6 py-4 border-b border-slate-100 bg-slate-50/70 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm font-medium text-slate-700">{{ $hint }}</p>
        <p class="text-[11px] text-slate-500 leading-snug max-w-xl">
            {{ __('admin.moota_webhooks.payload_legend') }}
        </p>
        @if ($realtimeEnabled)
            <span class="inline-flex items-center gap-2 text-xs font-semibold text-emerald-800 rounded-full px-3 py-1 bg-emerald-50 ring-1 ring-emerald-200 sm:shrink-0">
                <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-600"></span></span>
                {{ __('admin.moota_webhooks.realtime_badge') }}
            </span>
        @endif
    </div>

    <p class="px-6 py-10 text-center text-sm text-slate-500" x-show="rows.length === 0" x-transition.opacity>
        {{ __('admin.moota_webhooks.empty') }}
    </p>

    <div class="overflow-x-auto" x-show="rows.length > 0" x-transition.opacity>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3 whitespace-nowrap">{{ __('admin.moota_webhooks.col_time') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap">{{ __('admin.moota_webhooks.col_ip') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap">{{ __('admin.moota_webhooks.col_signature') }}</th>
                    <th class="px-4 py-3 min-w-[14rem]">{{ __('admin.moota_webhooks.col_mutation') }}</th>
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
                                <div class="space-y-1 max-w-[18rem]">
                                    <p class="font-semibold tabular-nums">
                                        <span x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(Number(row.mutation_summary.amount ?? 0))"></span>
                                        <span class="text-slate-400 mx-1 font-normal">·</span>
                                        <span class="font-normal" x-text="String(row.mutation_summary.type ?? '—')"></span>
                                    </p>
                                    <p class="text-[11px] text-slate-500 font-mono truncate" x-show="row.mutation_summary.mutation_id" x-text="'id: ' + row.mutation_summary.mutation_id"></p>
                                    <p class="text-[11px] font-mono text-emerald-900/90 truncate" x-show="row.mutation_summary.order_id" x-text="'order: ' + row.mutation_summary.order_id"></p>
                                    <p class="text-[11px] font-mono text-slate-600 truncate" x-show="row.mutation_summary.trx_id" x-text="'trx: ' + row.mutation_summary.trx_id"></p>
                                    <p class="text-[11px] font-mono text-brand-800 truncate" x-show="row.mutation_summary.booking_code" x-text="'code: ' + row.mutation_summary.booking_code"></p>
                                    <p class="text-[11px] text-slate-500" x-show="row.mutation_summary.payment_detail_status" x-text="'payment_detail: ' + row.mutation_summary.payment_detail_status"></p>
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
                    <tr x-show="expandedId === row.id" class="bg-slate-50/95 border-t border-slate-100">
                        <td colspan="7" class="px-4 sm:px-6 py-4 align-top">
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('admin.moota_webhooks.panel_request') }}</p>
                                    <dl class="mt-3 space-y-2 text-xs text-slate-700">
                                        <div x-show="row.x_moota_user">
                                            <dt class="text-slate-400 font-medium">X-Moota-User</dt>
                                            <dd class="font-mono break-all" x-text="row.x_moota_user"></dd>
                                        </div>
                                        <div x-show="row.request_meta && row.request_meta.user_agent">
                                            <dt class="text-slate-400 font-medium">User-Agent</dt>
                                            <dd class="break-all text-[11px]" x-text="row.request_meta.user_agent"></dd>
                                        </div>
                                        <div x-show="row.request_meta && row.request_meta.signature_header">
                                            <dt class="text-slate-400 font-medium">Signature (header)</dt>
                                            <dd class="font-mono break-all text-[11px]" x-text="row.request_meta.signature_header"></dd>
                                        </div>
                                    </dl>
                                </div>
                                <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('admin.moota_webhooks.panel_payload_meta') }}</p>
                                    <ul class="mt-3 space-y-1.5 text-xs text-slate-700">
                                        <li class="flex flex-wrap gap-x-2 gap-y-1">
                                            <span class="text-slate-500">{{ __('admin.moota_webhooks.label_source') }}</span>
                                            <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 font-mono text-[11px] text-slate-800" x-text="payloadSourceLabel(row.payload_meta && row.payload_meta.source)"></span>
                                        </li>
                                        <li class="flex flex-wrap gap-x-2 gap-y-1">
                                            <span class="text-slate-500">{{ __('admin.moota_webhooks.label_bytes') }}</span>
                                            <span class="tabular-nums font-medium" x-text="row.payload_meta ? Number(row.payload_meta.bytes || 0).toLocaleString('id-ID') : '0'"></span>
                                        </li>
                                        <li x-show="row.payload_meta && row.payload_meta.truncated" class="text-amber-800 font-medium">
                                            {{ __('admin.moota_webhooks.truncated_notice') }}
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="mt-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">{{ __('admin.moota_webhooks.expand_full_body') }}</p>
                                <template x-if="row.payload_expand && row.payload_expand !== '—'">
                                    <pre class="rounded-xl bg-slate-900 text-emerald-50/95 p-4 text-[11px] leading-relaxed overflow-x-auto max-h-[min(70vh,32rem)] shadow-inner ring-1 ring-black/20"><code class="whitespace-pre font-mono select-text" x-text="row.payload_expand"></code></pre>
                                </template>
                                <template x-if="!row.payload_expand || row.payload_expand === '—'">
                                    <div class="rounded-xl border border-dashed border-amber-200 bg-amber-50/50 px-4 py-3 text-sm text-amber-950">
                                        <p>{{ __('admin.moota_webhooks.no_payload_body') }}</p>
                                        <p class="mt-1 text-xs text-amber-900/80" x-show="row.parse_error" x-text="'Parse: ' + row.parse_error"></p>
                                    </div>
                                </template>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
