<fieldset class="m-0 space-y-3 border-0 p-0" x-bind:disabled="selectedRole !== 'customer'">
    <legend class="sr-only">Tipe jamaah</legend>
    <div x-show="selectedRole === 'customer'" @style(['display: none' => ! $page->isCustomer()])>
        <span class="mb-3 block text-sm font-semibold text-slate-800">Tipe jamaah<span class="text-red-600" aria-hidden="true"> *</span></span>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <label class="relative flex cursor-pointer rounded-2xl border-2 border-slate-200 bg-white p-4 transition hover:border-emerald-200 has-[:checked]:border-baytgo has-[:checked]:bg-emerald-50/50">
                <input
                    type="radio"
                    name="customer_type"
                    value="personal"
                    class="mt-1 text-baytgo focus:ring-baytgo/30"
                    x-model="customerType"
                    @checked(! $page->isCompany())
                    required
                >
                <span class="ms-3">
                    <span class="block text-sm font-semibold text-slate-900">Personal</span>
                    <span class="mt-0.5 block text-xs text-slate-500">Individu</span>
                </span>
            </label>
            <label class="relative flex cursor-pointer rounded-2xl border-2 border-slate-200 bg-white p-4 transition hover:border-emerald-200 has-[:checked]:border-baytgo has-[:checked]:bg-emerald-50/50">
                <input
                    type="radio"
                    name="customer_type"
                    value="company"
                    class="mt-1 text-baytgo focus:ring-baytgo/30"
                    x-model="customerType"
                    @checked($page->isCompany())
                >
                <span class="ms-3">
                    <span class="block text-sm font-semibold text-slate-900">Perusahaan</span>
                    <span class="mt-0.5 block text-xs text-slate-500">Badan usaha</span>
                </span>
            </label>
        </div>
        <x-input-error :messages="$errors->get('customer_type')" class="mt-2" />
    </div>
</fieldset>
