<x-app-layout>
    <div class="py-8 sm:py-12">
        <x-page-container class="space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-slate-900">{{ __('support.title') }}</h1>
                    <p class="mt-1 text-sm text-slate-600">{{ __('support.subtitle_index') }}</p>
                </div>
                <a href="{{ route('support.create') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-brand-600 to-brand-700 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-600/25 transition hover:from-brand-700 hover:to-brand-800">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    {{ __('support.new_ticket') }}
                </a>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ session('error') }}</div>
            @endif

            @if ($tickets->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 p-10 text-center text-sm text-slate-600">{{ __('support.empty_reporter') }}</div>
            @else
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('support.table_code') }}</th>
                                    <th class="px-4 py-3">{{ __('support.table_subject') }}</th>
                                    <th class="px-4 py-3">{{ __('support.table_status') }}</th>
                                    <th class="hidden px-4 py-3 sm:table-cell">{{ __('support.table_priority') }}</th>
                                    <th class="px-4 py-3">{{ __('support.table_updated') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('support.view_thread') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($tickets as $ticket)
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-slate-900">{{ $ticket->code }}</td>
                                        <td class="max-w-[12rem] px-4 py-3 text-slate-800 sm:max-w-xl">
                                            <span class="line-clamp-2">{{ $ticket->subject }}</span>
                                            @if(($ticket->messages_count ?? 0) > 0)
                                                <p class="mt-1 text-[11px] text-slate-500">{{ __('support.messages_count', ['count' => (int) $ticket->messages_count]) }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 align-middle">@include('support.partials.status-badge', ['status' => $ticket->status])</td>
                                        <td class="hidden px-4 py-3 text-slate-700 sm:table-cell">{{ $ticket->priority->label() }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">{{ ($ticket->last_activity_at ?? $ticket->updated_at)?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            <a href="{{ route('support.show', $ticket) }}" class="inline-flex rounded-xl border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-900 transition hover:bg-brand-100">{{ __('support.view_thread') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-100 px-4 py-3">{{ $tickets->withQueryString()->links() }}</div>
                </div>
            @endif
        </x-page-container>
    </div>
</x-app-layout>
