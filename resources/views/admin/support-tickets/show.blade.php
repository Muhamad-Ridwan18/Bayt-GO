<x-app-layout>
    @php
        $openAssignable = $ticket->assigned_admin_id === null || $ticket->assigned_admin_id !== auth()->id();
    @endphp
    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
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
                            <button type="submit" class="inline-flex rounded-xl border border-brand-200 bg-brand-50 px-3 py-2 text-xs font-semibold text-brand-900 transition hover:bg-brand-100">{{ __('support.assign_self') }}</button>
                        </form>
                    @endif
                    <a href="{{ route('admin.support-tickets.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">{{ __('support.back_admin') }}</a>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ session('error') }}</div>
            @endif

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
                        <button type="submit" class="inline-flex rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">{{ __('support.save_ticket') }}</button>
                    </div>
                </form>
            </section>

            <section aria-labelledby="admin-support-thread" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 id="admin-support-thread" class="text-sm font-semibold text-slate-900">{{ __('support.thread_title') }}</h2>
                <div class="mt-4 space-y-4">
                    @foreach ($ticket->messages as $message)
                        @php $staffCard = $message->is_staff; @endphp
                        <div class="rounded-2xl border px-4 py-3 text-sm shadow-sm ring-1
                            {{ $staffCard ? 'border-violet-200/90 bg-gradient-to-br from-violet-50 to-white ml-4 ring-violet-100 sm:ml-12' : 'border-slate-200 bg-slate-50/80 mr-4 ring-slate-100 sm:mr-12' }}">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100/90 pb-2 text-xs font-medium text-slate-600">
                                <span class="flex items-center gap-2 font-semibold text-slate-800">
                                    @if ($message->is_staff)
                                        <span class="inline-flex rounded-lg bg-violet-600 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-white">{{ __('support.staff_label') }}</span>
                                        {{ $message->author?->name }}
                                    @else
                                        {{ $message->author?->name ?? '—' }}
                                        @if ($message->author?->role)
                                            <span class="text-slate-500">({{ $message->author->role->label() }})</span>
                                        @endif
                                    @endif
                                </span>
                                <time datetime="{{ $message->created_at?->toIso8601String() }}" class="tabular-nums text-slate-500">{{ $message->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</time>
                            </div>
                            <div class="mt-3 max-w-none whitespace-pre-wrap text-slate-800">{{ $message->body }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            @if ($canReply && !$ticket->isClosed())
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('support.reply_heading') }}</h2>
                    <form method="POST" action="{{ route('admin.support-tickets.reply', $ticket) }}" class="mt-4 space-y-4">
                        @csrf
                        <textarea name="body" rows="5" required placeholder="{{ __('support.reply_placeholder') }}" class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('body') }}</textarea>
                        <button type="submit" class="inline-flex rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">{{ __('support.reply_heading') }}</button>
                    </form>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
