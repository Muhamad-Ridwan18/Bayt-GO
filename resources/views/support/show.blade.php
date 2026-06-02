<x-app-layout>
    <div class="py-8 sm:py-12">
        <x-page-container class="space-y-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-mono text-sm font-semibold text-slate-900">{{ $ticket->code }}</span>
                        @include('support.partials.status-badge', ['status' => $ticket->status])
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{{ $ticket->priority->label() }}</span>
                        <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-900 ring-1 ring-brand-200">{{ $ticket->category->label() }}</span>
                    </div>
                    <h1 class="mt-2 text-xl font-bold text-slate-900">{{ $ticket->subject }}</h1>
                </div>
                <a href="{{ route('support.index') }}" class="shrink-0 text-sm font-semibold text-brand-700 hover:text-brand-800">{{ __('support.back_list') }}</a>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ session('error') }}</div>
            @endif

            <section aria-labelledby="support-thread-title" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 id="support-thread-title" class="text-sm font-semibold text-slate-900">{{ __('support.thread_title') }}</h2>
                <div class="mt-4 space-y-4">
                    @foreach ($ticket->messages as $message)
                        @php
                            $mine = !$message->is_staff && $message->user_id === auth()->id();
                            $staffCard = $message->is_staff;
                        @endphp
                        <div class="rounded-2xl border px-4 py-3 text-sm shadow-sm ring-1
                            {{ $staffCard ? 'border-violet-200/90 bg-gradient-to-br from-violet-50 to-white ml-4 sm:ml-8 ring-violet-100' : '' }}
                            {{ $mine ? 'border-brand-200/90 bg-gradient-to-br from-brand-50/80 to-white mr-4 ring-brand-100' : '' }}
                            {{ !$staffCard && !$mine ? 'border-slate-200 bg-slate-50/80 mr-8 ring-slate-100' : '' }}">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100/90 pb-2 text-xs font-medium text-slate-600">
                                <span class="flex items-center gap-2 font-semibold text-slate-800">
                                    @if ($message->is_staff)
                                        <span class="inline-flex rounded-lg bg-violet-600 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-white">{{ __('support.staff_label') }}</span>
                                    @elseif ($mine)
                                        {{ __('support.you_label') }}
                                    @else
                                        {{ $message->author?->name ?? 'User' }}
                                    @endif
                                </span>
                                <time datetime="{{ $message->created_at?->toIso8601String() }}" class="tabular-nums text-slate-500">{{ $message->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</time>
                            </div>
                            <div class="prose prose-sm mt-3 max-w-none whitespace-pre-wrap text-slate-800">{{ $message->body }}</div>
                            @include('support.partials.message-attachments', ['message' => $message])
                        </div>
                    @endforeach
                </x-page-container>
            </section>

            @if ($canReply && ! $ticket->isClosed())
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('support.reply_heading') }}</h2>
                    <form method="POST" action="{{ route('support.reply', $ticket) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                        @csrf
                        <textarea name="body" rows="5" required placeholder="{{ __('support.reply_placeholder') }}" class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('body') }}</textarea>
                        <div>
                            <label for="reply-attachments" class="block text-xs font-medium text-slate-600">{{ __('support.attachments_label') }}</label>
                            <p class="mt-0.5 text-xs text-slate-500">{{ __('support.attachments_hint_short') }}</p>
                            <input id="reply-attachments" name="attachments[]" type="file" accept="image/jpeg,image/png,image/gif,image/webp,application/pdf" multiple class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-800 hover:file:bg-slate-200">
                        </div>
                        <button type="submit" class="inline-flex rounded-2xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">{{ __('support.reply_heading') }}</button>
                    </form>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
