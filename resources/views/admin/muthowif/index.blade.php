<x-app-layout>
    <div class="ui-page-y-compact">
        <x-page-container>

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

            {{-- Tabs --}}
            <div class="mb-6 overflow-x-auto">
                <div class="flex w-max gap-2 rounded-2xl border border-slate-200 bg-white p-1 shadow-sm">
                    @foreach ([
        'pending' => __('admin.muthowif.tab_pending'),
        'approved' => __('admin.muthowif.tab_approved'),
        'rejected' => __('admin.muthowif.tab_rejected'),
        'all' => __('admin.muthowif.tab_all'),
    ] as $key => $label)
                        <a href="{{ route('admin.muthowif.index', ['status' => $key]) }}"
                            class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition-all duration-200
                            {{ $currentStatus === $key
                                ? 'bg-brand-600 text-white shadow-sm'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            {{ $label }}

                            @if ($key !== 'all' && isset($counts[$key]))
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-semibold
                                    {{ $currentStatus === $key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $counts[$key] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <div
                x-data="muthowifVerificationLive({
                    listenAdmin: true,
                    fragmentUrl: @js(route('admin.muthowif.index.live-fragment')),
                })"
            >
            <div x-ref="liveRoot">
            @if ($profiles->count())
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    @foreach ($profiles as $p)
                        <a href="{{ route('admin.muthowif.show', $p) }}"
                            class="group flex h-full flex-col rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">

                            {{-- TOP --}}
                            <div class="flex items-start gap-4">

                                {{-- PHOTO --}}
                                <div class="shrink-0">
                                    @if ($p->photo_path)
                                        <img src="{{ route('admin.muthowif.photo', $p) }}" alt="{{ $p->user->name }}"
                                            class="h-20 w-20 rounded-2xl object-cover">
                                    @else
                                        <div
                                            class="flex h-20 w-20 items-center justify-center rounded-2xl bg-slate-100 text-lg font-bold text-slate-700">
                                            {{ strtoupper(substr($p->user->name, 0, 2)) }}
                                        </div>
                                    @endif
                                </div>

                                {{-- INFO --}}
                                <div class="min-w-0 flex-1">

                                    {{-- STATUS --}}
                                    <div class="mb-2">
                                        <span
                                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold
                            @switch($p->verification_status)
                                @case(\App\Enums\MuthowifVerificationStatus::Pending)
                                    bg-amber-100 text-amber-800
                                    @break

                                @case(\App\Enums\MuthowifVerificationStatus::Approved)
                                    bg-emerald-100 text-emerald-800
                                    @break

                                @default
                                    bg-red-100 text-red-800
                            @endswitch
                        ">
                                            {{ $p->verification_status->label() }}
                                        </span>
                                    </div>

                                    {{-- NAME --}}
                                    <h2
                                        class="break-words text-base font-semibold text-slate-900 transition group-hover:text-brand-600">
                                        {{ $p->user->name }}
                                    </h2>

                                    {{-- EMAIL --}}
                                    <p class="mt-1 break-all text-sm text-slate-500">
                                        {{ $p->user->email }}
                                    </p>
                                </div>
                            </div>

                            {{-- CONTENT --}}
                            <div class="mt-5 space-y-3 border-t border-slate-100 pt-4 text-sm">

                                {{-- PHONE --}}
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 text-slate-400">
                                        📞
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs text-slate-400">
                                            WhatsApp
                                        </p>

                                        <p class="break-all text-slate-700">
                                            {{ $p->phone ?: '-' }}
                                        </p>
                                    </div>
                                </div>

                                {{-- DATE --}}
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 text-slate-400">
                                        📅
                                    </div>

                                    <div>
                                        <p class="text-xs text-slate-400">
                                            Registered
                                        </p>

                                        <p class="text-slate-700">
                                            {{ $p->created_at->translatedFormat('d M Y') }}
                                        </p>
                                    </div>
                                </div>

                                {{-- ADDRESS --}}
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 text-slate-400">
                                        📍
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs text-slate-400">
                                            Address
                                        </p>

                                        <p class="line-clamp-2 break-words text-slate-700">
                                            {{ $p->address ?: 'No address available' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- FOOTER --}}
                            <div class="mt-auto pt-5">
                                <div
                                    class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 transition group-hover:bg-brand-50">
                                    <span class="text-sm font-medium text-slate-600 group-hover:text-brand-600">
                                        View Details
                                    </span>

                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 text-slate-400 transition group-hover:translate-x-1 group-hover:text-brand-600"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>

                        </a>
                    @endforeach
                </div>
            @else
                <div
                    class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M9.75 9.75h4.5m-4.5 4.5h4.5M7.5 4.5h9A1.5 1.5 0 0118 6v12a1.5 1.5 0 01-1.5 1.5h-9A1.5 1.5 0 016 18V6a1.5 1.5 0 011.5-1.5z" />
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
                <div
                    class="mt-8 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
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

        </x-page-container>
    </div>
</x-app-layout>
