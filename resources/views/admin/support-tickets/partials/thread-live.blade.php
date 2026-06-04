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
            @include('support.partials.message-attachments', ['message' => $message])
        </div>
    @endforeach
</div>
