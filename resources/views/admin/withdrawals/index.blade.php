<x-app-layout>
    <div class="py-8 sm:py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    {{ session('error') }}
                </div>
            @endif

            <div 
                x-data="adminWithdrawalsLive({ 
                    fragmentUrl: '{{ route('admin.withdrawals.fragment') }}',
                    toastLabel: '{{ __('admin.withdrawals.new_request_toast') }}'
                })"
                x-ref="liveRoot"
            >
                @include('admin.withdrawals._table')
            </div>
        </div>
    </div>

    <div id="proof-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">{{ __('admin.withdrawals.modal_title') }}</h3>
                    <p id="proof-modal-subtitle" class="mt-1 text-sm text-slate-600">{{ __('admin.withdrawals.modal_subtitle') }}</p>
                </div>
                <button type="button" id="proof-modal-close" class="rounded-lg px-2 py-1 text-slate-500 hover:bg-slate-100 hover:text-slate-700">✕</button>
            </div>

            <form id="proof-modal-form" method="POST" action="" enctype="multipart/form-data" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label for="transfer_proof" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.withdrawals.upload_label') }}</label>
                    <input id="transfer_proof" type="file" name="transfer_proof" accept="image/png,image/jpeg,image/webp,application/pdf" required class="mt-2 block w-full text-xs text-slate-600 file:mr-2 file:rounded-lg file:border-0 file:bg-slate-100 file:px-2 file:py-1 file:font-semibold file:text-slate-700">
                    <p class="mt-1 text-xs text-slate-500">{{ __('admin.withdrawals.upload_hint') }}</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" id="proof-modal-cancel" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        {{ __('admin.withdrawals.cancel') }}
                    </button>
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                        {{ __('admin.withdrawals.submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

<script>
    (() => {
        const modal = document.getElementById('proof-modal');
        const form = document.getElementById('proof-modal-form');
        const subtitle = document.getElementById('proof-modal-subtitle');
        const closeBtn = document.getElementById('proof-modal-close');
        const cancelBtn = document.getElementById('proof-modal-cancel');
        const fileInput = document.getElementById('transfer_proof');
        const proofSubtitleTemplate = @json(__('admin.withdrawals.proof_subtitle_template'));

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            form.reset();
        }

        document.querySelectorAll('[data-proof-button]').forEach((button) => {
            button.addEventListener('click', () => {
                const action = button.getAttribute('data-action');
                const name = button.getAttribute('data-name') || 'Muthowif';
                const amount = button.getAttribute('data-amount') || '';
                form.setAttribute('action', action || '');
                subtitle.textContent = proofSubtitleTemplate.replace('__NAME__', name).replace('__AMOUNT__', amount);
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                setTimeout(() => fileInput.focus(), 50);
            });
        });

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (event) => {
            if (event.target === modal) closeModal();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });
    })();
</script>
