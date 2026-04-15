<section>
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

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('profile.fields.name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('profile.fields.email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="mt-2 text-sm text-slate-800">
                        {{ __('profile.verification.unverified') }}

                        <button form="send-verification" type="submit" class="rounded-md text-sm font-medium text-brand-700 underline decoration-brand-700/30 underline-offset-2 hover:text-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                            {{ __('profile.verification.resend') }}
                        </button>
                    </p>

                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('profile.save') }}</x-primary-button>
        </div>
    </form>
</section>
