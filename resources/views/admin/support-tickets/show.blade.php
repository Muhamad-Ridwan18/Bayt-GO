<x-app-layout>
    @php
        $openAssignable = $ticket->assigned_admin_id === null || $ticket->assigned_admin_id !== auth()->id();
    @endphp
    <div class="ui-page-y">
        <x-page-container class="ui-stack-compact">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-mono text-sm font-semibold text-slate-900">{{ $ticket->code }}</span>
                        @include('support.partials.status-badge', ['status' => $ticket->status])
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{{ $ticket->priority->label() }}</span>
                        <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-900 ring-1 ring-brand-200">{{ $ticket->category->label() }}</span>
                    </div>
                    <h1 class="mt-2 text-xl font-bold text-slate-900">{{ $ticket->subject }}</h1>
                    <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-xs text-slate-600">
                        <div>
                            <dt class="font-semibold uppercase tracking-wide text-slate-500">{{ __('support.reporter') }}</dt>
                            <dd><span class="font-medium text-slate-900">{{ $ticket->reporter?->name }}</span> · {{ $ticket->reporter?->email }} · {{ $ticket->reporter?->role?->label() }}</dd>
                        </div>
                        @if ($ticket->reporter?->phone)
                            <div>
                                <dt class="font-semibold uppercase tracking-wide text-slate-500">{{ __('support.reporter_phone') }}</dt>
                                <dd class="font-mono text-slate-800">{{ $ticket->reporter->phone }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($openAssignable)
                        <form method="POST" action="{{ route('admin.support-tickets.assign_self', $ticket) }}">
                            @csrf
                            <x-submit-button class="rounded-xl border border-brand-200 bg-brand-50 px-3 py-2 text-xs font-semibold text-brand-900 transition hover:bg-brand-100">{{ __('support.assign_self') }}</x-submit-button>
                        </form>
                    @endif
                    <a href="{{ route('admin.support-tickets.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">{{ __('support.back_admin') }}</a>
                </div>
            </div>

            <section class="rounded-2xl border border-slate-200 bg-slate-50/80 p-5 shadow-inner sm:p-6">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('support.admin_panel') }}</h2>
                <form method="POST" action="{{ route('admin.support-tickets.update', $ticket) }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="ticket-status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('support.status_label') }}</label>
                        <select id="ticket-status" name="status" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @foreach ($statuses as $st)
                                <option value="{{ $st->value }}" @selected(old('status', $ticket->status->value) === $st->value)>{{ $st->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="assigned_admin_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('support.assign_admin') }}</label>
                        <select id="assigned_admin_id" name="assigned_admin_id" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">{{ __('support.assigned_open') }}</option>
                            @foreach ($admins as $adminRow)
                                <option value="{{ $adminRow->id }}" @selected(old('assigned_admin_id', $ticket->assigned_admin_id) === $adminRow->getKey())>{{ $adminRow->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2 flex flex-wrap items-center gap-3">
                        <x-submit-button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">{{ __('support.save_ticket') }}</x-submit-button>
                    </div>
                </form>
            </section>

            <section
                aria-labelledby="admin-support-thread"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"
                x-data="reverbFragmentLive({
                    fragmentUrl: @js(route('admin.support-tickets.show.fragment', $ticket)),
                    listeners: [
                        { channel: 'admin.support-tickets', event: '.support.ticket.updated', match: { field: 'ticket_id', value: @js($ticket->getKey()) } },
                        { channel: @js('App.Models.User.'.auth()->id()), event: '.support.ticket.updated', match: { field: 'ticket_id', value: @js($ticket->getKey()) } },
                    ],
                })"
            >
                <h2 id="admin-support-thread" class="text-sm font-semibold text-slate-900">{{ __('support.thread_title') }}</h2>
                <div x-ref="liveRoot">
                    @include('admin.support-tickets.partials.thread-live', ['ticket' => $ticket])
                </div>
            </section>

            @if ($canReply && !$ticket->isClosed())
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('support.reply_heading') }}</h2>
                    <form method="POST" action="{{ route('admin.support-tickets.reply', $ticket) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                        @csrf
                        <textarea name="body" rows="5" required placeholder="{{ __('support.reply_placeholder') }}" class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('body') }}</textarea>
                        <div>
                            <label for="admin-reply-attachments" class="block text-xs font-medium text-slate-600">{{ __('support.attachments_label') }}</label>
                            <input id="admin-reply-attachments" name="attachments[]" type="file" accept="image/jpeg,image/png,image/gif,image/webp,application/pdf" multiple class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-800 hover:file:bg-slate-200">
                        </div>
                        <x-submit-button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">{{ __('support.reply_heading') }}</x-submit-button>
                    </form>
                </section>
            @endif
        </x-page-container>
    </div>
</x-app-layout>
