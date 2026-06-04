<x-app-layout>
    <div class="relative min-h-[calc(100vh-4rem)] overflow-hidden bg-gradient-to-b from-slate-100 via-slate-50 to-white py-8 sm:py-12">
        <x-page-container class="relative space-y-8">
            @if (session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-700">Campaigns</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">Kelola Campaign</h1>
                    <p class="mt-2 max-w-xl text-sm text-slate-600">Atur dan kelola semua campaign promosi.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300">
                        Kembali ke Pengaturan
                    </a>
                    <a href="{{ route('admin.campaign.create') }}" class="inline-flex items-center rounded-xl bg-baytgo px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-baytgo/20 transition hover:bg-baytgo-800">
                        Campaign Baru
                    </a>
                </x-page-container>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                @if ($campaigns->isEmpty())
                    <p class="p-10 text-center text-sm text-slate-600">Belum ada data campaign.</p>
                @else
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700">Judul</th>
                                <th scope="col" class="px-4 py-3 text-center font-semibold text-slate-700">Status</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700">Tgl Mulai</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700">Tgl Selesai</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold text-slate-700">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($campaigns as $row)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="max-w-xs truncate px-4 py-3 text-slate-900">{{ $row->title }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($row->is_active)
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Aktif</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">Draft / Tidak Aktif</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $row->start_date?->translatedFormat('d M Y H:i') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $row->end_date?->translatedFormat('d M Y H:i') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.campaign.edit', $row) }}" class="rounded-lg px-2 py-1 text-xs font-semibold text-baytgo hover:bg-baytgo/10">Edit</a>
                                            <form action="{{ route('admin.campaign.destroy', $row) }}" method="post" class="inline" onsubmit="return confirm('Hapus campaign ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <x-submit-button class="rounded-lg px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Hapus</x-submit-button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
