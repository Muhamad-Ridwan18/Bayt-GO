<x-app-layout>
    <div class="relative min-h-[calc(100vh-4rem)] overflow-x-hidden bg-slate-50">
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -right-20 top-0 h-72 w-72 rounded-full bg-amber-400/15 blur-3xl"></div>
            <div class="absolute -left-16 bottom-20 h-64 w-64 rounded-full bg-slate-300/20 blur-3xl"></div>
        </div>

        <div class="relative z-10 mx-auto max-w-xl px-4 pb-16 pt-10 sm:px-6">
            <a href="{{ route('bookings.show', $booking) }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-brand-700 transition hover:text-brand-800">
                <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                </svg>
                {{ __('bookings.unconfigured.back') }}
            </a>

            <div class="relative overflow-hidden rounded-2xl border border-amber-200/90 bg-gradient-to-br from-amber-50 via-white to-white p-6 shadow-lg ring-1 ring-amber-100/70">
                <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-400 to-orange-400" aria-hidden="true"></span>
                <div class="relative">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-800 shadow-sm ring-1 ring-amber-200/80">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <p class="mt-4 text-lg font-bold text-amber-950">{{ __('bookings.unconfigured.title') }}</p>
                    <p class="mt-2 text-sm leading-relaxed text-amber-950/90">
                        {!! __('bookings.unconfigured.body') !!}
                    </p>
                    <p class="mt-4 break-all rounded-xl border border-amber-200/80 bg-white/80 px-3 py-2.5 font-mono text-[11px] leading-snug text-amber-950 ring-1 ring-amber-100/80">
                        {{ url('/payments/midtrans/notification') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
