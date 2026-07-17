@php
    use App\Enums\AffiliateBankVerificationStatus;
    use App\Support\AffiliateBankOptions;
    use App\Support\IndonesianNumber;
@endphp
<x-app-layout>
    <div class="ui-page-y">
        <x-page-container class="ui-stack-compact">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ session('error') }}</div>
            @endif

            <div class="rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-900 via-brand-900 to-amber-950 p-8 text-white shadow-xl">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-200">Affiliate Dashboard</p>
                <h1 class="mt-2 text-2xl font-bold">Kode: <span class="font-mono tracking-wide">{{ $affiliate->code }}</span></h1>
                <p class="mt-2 text-sm text-white/80">Status: {{ $affiliate->status->label() }} · Rate {{ number_format($stats['rate'] * 100, 2) }}%</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <input type="text" readonly value="{{ $shareUrl }}" class="min-w-0 flex-1 rounded-xl border-0 bg-white/10 px-3 py-2 text-sm text-white ring-1 ring-white/20" id="affiliate-share-url">
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('affiliate-share-url').value)" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-900">Salin Link</button>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-slate-500">Saldo tersedia</p>
                    <p class="mt-2 text-xl font-bold tabular-nums text-slate-900">Rp {{ IndonesianNumber::formatThousands((string) (int) $stats['available_balance']) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-slate-500">Pending commission</p>
                    <p class="mt-2 text-xl font-bold tabular-nums text-amber-700">Rp {{ IndonesianNumber::formatThousands((string) (int) $stats['pending_commission']) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-slate-500">Booking berhasil</p>
                    <p class="mt-2 text-xl font-bold tabular-nums text-slate-900">{{ $stats['success_booking'] }} / {{ $stats['total_booking'] }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-slate-500">Total withdraw</p>
                    <p class="mt-2 text-xl font-bold tabular-nums text-slate-900">Rp {{ IndonesianNumber::formatThousands((string) (int) $stats['total_withdraw']) }}</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="font-semibold text-slate-900">Rekening payout</h2>
                    <p class="mt-1 text-xs text-slate-500">Harus diverifikasi admin sebelum withdraw.</p>
                    <ul class="mt-4 divide-y divide-slate-100">
                        @forelse ($bankAccounts as $bank)
                            <li class="flex items-start justify-between gap-3 py-3 text-sm">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $bank->bank_name }} · {{ $bank->account_number }}</p>
                                    <p class="text-slate-600">{{ $bank->account_holder }}</p>
                                    <p class="mt-1 text-xs {{ $bank->verification_status === AffiliateBankVerificationStatus::Verified ? 'text-emerald-700' : 'text-amber-700' }}">
                                        {{ $bank->verification_status->label() }}
                                        @if ($bank->is_primary) · Primary @endif
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('affiliate.bank-accounts.destroy', $bank) }}" onsubmit="return confirm('Hapus rekening ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs font-semibold text-red-600">Hapus</button>
                                </form>
                            </li>
                        @empty
                            <li class="py-3 text-sm text-slate-500">Belum ada rekening.</li>
                        @endforelse
                    </ul>
                    <form method="POST" action="{{ route('affiliate.bank-accounts.store') }}" class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                        @csrf
                        <div>
                            <x-input-label for="bank_code" value="Bank" />
                            <select id="bank_code" name="bank_code" required class="mt-1 w-full rounded-xl border-slate-300 text-sm">
                                @foreach (AffiliateBankOptions::all() as $code => $label)
                                    <option value="{{ $code }}" @selected(old('bank_code') === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="account_holder" value="Nama pemilik" />
                            <x-text-input id="account_holder" name="account_holder" class="mt-1 block w-full" :value="old('account_holder')" required />
                        </div>
                        <div>
                            <x-input-label for="account_number" value="Nomor rekening" />
                            <x-text-input id="account_number" name="account_number" class="mt-1 block w-full" :value="old('account_number')" required />
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="is_primary" value="1" class="rounded border-slate-300"> Jadikan primary
                        </label>
                        <x-input-error :messages="$errors->get('bank_code')" />
                        <x-input-error :messages="$errors->get('account_holder')" />
                        <x-input-error :messages="$errors->get('account_number')" />
                        <x-submit-button>Tambah rekening</x-submit-button>
                    </form>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="font-semibold text-slate-900">Request withdraw</h2>
                    <p class="mt-1 text-xs text-slate-500">Minimal Rp {{ IndonesianNumber::formatThousands((string) (int) $stats['min_withdraw']) }}</p>
                    <form method="POST" action="{{ route('affiliate.withdrawals.store') }}" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <x-input-label for="amount" value="Nominal" />
                            <x-text-input id="amount" name="amount" type="number" class="mt-1 block w-full" :value="old('amount')" min="1" step="1" required />
                            <x-input-error :messages="$errors->get('amount')" />
                        </div>
                        <div>
                            <x-input-label for="bank_account_id" value="Rekening terverifikasi" />
                            <select id="bank_account_id" name="bank_account_id" required class="mt-1 w-full rounded-xl border-slate-300 text-sm">
                                <option value="">Pilih rekening</option>
                                @foreach ($bankAccounts->where('verification_status', AffiliateBankVerificationStatus::Verified) as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->bank_code }} · {{ $bank->account_number }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('bank_account_id')" />
                        </div>
                        <div>
                            <x-input-label for="notes" value="Catatan (opsional)" />
                            <x-text-input id="notes" name="notes" class="mt-1 block w-full" :value="old('notes')" />
                        </div>
                        <x-submit-button>Ajukan withdraw</x-submit-button>
                    </form>
                </section>
            </div>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold text-slate-900">Riwayat komisi</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2">Booking</th>
                                <th class="px-3 py-2">Rate</th>
                                <th class="px-3 py-2">Nominal</th>
                                <th class="px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($commissions as $commission)
                                <tr>
                                    <td class="px-3 py-2">{{ $commission->booking?->booking_code ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ number_format((float) $commission->commission_rate_snapshot * 100, 2) }}%</td>
                                    <td class="px-3 py-2 tabular-nums">Rp {{ IndonesianNumber::formatThousands((string) (int) $commission->commission_amount) }}</td>
                                    <td class="px-3 py-2">{{ $commission->status->label() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-3 py-4 text-slate-500">Belum ada komisi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $commissions->links() }}</div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold text-slate-900">Riwayat withdraw</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2">Waktu</th>
                                <th class="px-3 py-2">Nominal</th>
                                <th class="px-3 py-2">Rekening</th>
                                <th class="px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($withdrawals as $withdrawal)
                                <tr>
                                    <td class="px-3 py-2">{{ $withdrawal->requested_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2 tabular-nums">Rp {{ IndonesianNumber::formatThousands((string) (int) $withdrawal->amount) }}</td>
                                    <td class="px-3 py-2">{{ $withdrawal->beneficiary_bank }} · {{ $withdrawal->beneficiary_account }}</td>
                                    <td class="px-3 py-2">{{ $withdrawal->status->label() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-3 py-4 text-slate-500">Belum ada withdraw.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $withdrawals->links() }}</div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold text-slate-900">Ledger wallet</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2">Waktu</th>
                                <th class="px-3 py-2">Tipe</th>
                                <th class="px-3 py-2">Nominal</th>
                                <th class="px-3 py-2">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($ledger as $entry)
                                <tr>
                                    <td class="px-3 py-2">{{ $entry->occurred_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2">{{ $entry->type->label() }}</td>
                                    <td class="px-3 py-2 tabular-nums {{ (float) $entry->amount >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                        Rp {{ IndonesianNumber::formatThousands((string) (int) $entry->amount) }}
                                    </td>
                                    <td class="px-3 py-2 tabular-nums">Rp {{ IndonesianNumber::formatThousands((string) (int) $entry->balance_after) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-3 py-4 text-slate-500">Belum ada transaksi wallet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $ledger->links() }}</div>
            </section>
        </x-page-container>
    </div>
</x-app-layout>
