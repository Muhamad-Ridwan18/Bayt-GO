<section x-data="profileForm">
    <header>
        <h2 class="text-lg font-semibold text-slate-900">
            {{ __('profile.password.title') }}
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            {{ __('profile.password.subtitle') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" @submit="submit" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <!-- Error alert banner -->
        <div x-show="errorMessage" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 shadow-sm" x-cloak>
            <p class="font-semibold" x-text="errorMessage"></p>
            <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs text-red-850">
                <template x-for="(messages, key) in errors" :key="key">
                    <template x-for="msg in messages" :key="msg">
                        <li x-text="msg"></li>
                    </template>
                </template>
            </ul>
        </div>

        <div>
            <x-input-label for="update_password_current_password" :value="__('profile.password.current')" />
            <x-password-input id="update_password_current_password" name="current_password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" field="current_password" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('profile.password.new')" />
            <x-password-input id="update_password_password" name="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" field="password" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('profile.password.confirm')" />
            <x-password-input id="update_password_password_confirmation" name="password_confirmation" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" field="password_confirmation" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button x-bind:disabled="loading">
                <span x-show="loading" class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" x-cloak></span>
                <span x-text="loading ? 'Mengubah...' : 'Ubah password'"></span>
            </x-primary-button>
        </div>
    </form>
</section>

