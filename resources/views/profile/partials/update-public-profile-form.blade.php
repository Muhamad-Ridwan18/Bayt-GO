<section>
    <header>
        <h2 class="text-lg font-semibold text-slate-900">
            {{ __('profile_public.title') }}
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            {{ __('profile_public.subtitle') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.public.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="public_phone" :value="__('profile_public.phone')" />
                <x-text-input id="public_phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $muthowifProfile->phone)" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>

            <div>
                <x-input-label for="public_passport_number" :value="__('profile_public.passport')" />
                <x-text-input id="public_passport_number" name="passport_number" type="text" class="mt-1 block w-full" :value="old('passport_number', $muthowifProfile->passport_number)" />
                <x-input-error class="mt-2" :messages="$errors->get('passport_number')" />
            </div>
        </div>

        <div>
            <x-input-label for="public_birth_date" :value="__('profile_public.birth_date')" />
            <x-text-input id="public_birth_date" name="birth_date" type="date" class="mt-1 block w-full" :value="old('birth_date', optional($muthowifProfile->birth_date)->toDateString())" />
            <x-input-error class="mt-2" :messages="$errors->get('birth_date')" />
        </div>

        <div>
            <x-input-label for="public_address" :value="__('profile_public.address')" />
            <textarea id="public_address" name="address" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('address', $muthowifProfile->address) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('address')" />
        </div>

        <div>
            <x-input-label for="public_photo" :value="__('profile_public.photo')" />
            <x-input-file id="public_photo" name="photo" accept="image/jpeg,image/png,image/webp" />
            <p class="mt-1 text-xs text-slate-500">{{ __('profile_public.photo_hint') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('photo')" />
        </div>

        <x-repeating-text-field
            name="languages"
            :label="__('profile_public.languages')"
            :item-label="__('profile_public.language_item')"
            :placeholder="__('profile_public.language_placeholder')"
            :add-label="__('profile_public.language_add')"
            :items="$muthowifProfile->languagesForDisplay() ?: ['']"
        />

        <x-repeating-text-field
            name="educations"
            :label="__('profile_public.education')"
            :item-label="__('profile_public.education_item')"
            :placeholder="__('profile_public.education_placeholder')"
            :add-label="__('profile_public.education_add')"
            :items="$muthowifProfile->educationsForDisplay() ?: ['']"
        />

        <x-repeating-text-field
            name="work_experiences"
            :label="__('profile_public.work')"
            :item-label="__('profile_public.work_item')"
            :placeholder="__('profile_public.work_placeholder')"
            :add-label="__('profile_public.work_add')"
            :items="$muthowifProfile->workExperiencesForDisplay() ?: ['']"
        />

        <div>
            <x-input-label for="public_reference_text" :value="__('profile_public.reference')" />
            <textarea id="public_reference_text" name="reference_text" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('reference_text', $muthowifProfile->reference_text) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('reference_text')" />
        </div>

        <div class="mt-8 border-t border-slate-200 pt-8">
            <h3 class="text-base font-semibold text-slate-900">{{ __('profile_public.referral_section_title') }}</h3>
            <p class="mt-1 text-sm text-slate-600">{{ __('profile_public.referral_section_subtitle') }}</p>

            <div class="mt-4 rounded-xl border border-slate-200/90 bg-slate-50/80 p-4 ring-1 ring-slate-100/80">
                <x-input-label :value="__('profile_public.my_referral_code_label')" />
                @if (filled($muthowifProfile->referral_code))
                    <p class="mt-2 font-mono text-lg font-semibold tracking-wide text-slate-900 select-all">{{ $muthowifProfile->referral_code }}</p>
                    <p class="mt-2 text-xs text-slate-600">{{ __('profile_public.my_referral_code_hint') }}</p>
                @else
                    <p class="mt-2 text-sm text-slate-600">{{ __('profile_public.my_referral_code_pending') }}</p>
                @endif
            </div>

            <div class="mt-4">
                @if ($muthowifProfile->referred_by_muthowif_profile_id !== null && $muthowifProfile->referredBy)
                    <x-input-label :value="__('profile_public.inviter_label')" />
                    <p class="mt-2 text-sm text-slate-800">
                        {{ $muthowifProfile->referredBy->user?->name ?? '—' }}
                        @if (filled($muthowifProfile->referredBy->referral_code))
                            <span class="text-slate-500">·</span>
                            <span class="font-mono font-medium text-slate-900">{{ $muthowifProfile->referredBy->referral_code }}</span>
                        @endif
                    </p>
                    <p class="mt-2 text-xs text-slate-500">{{ __('profile_public.inviter_locked_hint') }}</p>
                @else
                    <x-input-label for="inviter_referral_code" :value="__('profile_public.inviter_code_label')" />
                    <x-text-input
                        id="inviter_referral_code"
                        name="inviter_referral_code"
                        type="text"
                        class="mt-1 block w-full font-mono uppercase"
                        :value="old('inviter_referral_code')"
                        maxlength="16"
                        autocomplete="off"
                        :placeholder="__('profile_public.inviter_code_placeholder')"
                    />
                    <p class="mt-1 text-xs text-slate-500">{{ __('profile_public.inviter_code_hint') }}</p>
                    <x-input-error class="mt-2" :messages="$errors->get('inviter_referral_code')" />
                @endif
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('profile_public.save') }}</x-primary-button>
        </div>
    </form>
</section>
