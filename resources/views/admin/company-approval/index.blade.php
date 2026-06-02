<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            Persetujuan Akun Perusahaan
        </h2>
    </x-slot>

    <div class="py-12">
        <x-page-container>

            @if (session('status'))
                <div class="mb-4 rounded-lg bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg bg-rose-50 p-4 text-sm font-medium text-rose-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-6 flex flex-wrap gap-2">
                <a href="{{ route('admin.company_approval.index', ['status' => 'pending']) }}" 
                   class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition
                   {{ $currentStatus === 'pending' ? 'bg-brand-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                    Menunggu ({{ $counts['pending'] ?? 0 }})
                </a>
                <a href="{{ route('admin.company_approval.index', ['status' => 'approved']) }}" 
                   class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition
                   {{ $currentStatus === 'approved' ? 'bg-brand-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                    Disetujui ({{ $counts['approved'] ?? 0 }})
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/60">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500 border-b border-slate-200">
                            <tr>
                                <th scope="col" class="px-6 py-4 font-semibold">Nama Perusahaan / Email</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Telepon</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Tipe</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Waktu Daftar</th>
                                <th scope="col" class="px-6 py-4 font-semibold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/70">
                            @forelse ($companies as $company)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900">{{ $company->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $company->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-700">
                                        {{ $company->phone ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200/80">
                                            Perusahaan (B2B)
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs">
                                        {{ $company->created_at?->format('d/m/Y H:i') ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        @if (!$company->is_company_approved)
                                            <form action="{{ route('admin.company_approval.approve', $company) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                                    Setujui
                                                </button>
                                            </form>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600">
                                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                                Disetujui
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                        Tidak ada akun perusahaan dalam status ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($companies->hasPages())
                    <div class="border-t border-slate-200 px-6 py-4">
                        {{ $companies->links() }}
                    </div>
                @endif
            </x-page-container>
        </div>
    </div>
</x-app-layout>
