<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('profile_public.title') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
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
            <textarea id="public_address" name="address" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">{{ old('address', $muthowifProfile->address) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('address')" />
        </div>

        <div>
            <x-input-label for="public_photo" :value="__('profile_public.photo')" />
            <x-input-file id="public_photo" name="photo" accept="image/jpeg,image/png,image/webp" />
            <p class="mt-1 text-xs text-gray-500">{{ __('profile_public.photo_hint') }}</p>
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
            <textarea id="public_reference_text" name="reference_text" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">{{ old('reference_text', $muthowifProfile->reference_text) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('reference_text')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('profile_public.save') }}</x-primary-button>

            @if (session('status') === 'public-profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-gray-600">
                    {{ __('profile_public.saved') }}
                </p>
            @endif
        </div>
    </form>
</section>

