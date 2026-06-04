@if ($profiles->count())
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        @foreach ($profiles as $p)
            <a href="{{ route('admin.muthowif.show', $p) }}"
                class="group flex h-full flex-col rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                <div class="flex items-start gap-4">
                    <div class="shrink-0">
                        @if ($p->photo_path)
                            <img src="{{ route('admin.muthowif.photo', $p) }}" alt="{{ $p->user->name }}"
                                class="h-20 w-20 rounded-2xl object-cover">
                        @else
                            <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-slate-100 text-lg font-bold text-slate-700">
                                {{ strtoupper(substr($p->user->name, 0, 2)) }}
                            </div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold
                            @switch($p->verification_status)
                                @case(\App\Enums\MuthowifVerificationStatus::Pending) bg-amber-100 text-amber-800 @break
                                @case(\App\Enums\MuthowifVerificationStatus::Approved) bg-emerald-100 text-emerald-800 @break
                                @default bg-rose-100 text-rose-800
                            @endswitch">
                            {{ $p->verification_status->label() }}
                        </span>
                        <h2 class="mt-2 break-words text-base font-semibold text-slate-900 group-hover:text-brand-600">{{ $p->user->name }}</h2>
                        <p class="mt-1 break-all text-sm text-slate-500">{{ $p->user->email }}</p>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
    <div class="mt-8">{{ $profiles->withQueryString()->links() }}</div>
@else
    <p class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-sm text-slate-600">Tidak ada profil pada filter ini.</p>
@endif
