<x-app-layout>

    <div class="py-8 sm:py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="flex flex-wrap gap-2">
                @foreach (['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'all' => 'Semua'] as $key => $label)
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
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-4 py-4 border-b border-slate-100 last:border-b-0 hover:bg-slate-50/80 transition"
                    >
                        <div>
                            <p class="font-semibold text-slate-900">{{ $p->user->name }}</p>
                            <p class="text-sm text-slate-500">{{ $p->user->email }} · {{ $p->phone }}</p>
                            <p class="text-xs text-slate-400 mt-1">Daftar {{ $p->created_at->translatedFormat('d M Y H:i') }}</p>
                        </div>
                        <span class="inline-flex shrink-0 rounded-lg px-2.5 py-1 text-xs font-medium
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
                    </a>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-slate-500">Tidak ada data untuk filter ini.</p>
                @endforelse
            </div>

            <div class="px-1">
                {{ $profiles->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
