<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                Jadwal libur
            </h2>
            <p class="mt-1 text-sm text-slate-500">Tandai tanggal Anda tidak menerima pendampingan. Jamaah akan melihat daftar ini di profil publik Anda.</p>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Tambah tanggal libur</h3>
                <form method="POST" action="{{ route('muthowif.jadwal.store') }}" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @csrf
                    <div>
                        <x-input-label for="blocked_on" value="Tanggal" />
                        <x-text-input id="blocked_on" name="blocked_on" type="date" class="mt-1 block w-full" required
                                      :value="old('blocked_on')" min="{{ now()->toDateString() }}" />
                        <x-input-error class="mt-2" :messages="$errors->get('blocked_on')" />
                    </div>
                    <div>
                        <x-input-label for="note" value="Keterangan (opsional)" />
                        <x-text-input id="note" name="note" type="text" class="mt-1 block w-full" :value="old('note')" placeholder="Contoh: cuti keluarga" />
                        <x-input-error class="mt-2" :messages="$errors->get('note')" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-primary-button type="submit">Simpan tanggal</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-4 bg-slate-50/80">
                    <h3 class="font-semibold text-slate-900">Tanggal libur mendatang</h3>
                </div>
                @if ($blockedDates->isEmpty())
                    <p class="p-6 text-sm text-slate-600">Belum ada tanggal libur. Tambahkan agar jamaah mengetahui ketersediaan Anda.</p>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($blockedDates as $bd)
                            <li class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-6 py-4">
                                <div>
                                    <p class="font-medium text-slate-900">{{ $bd->blocked_on->format('d/m/Y') }}</p>
                                    @if (filled($bd->note))
                                        <p class="text-sm text-slate-500">{{ $bd->note }}</p>
                                    @endif
                                </div>
                                <form action="{{ route('muthowif.jadwal.destroy', $bd) }}" method="post" onsubmit="return confirm('Hapus tanggal ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-700">Hapus</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                    <div class="px-6 py-4 border-t border-slate-100">
                        {{ $blockedDates->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
