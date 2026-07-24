<div>
    <x-input-label for="password" :value="__('Password')" required />
    <x-password-input id="password" class="mt-1 block w-full border-slate-300" name="password" required autocomplete="new-password" />
    <x-input-error :messages="$errors->get('password')" class="mt-2" />
</div>

<div>
    <x-input-label for="password_confirmation" :value="__('Konfirmasi password')" required />
    <x-password-input id="password_confirmation" class="mt-1 block w-full border-slate-300" name="password_confirmation" required autocomplete="new-password" />
    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
</div>
