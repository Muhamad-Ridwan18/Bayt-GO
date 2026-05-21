<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                        Muthowif Verification
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Manage and review all registered muthowif accounts.
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <div class="flex items-center gap-6">
                        <div>
                            <p class="text-xs text-slate-500">Total</p>
                            <p class="text-lg font-semibold text-slate-900">
                                {{ $profiles->total() }}
                            </p>
                        </div>

                        <div class="h-10 w-px bg-slate-200"></div>

                        <div>
                            <p class="text-xs text-slate-500">Pending</p>
                            <p class="text-lg font-semibold text-amber-600">
                                {{ $counts['pending'] ?? 0 }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Alert --}}
            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Tabs --}}
            <div class="mb-6 overflow-x-auto">
                <div class="flex w-max gap-2 rounded-2xl border border-slate-200 bg-white p-1 shadow-sm">
                    @foreach ([
                        'pending' => __('admin.muthowif.tab_pending'),
                        'approved' => __('admin.muthowif.tab_approved'),
                        'rejected' => __('admin.muthowif.tab_rejected'),
                        'all' => __('admin.muthowif.tab_all'),
                    ] as $key => $label)
                        <a
                            href="{{ route('admin.muthowif.index', ['status' => $key]) }}"
                            class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition-all duration-200
                            {{ $currentStatus === $key
                                ? 'bg-brand-600 text-white shadow-sm'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                        >
                            {{ $label }}

                            @if ($key !== 'all' && isset($counts[$key]))
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold
                                    {{ $currentStatus === $key
                                        ? 'bg-white/20 text-white'
                                        : 'bg-slate-100 text-slate-600' }}">
                                    {{ $counts[$key] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Cards --}}
            @if ($profiles->count())
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    @foreach ($profiles as $p)
                        <a
                            href="{{ route('admin.muthowif.show', $p) }}"
                            class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-slate-300 hover:shadow-xl"
                        >

                            {{-- Profile --}}
                            <div class="flex items-start gap-4">
                                <div class="shrink-0">
                                    @if ($p->photo_path)
                                        <img
                                            src="{{ route('admin.muthowif.photo', $p) }}"
                                            alt="{{ $p->user->name }}"
                                            class="h-20 w-20 rounded-2xl object-cover ring-4 ring-slate-100"
                                        >
                                    @else
                                        <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 text-lg font-bold text-slate-700">
                                            {{ strtoupper(substr($p->user->name, 0, 2)) }}
                                        </div>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="mb-1 flex items-start justify-between gap-3">
                                        <h2 class="text-base font-semibold text-slate-900 group-hover:text-brand-600">
                                            {{ $p->user->name }}
                                        </h2>
                                        
                                        {{-- Status Badge --}}
                                        <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-semibold tracking-wide
                                            @switch($p->verification_status)
                                                @case(\App\Enums\MuthowifVerificationStatus::Pending)
                                                    bg-amber-100 text-amber-800
                                                    @break

                                                @case(\App\Enums\MuthowifVerificationStatus::Approved)
                                                    bg-emerald-100 text-emerald-800
                                                    @break

                                                @default
                                                    bg-rose-100 text-rose-800
                                            @endswitch
                                        ">
                                            {{ $p->verification_status->label() }}
                                        </span>
                                    </div>
                                    <p class="mt-1 truncate text-sm text-slate-500">
                                        {{ $p->user->email }}
                                    </p>

                                    <div class="mt-4 space-y-2 text-sm">
                                        <div class="flex items-start gap-2 text-slate-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5h2l3.6 7.59a1 1 0 00.91.58h7.72a1 1 0 00.91-.58L21 5H7" />
                                            </svg>

                                            <span class="truncate">
                                                {{ $p->phone ?: '-' }}
                                            </span>
                                        </div>

                                        <div class="flex items-start gap-2 text-slate-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v11a2 2 0 002 2z" />
                                            </svg>

                                            <span>
                                                {{ $p->created_at->translatedFormat('d M Y') }}
                                            </span>
                                        </div>

                                        <div class="flex items-start gap-2 text-slate-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 12.414a2 2 0 010-2.828l4.243-4.243m0 0A8 8 0 105.343 16.657a8 8 0 0012.314 0z" />
                                            </svg>

                                            <span class="line-clamp-2">
                                                {{ $p->address ?: 'No address available' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Footer --}}
                            <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                                <span class="text-sm font-medium text-slate-500 group-hover:text-brand-600">
                                    View Details
                                </span>

                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400 transition group-hover:translate-x-1 group-hover:text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 9.75h4.5m-4.5 4.5h4.5M7.5 4.5h9A1.5 1.5 0 0118 6v12a1.5 1.5 0 01-1.5 1.5h-9A1.5 1.5 0 016 18V6a1.5 1.5 0 011.5-1.5z" />
                        </svg>
                    </div>

                    <h3 class="text-lg font-semibold text-slate-900">
                        No Data Found
                    </h3>

                    <p class="mt-2 text-sm text-slate-500">
                        {{ __('admin.muthowif.empty_filter') }}
                    </p>
                </div>
            @endif

            {{-- Pagination --}}
            @if ($profiles->hasPages())
                <div class="mt-8 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-slate-500">
                        Showing
                        <span class="font-medium text-slate-700">{{ $profiles->firstItem() ?? 0 }}</span>
                        to
                        <span class="font-medium text-slate-700">{{ $profiles->lastItem() ?? 0 }}</span>
                        of
                        <span class="font-medium text-slate-700">{{ $profiles->total() }}</span>
                        results
                    </div>

                    <div>
                        {{ $profiles->links() }}
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>