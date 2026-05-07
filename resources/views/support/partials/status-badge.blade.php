@php
    /** @var \App\Enums\SupportTicketStatus $status */
    $map = [
        'open' => 'bg-sky-50 text-sky-800 ring-sky-200',
        'in_progress' => 'bg-amber-50 text-amber-900 ring-amber-200',
        'awaiting_customer' => 'bg-violet-50 text-violet-900 ring-violet-200',
        'resolved' => 'bg-emerald-50 text-emerald-900 ring-emerald-200',
        'closed' => 'bg-slate-100 text-slate-700 ring-slate-200',
    ];
    $cls = $map[$status->value] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
@endphp
<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $cls }}">{{ $status->label() }}</span>
