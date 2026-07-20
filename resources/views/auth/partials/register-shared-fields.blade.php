<div class="space-y-2">
    <x-phone-international-input
        name="phone"
        :value="old('phone')"
        :country="old('country')"
        :label="__('No. WhatsApp')"
        :hint="__('auth_custom.register_phone_hint')"
        :required="true"
        input-id="register_phone_local"
        select-id="register_phone_country_code"
        error-key="phone"
    />
</div>

<div>
    <x-input-label for="name" required>
        <span x-text="(selectedRole === 'customer' && customerType === 'company') ? 'Nama perusahaan' : 'Nama lengkap'"></span>
    </x-input-label>
    <x-text-input id="name" class="mt-1 block w-full border-slate-300" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
    <p class="mt-1 text-xs text-slate-500" x-text="(selectedRole === 'customer' && customerType === 'company') ? 'Nama badan usaha sesuai dokumen.' : 'Sesuai identitas resmi (KTP / passport).'"></p>
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div>
    <x-input-label for="email" :value="__('Email')" required />
    <x-text-input id="email" class="mt-1 block w-full border-slate-300" type="email" name="email" :value="old('email')" required autocomplete="username" />
    <x-input-error :messages="$errors->get('email')" class="mt-2" />
</div>
