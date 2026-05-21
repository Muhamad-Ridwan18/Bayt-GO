<x-app-layout>

    <div class="py-8 sm:py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="flex flex-wrap gap-2">
                @foreach ([
                    'pending' => __('admin.muthowif.tab_pending'),
                    'approved' => __('admin.muthowif.tab_approved'),
                    'rejected' => __('admin.muthowif.tab_rejected'),
                    'all' => __('admin.muthowif.tab_all'),
                ] as $key => $label)
                    <a
                        href="{{ route('admin.muthowif.index', ['status' => $key]) }}"
                        class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium transition
                            {{ $currentStatus === $key
                                ? 'bg-brand-600 text-white'
                                : 'bg-white border border-slate-200 text-slate-700 hover:border-brand-300' }}"
                    >
                        {{ $label }}
                        @if ($key !== 'all' && isset($counts[$key]))
                            <span class="ms-1.5 rounded-full bg-white/20 px-1.5 text-xs {{ $currentStatus === $key ? '' : 'bg-slate-100 text-slate-600' }}">
                                {{ $counts[$key] }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                @forelse ($profiles as $p)
                    <a
                        href="{{ route('admin.muthowif.show', $p) }}"
                        class="group block rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300 hover:shadow-lg sm:p-5"
                    >
                        <div class="grid gap-4 sm:grid-cols-[96px_minmax(0,1fr)_240px] sm:items-center">
                            <div class="flex items-center justify-center">
                                @if ($p->photo_path)
                                    <img
                                        src="{{ route('admin.muthowif.photo', $p) }}"
                                        alt="Foto {{ $p->user->name }}"
                                        class="h-24 w-24 rounded-[1.5rem] object-cover bg-slate-100"
                                    />
                                @else
                                    <div class="flex h-24 w-24 items-center justify-center rounded-[1.5rem] bg-slate-100 text-lg font-semibold text-slate-600">
                                        {{ strtoupper(substr($p->user->name, 0, 2)) }}
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-3">
                                <div class="flex flex-col gap-1">
                                    <p class="text-lg font-semibold text-slate-900">{{ $p->user->name }}</p>
                                    <p class="text-sm text-slate-500">{{ $p->user->email }}</p>
                                </div>

                                <div class="grid gap-2 sm:grid-cols-2 text-sm text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M16 5c1.1 0 2 .9 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7c0-1.1.9-2 2-2h10Zm-5 3.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm3.5 9.5H7" />
                                            </svg>
                                        </span>
                                        <span class="truncate">{{ $p->phone }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 1 1 18 0Z" />
                                                <circle cx="12" cy="10" r="3" />
                                            </svg>
                                        </span>
                                        <span class="truncate">{{ $p->address ? \Illuminate\Support\Str::limit($p->address, 80) : 'Alamat tidak tersedia' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col justify-between gap-4 text-right">
                                <div class="text-sm text-slate-500">
                                    <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M8 7V3M16 7V3M4 11h16M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" />
                                        </svg>
                                        <span>{{ __('admin.muthowif.registered_at', ['datetime' => $p->created_at->translatedFormat('d M Y')]) }}</span>
                                    </div>
                                </div>
                                <span class="inline-flex self-end rounded-full px-4 py-1.5 text-xs font-semibold tracking-wide text-slate-800
                                    @switch($p->verification_status)
                                        @case(\App\Enums\MuthowifVerificationStatus::Pending)
                                            bg-amber-100 text-amber-900
                                            @break
                                        @case(\App\Enums\MuthowifVerificationStatus::Approved)
                                            bg-emerald-100 text-emerald-900
                                            @break
                                        @default
                                            bg-rose-100 text-rose-900
                                    @endswitch
                                ">
                                    {{ $p->verification_status->label() }}
                                </span>
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-slate-500">{{ __('admin.muthowif.empty_filter') }}</p>
                @endforelse
            </div>

            <div class="px-1">
                {{ $profiles->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
