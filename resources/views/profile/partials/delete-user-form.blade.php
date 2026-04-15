<section class="space-y-6">
    <header>
        <h2 class="text-lg font-semibold text-slate-900">
            {{ __('profile.delete.title') }}
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            {{ __('profile.delete.subtitle') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('profile.delete.button') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-semibold text-slate-900">
                {{ __('profile.delete.modal_title') }}
            </h2>

            <p class="mt-1 text-sm text-slate-600">
                {{ __('profile.delete.modal_body') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" :value="__('profile.password_placeholder')" class="sr-only" />

                <div class="mt-1 w-3/4 max-w-full">
                    <x-password-input
                        id="password"
                        name="password"
                        class="block w-full"
                        :placeholder="__('profile.password_placeholder')"
                    />
                </div>

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('profile.delete.cancel') }}
                </x-secondary-button>

                <x-danger-button>
                    {{ __('profile.delete.confirm') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
