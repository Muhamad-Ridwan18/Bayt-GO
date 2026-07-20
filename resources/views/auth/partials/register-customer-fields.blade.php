<fieldset class="m-0 space-y-5 border-0 p-0" x-bind:disabled="selectedRole !== 'customer'">
    <legend class="sr-only">Alamat jamaah</legend>
    <div x-show="selectedRole === 'customer'" class="space-y-5" @style(['display: none' => ! $page->isCustomer()])>
        <div>
            <x-input-label for="address_customer" value="Alamat" required />
            <textarea id="address_customer" name="address" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Alamat lengkap">{{ old('address') }}</textarea>
            <x-input-error :messages="$errors->get('address')" class="mt-2" />
        </div>

        <div x-show="customerType === 'company'" @style(['display: none' => ! $page->isCompany()])>
            <x-input-label for="ppui_number" value="Nomor PPUI" required />
            <x-text-input id="ppui_number" class="mt-1 block w-full border-slate-300" type="text" name="ppui_number" :value="old('ppui_number')" autocomplete="off" x-bind:disabled="customerType !== 'company'" />
            <p class="mt-1 text-xs text-slate-500">Wajib untuk jamaah tipe perusahaan.</p>
            <x-input-error :messages="$errors->get('ppui_number')" class="mt-2" />
        </div>
    </div>
</fieldset>
