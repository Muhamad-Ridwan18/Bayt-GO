<div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4 sm:p-5">
    <span class="mb-3 block text-sm font-semibold text-slate-800">{{ __('guest.register.role_heading') }}<span class="text-red-600" aria-hidden="true"> *</span></span>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <label class="group relative flex cursor-pointer items-start gap-3 rounded-2xl border-2 border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-200 hover:shadow-md has-[:checked]:border-baytgo has-[:checked]:bg-emerald-50/50 has-[:checked]:shadow-md">
            <input type="radio" name="role" value="customer" class="sr-only" @checked($page->isCustomer()) required x-on:change="selectedRole = 'customer'">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-700 ring-1 ring-sky-100 transition group-has-[:checked]:bg-baytgo group-has-[:checked]:text-white group-has-[:checked]:ring-baytgo/30">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
            </span>
            <span class="min-w-0 pt-0.5">
                <span class="block text-sm font-bold text-slate-900">{{ __('guest.register.role_customer') }}</span>
                <span class="mt-0.5 block text-xs text-slate-500">{{ __('guest.register.role_customer_sub') }}</span>
            </span>
        </label>
        <label class="group relative flex cursor-pointer items-start gap-3 rounded-2xl border-2 border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-200 hover:shadow-md has-[:checked]:border-baytgo has-[:checked]:bg-emerald-50/50 has-[:checked]:shadow-md">
            <input type="radio" name="role" value="muthowif" class="sr-only" @checked($page->isMuthowif()) x-on:change="selectedRole = 'muthowif'">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 ring-1 ring-amber-100 transition group-has-[:checked]:bg-baytgo group-has-[:checked]:text-white group-has-[:checked]:ring-baytgo/30">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" /></svg>
            </span>
            <span class="min-w-0 pt-0.5">
                <span class="block text-sm font-bold text-slate-900">{{ __('guest.register.role_muthowif') }}</span>
                <span class="mt-0.5 block text-xs text-slate-500">{{ __('guest.register.role_muthowif_sub') }}</span>
            </span>
        </label>
    </div>
    <x-input-error :messages="$errors->get('role')" class="mt-2" />
</div>
