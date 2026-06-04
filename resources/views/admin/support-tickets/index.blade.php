<x-app-layout>
    <div class="py-8 sm:py-12">
        <x-page-container class="space-y-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-slate-900">{{ __('support.admin_title') }}</h1>
                    <p class="mt-1 text-sm text-slate-600">{{ __('support.admin_subtitle') }}</p>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ session('error') }}</div>
            @endif

            <form method="GET" action="{{ route('admin.support-tickets.index') }}" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="min-w-[14rem] flex-1">
                    <label for="ticket-q" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('support.search_placeholder') }}</label>
                    <input id="ticket-q" type="text" name="q" value="{{ $q }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div class="min-w-[10rem]">
                    <label for="ticket-status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('support.table_status') }}</label>
                    <select id="ticket-status" name="status" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">{{ __('support.filter_all_status') }}</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st->value }}" @selected($statusFilter === $st->value)>{{ $st->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">{{ __('support.apply_filters') }}</button>
                @if ($q !== '' || $statusFilter !== '')
                    <a href="{{ route('admin.support-tickets.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">{{ __('support.clear_filters') }}</a>
                @endif
            </form>

            <div
                x-data="reverbFragmentLive({
                    fragmentUrl: @js(route('admin.support-tickets.index.live-fragment')),
                    appendQuery: true,
                    listeners: [
                        { channel: 'admin.support-tickets', event: '.support.ticket.updated' },
                    ],
                })"
            >
            <div x-ref="liveRoot">
            @if ($tickets->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 p-10 text-center text-sm text-slate-600">{{ __('support.empty_admin') }}</div>
            @else
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('support.table_code') }}</th>
                                    <th class="px-4 py-3">{{ __('support.table_subject') }}</th>
                                    <th class="px-4 py-3">{{ __('support.table_status') }}</th>
                                    <th class="px-4 py-3">{{ __('support.reporter') }}</th>
                                    <th class="px-4 py-3">{{ __('support.assignee') }}</th>
                                    <th class="px-4 py-3">{{ __('support.table_updated') }}</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($tickets as $ticket)
                                    <tr class="hover:bg-slate-50/80 align-top">
                                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold">{{ $ticket->code }}</td>
                                        <td class="max-w-xs px-4 py-3 text-slate-800">
                                            <span class="line-clamp-2 font-medium">{{ $ticket->subject }}</span>
                                            <p class="mt-1 text-[11px] text-slate-500">{{ __('support.messages_count', ['count' => (int) ($ticket->messages_count ?? 0)]) }}</p>
                                        </td>
                                        <td class="px-4 py-3">@include('support.partials.status-badge', ['status' => $ticket->status])</td>
                                        <td class="px-4 py-3 text-xs text-slate-700">
                                            <p class="font-medium text-slate-900">{{ $ticket->reporter?->name }}</p>
                                            <p class="text-slate-500">{{ $ticket->reporter?->role?->label() }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-slate-700">{{ $ticket->assignedAdmin?->name ?? __('support.assigned_open') }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">{{ ($ticket->last_activity_at ?? $ticket->updated_at)?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            <a href="{{ route('admin.support-tickets.show', $ticket) }}" class="inline-flex rounded-xl border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-900 transition hover:bg-brand-100">{{ __('support.view_thread') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-100 px-4 py-3">{{ $tickets->withQueryString()->links() }}</div>
                </div>
            @endif
            </div>
            </div>
        </x-page-container>
    </div>
</x-app-layout>
