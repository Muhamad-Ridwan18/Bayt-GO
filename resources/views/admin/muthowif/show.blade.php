<x-app-layout>

    <div class="py-8 sm:py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-slate-500">{{ __('admin.muthowif.name') }}</p>
                        <p class="text-lg font-semibold text-slate-900">{{ $profile->user->name }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $profile->user->email }}</p>
                    </div>
                    <span class="inline-flex rounded-lg px-3 py-1 text-sm font-medium
                        @switch($profile->verification_status)
                            @case(\App\Enums\MuthowifVerificationStatus::Pending)
                                bg-amber-100 text-amber-900
                                @break
                            @case(\App\Enums\MuthowifVerificationStatus::Approved)
                                bg-emerald-100 text-emerald-900
                                @break
                            @default
                                bg-rose-100 text-rose-900
                        @endswitch
                    ">
                        {{ $profile->verification_status->label() }}
                    </span>
                </div>

                @if ($profile->isRejected() && filled($profile->rejection_reason))
                    <div class="rounded-lg border border-rose-100 bg-rose-50/80 px-4 py-3 text-sm text-rose-900">
                        <p class="font-medium">{{ __('admin.muthowif.rejection_heading') }}</p>
                        <p class="mt-1 whitespace-pre-line">{{ $profile->rejection_reason }}</p>
                    </div>
                @endif

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-slate-500">{{ __('admin.muthowif.whatsapp') }}</dt>
                        <dd class="font-medium text-slate-900">{{ $profile->phone }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-slate-500">{{ __('admin.muthowif.address') }}</dt>
                        <dd class="font-medium text-slate-900 whitespace-pre-line">{{ $profile->address }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">{{ __('admin.muthowif.nik') }}</dt>
                        <dd class="font-medium text-slate-900">{{ $profile->nik }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">{{ __('admin.muthowif.birth_date') }}</dt>
                        <dd class="font-medium text-slate-900">{{ $profile->birth_date?->translatedFormat('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">{{ __('admin.muthowif.passport') }}</dt>
                        <dd class="font-medium text-slate-900">{{ $profile->passport_number ?? '—' }}</dd>
                    </div>
                </dl>

                <x-line-list :label="__('admin.muthowif.languages')" :items="$profile->languagesForDisplay()" />
                <x-line-list :label="__('admin.muthowif.education')" :items="$profile->educationsForDisplay()" />
                <x-line-list :label="__('admin.muthowif.work')" :items="$profile->workExperiencesForDisplay()" />

                @if (filled($profile->reference_text))
                    <div>
                        <p class="text-sm font-medium text-slate-900">{{ __('admin.muthowif.reference') }}</p>
                        <p class="mt-1 text-sm text-slate-700 whitespace-pre-line">{{ $profile->reference_text }}</p>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-sm font-medium text-slate-900 mb-3">{{ __('admin.muthowif.photo_title') }}</p>
                    <img
                        src="{{ route('admin.muthowif.photo', $profile) }}"
                        alt="{{ __('admin.muthowif.photo_alt') }}"
                        class="w-full max-h-80 object-contain rounded-lg bg-slate-100"
                    />
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-sm font-medium text-slate-900 mb-3">{{ __('admin.muthowif.ktp_title') }}</p>
                    <img
                        src="{{ route('admin.muthowif.ktp', $profile) }}"
                        alt="{{ __('admin.muthowif.ktp_alt') }}"
                        class="w-full max-h-80 object-contain rounded-lg bg-slate-100"
                    />
                </div>
            </div>

            @if ($profile->supportingDocuments->isNotEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                    <p class="text-sm font-medium text-slate-900">{{ __('admin.muthowif.documents') }}</p>
                    <ul class="space-y-2">
                        @foreach ($profile->supportingDocuments as $doc)
                            <li class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                <span class="text-slate-700">{{ $doc->original_name ?? basename($doc->path) }}</span>
                                <a
                                    href="{{ route('admin.muthowif.document', [$profile, $doc]) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="font-medium text-brand-700 hover:text-brand-800"
                                >
                                    {{ __('admin.muthowif.open_download') }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($profile->isPending())
                <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm space-y-6">
                    <form method="POST" action="{{ route('admin.muthowif.approve', $profile) }}" class="inline">
                        @csrf
                        <x-primary-button type="submit" class="bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500">
                            {{ __('admin.muthowif.approve_registration') }}
                        </x-primary-button>
                    </form>

                    <form method="POST" action="{{ route('admin.muthowif.reject', $profile) }}" class="space-y-3 border-t border-slate-100 pt-6">
                        @csrf
                        <x-input-label for="rejection_reason" :value="__('admin.muthowif.reject_label')" />
                        <textarea
                            id="rejection_reason"
                            name="rejection_reason"
                            rows="3"
                            class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"
                            placeholder="{{ __('admin.muthowif.reject_placeholder') }}"
                        >{{ old('rejection_reason') }}</textarea>
                        <x-input-error :messages="$errors->get('rejection_reason')" class="mt-1" />
                        <x-secondary-button type="submit" class="border-rose-200 text-rose-800 hover:bg-rose-50">
                            {{ __('admin.muthowif.reject_submit') }}
                        </x-secondary-button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
