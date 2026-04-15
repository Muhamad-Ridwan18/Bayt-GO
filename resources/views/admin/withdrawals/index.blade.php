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

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-900">{{ __('admin.withdrawals.summary') }}</h3>
                <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.withdrawals.pending_count') }}</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900 tabular-nums">{{ $pendingCount }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.withdrawals.pending_amount') }}</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900 tabular-nums">
                            Rp {{ \App\Support\IndonesianNumber::formatThousands((string) $pendingAmount) }}
                        </p>
                        <p class="mt-1 text-xs text-slate-600">{{ __('admin.withdrawals.pending_hint') }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-900">{{ __('admin.withdrawals.list_title') }}</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-4 py-3 whitespace-nowrap">{{ __('admin.withdrawals.time') }}</th>
                                <th class="px-4 py-3 whitespace-nowrap">{{ __('admin.withdrawals.muthowif') }}</th>
                                <th class="px-4 py-3 whitespace-nowrap">{{ __('admin.withdrawals.amount') }}</th>
                                <th class="px-4 py-3 whitespace-nowrap">{{ __('admin.withdrawals.destination') }}</th>
                                <th class="px-4 py-3 whitespace-nowrap">{{ __('admin.withdrawals.status') }}</th>
                                <th class="px-4 py-3 whitespace-nowrap">{{ __('admin.withdrawals.proof') }}</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">{{ __('admin.withdrawals.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($withdrawals as $w)
                                @php
                                    $profile = $w->muthowifProfile;
                                    $user = $profile?->user;
                                    $tagClass = match ($w->status) {
                                        'pending_approval' => 'bg-orange-50 text-orange-900 ring-orange-200',
                                        'processing' => 'bg-amber-50 text-amber-900 ring-amber-200',
                                        'succeeded' => 'bg-emerald-50 text-emerald-900 ring-emerald-200',
                                        'failed' => 'bg-red-50 text-red-900 ring-red-200',
                                        default => 'bg-slate-50 text-slate-900 ring-slate-200',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                        {{ $w->requested_at?->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-800 whitespace-nowrap">
                                        {{ $user?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 font-medium text-slate-900 whitespace-nowrap">
                                        Rp {{ \App\Support\IndonesianNumber::formatThousands((string) $w->amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-800 whitespace-nowrap">
                                        {{ $w->beneficiary_bank }} • {{ $w->beneficiary_account }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $tagClass }}">
                                            {{ $w->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($w->transfer_proof_path)
                                            <a href="{{ asset('storage/'.$w->transfer_proof_path) }}" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-brand-700 hover:text-brand-800 underline">
                                                {{ __('admin.withdrawals.view_proof') }}
                                            </a>
                                        @else
                                            <span class="text-xs text-slate-400">{{ __('admin.withdrawals.proof_missing') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        @if ($w->status === 'pending_approval')
                                            <form method="POST" action="{{ route('admin.withdrawals.approve', $w) }}" onsubmit="return confirm(@json(__('admin.withdrawals.approve_confirm')));">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                                                    {{ __('admin.withdrawals.approve') }}
                                                </button>
                                            </form>
                                        @elseif ($w->status === 'processing')
                                            <div class="flex flex-col items-end gap-2">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                                                    data-proof-button
                                                    data-action="{{ route('admin.withdrawals.mark_transferred', $w) }}"
                                                    data-name="{{ $user?->name ?? 'Muthowif' }}"
                                                    data-amount="Rp {{ \App\Support\IndonesianNumber::formatThousands((string) $w->amount) }}"
                                                >
                                                    {{ __('admin.withdrawals.mark_transferred') }}
                                                </button>
                                                <form method="POST" action="{{ route('admin.withdrawals.mark_transfer_failed', $w) }}" onsubmit="return confirm(@json(__('admin.withdrawals.fail_confirm')));">
                                                    @csrf
                                                    <button type="submit" class="text-xs font-semibold text-red-700 hover:text-red-900 underline">
                                                        {{ __('admin.withdrawals.mark_failed') }}
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-500">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-500">
                                        {{ __('admin.withdrawals.empty_table') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $withdrawals->links() }}
                </div>
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
