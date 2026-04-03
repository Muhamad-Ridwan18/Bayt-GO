<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Log viewer</h2>
            <div class="text-sm text-slate-500">
                Tail terakhir {{ $n }} baris
                <a href="{{ url('/logs?n=300') }}" class="underline ml-2 hover:text-slate-700">default</a>
            </div>
            <form method="POST" action="{{ route('admin.logs.clear', ['n' => $n]) }}" class="sm:ml-auto">
                @csrf
                <button
                    type="submit"
                    onclick="return confirm('Yakin ingin membersihkan laravel.log?');"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    Clear log
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif
            @if ($error)
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $error }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                <div class="px-4 py-3 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-800">storage/logs/laravel.log</p>
                    <a href="{{ url('/logs?n=800') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800 underline">
                        Lihat 800 baris
                    </a>
                </div>

                <pre class="m-0 p-4 text-xs leading-relaxed text-slate-700 whitespace-pre-wrap overflow-auto max-h-[70vh]">{{ implode("\n", $lines) }}</pre>
            </div>
        </div>
    </div>
</x-app-layout>

