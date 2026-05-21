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

            <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                @forelse ($profiles as $p)
                    <div>
                        <a
                            href="{{ route('admin.muthowif.show', $p) }}"
                            class="group block rounded-[1.25rem] border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300 hover:shadow-lg sm:p-5 h-full"
                        >
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    @if ($p->photo_path)
                                        <img
                                            src="{{ route('admin.muthowif.photo', $p) }}"
                                            alt="Foto {{ $p->user->name }}"
                                            class="h-20 w-20 rounded-2xl object-cover bg-slate-100"
                                        />
                                    @else
                                        <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-slate-100 text-lg font-semibold text-slate-600">
                                            {{ strtoupper(substr($p->user->name, 0, 2)) }}
                                        </div>
                                    @endif
                                </div>

                                <div class="flex-1">
                                    <p class="font-semibold text-slate-900">{{ $p->user->name }}</p>
                                    <p class="text-sm text-slate-500">{{ $p->user->email }}</p>
                                    <div class="mt-3 grid gap-2 text-sm text-slate-500">
                                        <p class="truncate"><strong class="text-slate-700">WA:</strong> {{ $p->phone }}</p>
                                        <p class="truncate"><strong class="text-slate-700">{{ __('admin.muthowif.registered_at', ['datetime' => $p->created_at->translatedFormat('d M Y')]) }}</strong></p>
                                        <p class="truncate">{{ $p->address ? \Illuminate\Support\Str::limit($p->address, 80) : '—' }}</p>
                                    </div>
                                </div>

                                <div class="ms-3 flex flex-col items-end gap-2">
                                    <span class="text-sm text-slate-500">&nbsp;</span>
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold tracking-wide text-slate-800
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
                    </div>
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
