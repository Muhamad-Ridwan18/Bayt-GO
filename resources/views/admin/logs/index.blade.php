<x-app-layout>

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
                        {{ __('admin.logs.view_lines') }}
                    </a>
                </div>

                <pre class="m-0 p-4 text-xs leading-relaxed text-slate-700 whitespace-pre-wrap overflow-auto max-h-[70vh]">{{ implode("\n", $lines) }}</pre>
            </div>
        </div>
    </div>
</x-app-layout>

