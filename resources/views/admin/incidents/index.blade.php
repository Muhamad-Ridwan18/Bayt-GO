<x-app-layout>
    <div class="py-8 sm:py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('incidents.admin.index_title') }}</h2>
                </div>
                <div class="flex gap-2 text-sm">
                    <a href="{{ route('admin.incidents.index', ['status' => 'open']) }}" class="rounded-lg px-3 py-1.5 {{ $status === 'open' ? 'bg-brand-100 font-semibold text-brand-900' : 'bg-slate-100 text-slate-700' }}">Open</a>
                    <a href="{{ route('admin.incidents.index', ['status' => 'all']) }}" class="rounded-lg px-3 py-1.5 {{ $status === 'all' ? 'bg-brand-100 font-semibold text-brand-900' : 'bg-slate-100 text-slate-700' }}">All</a>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Dibuka</th>
                            <th class="px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Kasus</th>
                            <th class="px-4 py-3">Severity</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($incidents as $incident)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-4 py-3 whitespace-nowrap">{{ $incident->opened_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $incident->muthowifBooking?->booking_code }}</td>
                                <td class="px-4 py-3">{{ $incident->case_type->label() }}</td>
                                <td class="px-4 py-3">{{ $incident->severity->label() }}</td>
                                <td class="px-4 py-3">{{ $incident->status->label() }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.incidents.show', $incident) }}" class="font-semibold text-brand-700 hover:text-brand-800">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-slate-500">Tidak ada insiden.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $incidents->links() }}
        </div>
    </div>
</x-app-layout>
