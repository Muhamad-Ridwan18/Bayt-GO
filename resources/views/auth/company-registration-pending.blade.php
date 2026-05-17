<x-guest-layout>
    <div class="flex flex-col items-center justify-center p-6 text-center sm:p-12">
        <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-amber-100 ring-8 ring-amber-50">
            <svg class="h-10 w-10 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        
        <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Menunggu Persetujuan Admin</h2>
        <p class="mt-4 max-w-md text-sm leading-relaxed text-slate-600">
            Akun perusahaan Anda telah berhasil dibuat, namun memerlukan persetujuan dari administrator sebelum dapat digunakan. 
            Mohon tunggu di halaman ini, Anda akan otomatis diarahkan ke halaman login setelah disetujui.
        </p>

        <div class="mt-8 flex items-center gap-3 rounded-full bg-slate-50 px-6 py-3 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-200">
            <svg class="h-5 w-5 animate-spin text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Mengecek status secara real-time...
        </div>

        <div class="mt-8 border-t border-slate-100 pt-8 w-full max-w-md">
            <a href="{{ route('login') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-500">
                Kembali ke Halaman Login &rarr;
            </a>
        </div>
    </div>

    @push('scripts')
        <script type="module">
            document.addEventListener('DOMContentLoaded', () => {
                const pendingId = '{{ $pendingId }}';
                if (window.Echo) {
                    window.Echo.channel(`company.pending.${pendingId}`)
                        .listen('CompanyApproved', (e) => {
                            // Automatically redirect to login page when approved
                            window.location.href = '{{ route("login") }}?approved=1';
                        });
                }
            });
        </script>
    @endpush
</x-guest-layout>
