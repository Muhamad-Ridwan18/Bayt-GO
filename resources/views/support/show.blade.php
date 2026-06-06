<x-app-layout>
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
                </div>
                <a href="{{ route('support.index') }}" class="shrink-0 text-sm font-semibold text-brand-700 hover:text-brand-800">{{ __('support.back_list') }}</a>
            </div>

            <section
                aria-labelledby="support-thread-title"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"
                x-data="reverbFragmentLive({
                    fragmentUrl: @js(route('support.show.fragment', $ticket)),
                    listeners: [
                        { channel: @js('App.Models.User.'.auth()->id()), event: '.support.ticket.updated', match: { field: 'ticket_id', value: @js($ticket->getKey()) } },
                    ],
                })"
            >
                <h2 id="support-thread-title" class="text-sm font-semibold text-slate-900">{{ __('support.thread_title') }}</h2>
                <div x-ref="liveRoot">
                    @include('support.partials.thread-live', ['ticket' => $ticket])
                </div>
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
                        <x-submit-button class="rounded-2xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">{{ __('support.reply_heading') }}</x-submit-button>
                    </form>
                </section>
            @endif
        </x-page-container>
    </div>
</x-app-layout>
