<section x-data="profileForm">
    <header>
        <h2 class="text-lg font-semibold text-slate-900">
            {{ __('profile.account.title') }}
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            {{ __('profile.account.subtitle') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" data-submit-lock="off" @submit="submit" class="mt-6 ui-stack-compact">
        @csrf
        @method('patch')

        <!-- Error alert banner -->
        <div x-show="errorMessage" class="ui-alert-error" x-cloak>
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
            <x-input-label for="name" :value="__('profile.fields.name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" field="name" />
        </div>

        <div>
            <x-input-label for="email" :value="__('profile.fields.email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" field="email" />
            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="mt-2 text-sm text-slate-800">
                        {{ __('profile.verification.unverified') }}

                        <x-submit-button form="send-verification" class="rounded-md text-sm font-medium text-brand-700 underline decoration-brand-700/30 underline-offset-2 hover:text-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                            {{ __('profile.verification.resend') }}
                        </x-submit-button>
                    </p>
                </div>
            @endif
        </div>
        
        <div>
            <x-input-label for="phone" :value="__('profile.fields.whatsapp')" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone)" autocomplete="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" field="phone" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button x-bind:disabled="loading">
                <span x-show="loading" class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" x-cloak></span>
                <span x-text="loading ? 'Menyimpan...' : '{{ __('profile.save') }}'"></span>
            </x-primary-button>
        </div>
    </form>
</section>

