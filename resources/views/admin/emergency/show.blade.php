@php
    use App\Enums\EmergencyReportStatus;
    $b = $report->muthowifBooking;
@endphp
<x-app-layout>
    <x-page-container class="py-8">
        <a href="{{ route('admin.emergency.index') }}" class="mb-4 inline-block text-sm font-semibold text-brand-700">← {{ __('emergency.admin.index_title') }}</a>
        <h1 class="text-2xl font-bold text-slate-900">{{ __('emergency.admin.show_title') }}</h1>

        @if (session('status'))
            <p class="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p class="mt-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-900">{{ session('error') }}</p>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <dl class="space-y-3 text-sm">
                    <div><dt class="font-semibold text-slate-500">{{ __('emergency.admin.booking_code') }}</dt><dd class="font-mono">{{ $b?->booking_code }}</dd></div>
                    <div><dt class="font-semibold text-slate-500">{{ __('emergency.admin.customer') }}</dt><dd>{{ $b?->customer?->name }} · {{ $b?->customer?->email }}</dd></div>
                    <div><dt class="font-semibold text-slate-500">{{ __('emergency.admin.muthowif') }}</dt><dd>{{ $b?->muthowifProfile?->user?->name }}</dd></div>
                    <div><dt class="font-semibold text-slate-500">{{ __('emergency.admin.case') }}</dt><dd>{{ $report->case_type->label() }}</dd></div>
                    <div><dt class="font-semibold text-slate-500">{{ __('emergency.admin.status') }}</dt><dd>{{ $report->status->label() }} · Batch {{ $report->replacement_batch_number }}</dd></div>
                    @if ($report->description)
                        <div><dt class="font-semibold text-slate-500">{{ __('emergency.admin.description') }}</dt><dd class="whitespace-pre-wrap">{{ $report->description }}</dd></div>
                    @endif
                    @if ($report->admin_note)
                        <div><dt class="font-semibold text-slate-500">{{ __('emergency.admin.admin_note') }}</dt><dd class="whitespace-pre-wrap">{{ $report->admin_note }}</dd></div>
                    @endif
                </dl>

                @if ($report->status === EmergencyReportStatus::Submitted)
                    <form method="POST" action="{{ route('admin.emergency.under_review', $report) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-semibold text-white">{{ __('emergency.admin.mark_review') }}</button>
                    </form>
                @endif

                @if (in_array($report->status, [EmergencyReportStatus::Submitted, EmergencyReportStatus::UnderReview], true))
                    <form method="POST" action="{{ route('admin.emergency.verify', $report) }}" class="mt-4 space-y-2">
                        @csrf
                        <textarea name="admin_note" rows="2" class="w-full rounded-xl border-slate-200 text-sm" placeholder="{{ __('emergency.admin.admin_note') }}"></textarea>
                        <button type="submit" class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">{{ __('emergency.admin.verify') }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.emergency.reject', $report) }}" class="mt-3 space-y-2">
                        @csrf
                        <textarea name="admin_note" rows="2" class="w-full rounded-xl border-slate-200 text-sm"></textarea>
                        <button type="submit" class="rounded-xl bg-rose-700 px-4 py-2 text-sm font-semibold text-white">{{ __('emergency.admin.reject') }}</button>
                    </form>
                @endif

                @if ($report->status === EmergencyReportStatus::Verified && $report->recruitment_open)
                    <form method="POST" action="{{ route('admin.emergency.broadcast', $report) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="rounded-xl bg-brand-700 px-4 py-2 text-sm font-semibold text-white">{{ __('emergency.admin.broadcast') }}</button>
                    </form>
                @endif
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-bold text-slate-900">{{ __('emergency.admin.offers') }}</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($report->offers as $offer)
                        <li class="rounded-lg bg-slate-50 px-3 py-2">
                            <span class="font-medium">{{ $offer->muthowifProfile?->user?->name }}</span>
                            · {{ $offer->status->label() }}
                            · {{ __('emergency.admin.batch') }} {{ $offer->batch_number }}
                            @if ($offer->source === 'admin_invite') <span class="text-xs text-brand-700">(manual)</span> @endif
                        </li>
                    @empty
                        <li class="text-slate-500">—</li>
                    @endforelse
                </ul>

                @if ($report->status === EmergencyReportStatus::Verified && $report->recruitment_open && $manualCandidates->isNotEmpty())
                    <form method="POST" action="{{ route('admin.emergency.invite', $report) }}" class="mt-6 space-y-2">
                        @csrf
                        <label class="text-xs font-semibold text-slate-600">{{ __('emergency.admin.manual_invite') }}</label>
                        <select name="muthowif_profile_id" class="w-full rounded-xl border-slate-200 text-sm" required>
                            <option value="">{{ __('emergency.admin.select_muthowif') }}</option>
                            @foreach ($manualCandidates as $candidate)
                                <option value="{{ $candidate->id }}">{{ $candidate->user?->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="rounded-xl bg-amber-700 px-4 py-2 text-sm font-semibold text-white">{{ __('emergency.admin.invite') }}</button>
                    </form>
                @endif
            </section>
        </div>
    </x-page-container>
</x-app-layout>
