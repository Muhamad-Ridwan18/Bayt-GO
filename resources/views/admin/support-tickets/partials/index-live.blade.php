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
