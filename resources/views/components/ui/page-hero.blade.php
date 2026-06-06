@props([
    'badge' => null,
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-slate-900 via-brand-900 to-slate-950 p-6 text-white shadow-[0_25px_50px_-12px_rgba(15,23,42,0.4)] ring-1 ring-white/10 sm:rounded-3xl sm:p-8']) }}>
    <div class="pointer-events-none absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'40\' height=\'40\' viewBox=\'0 0 40 40\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.03\'%3E%3Cpath d=\'M20 20h20v20H20zM0 0h20v20H0z\'/%3E%3C/g%3E%3C/svg%3E')] opacity-60" aria-hidden="true"></div>

    <div class="relative flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-start gap-4">
            @if (isset($icon))
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/25" aria-hidden="true">
                    {{ $icon }}
                </span>
            @endif
            <div>
                @if ($badge)
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-200/90">{{ $badge }}</p>
                @endif
                <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/75">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        @if (isset($actions))
            <div class="flex shrink-0 flex-wrap gap-3 self-start">
                {{ $actions }}
            </div>
        @endif
    </div>

    @if (isset($stats))
        <div class="relative mt-8">
            {{ $stats }}
        </div>
    @endif
</div>
