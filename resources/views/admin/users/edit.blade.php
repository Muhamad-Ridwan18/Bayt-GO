@php
    use App\Enums\CustomerType;
    use App\Enums\UserRole;
@endphp

<x-app-layout>
    <div class="relative min-h-[calc(100vh-4rem)] overflow-hidden bg-gradient-to-b from-slate-100 via-slate-50 to-white py-8 sm:py-12" x-data="{ role: '{{ old('role', $editUser->role->value) }}' }">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_40%_at_50%_-10%,rgba(120,53,15,0.06),transparent)]"></div>
        <div class="relative mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-violet-950 to-brand-900 p-6 text-white shadow-lg shadow-violet-900/25 ring-1 ring-white/10 sm:rounded-3xl">
                <div class="pointer-events-none absolute -right-10 top-0 h-40 w-40 rounded-full bg-violet-500/25 blur-3xl"></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20" aria-hidden="true">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" /></svg>
                        </span>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-violet-200/90">{{ __('admin.users.edit_badge') }}</p>
                            <h1 class="mt-1 text-xl font-bold tracking-tight sm:text-2xl">{{ $editUser->name }}</h1>
                            <p class="mt-1 text-sm text-violet-100/80">{{ __('admin.users.edit_subtitle') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.users.index') }}" class="inline-flex shrink-0 items-center gap-2 self-start rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20">
                        {{ __('admin.users.back_list') }}
                    </a>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/80 shadow-sm ring-1 ring-slate-100/80">
                <div class="flex min-w-0">
                    <div class="w-1 shrink-0 bg-brand-500" aria-hidden="true"></div>
                    <div class="min-w-0 flex-1 p-5 sm:p-7">
                        <form method="post" action="{{ route('admin.users.update', $editUser) }}" class="space-y-6">
                            @csrf
                            @method('patch')

                            <div>
                                <x-input-label for="name" :value="__('admin.users.field_name')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $editUser->name)" required autofocus />
                                <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            </div>

                            <div>
                                <x-input-label for="email" :value="__('admin.users.field_email')" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $editUser->email)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('email')" />
                            </div>

                            <div>
                                <x-input-label for="phone" :value="__('admin.users.field_phone')" />
                                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $editUser->phone)" />
                                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                            </div>

                            <div>
                                <x-input-label for="address" :value="__('admin.users.field_address')" />
                                <textarea id="address" name="address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('address', $editUser->address) }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('address')" />
                            </div>

                            <div>
                                <x-input-label for="role" :value="__('admin.users.field_role')" />
                                <select id="role" name="role" x-model="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    @foreach (UserRole::cases() as $r)
                                        <option value="{{ $r->value }}" @selected(old('role', $editUser->role->value) === $r->value)>{{ $r->label() }}</option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('role')" />
                            </div>

                            <div x-show="role === '{{ UserRole::Customer->value }}'" x-cloak class="space-y-4 rounded-xl border border-sky-100 bg-sky-50/50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-sky-800">{{ __('admin.users.customer_section') }}</p>
                                <div>
                                    <x-input-label for="customer_type" :value="__('admin.users.field_customer_type')" />
                                    <select id="customer_type" name="customer_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                        <option value="">{{ __('admin.users.select_customer_type') }}</option>
                                        @foreach (CustomerType::cases() as $ct)
                                            <option value="{{ $ct->value }}" @selected(old('customer_type', $editUser->customer_type?->value) === $ct->value)>{{ $ct->label() }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('customer_type')" />
                                </div>
                                <div>
                                    <x-input-label for="ppui_number" :value="__('admin.users.field_ppui')" />
                                    <x-text-input id="ppui_number" name="ppui_number" type="text" class="mt-1 block w-full" :value="old('ppui_number', $editUser->ppui_number)" />
                                    <x-input-error class="mt-2" :messages="$errors->get('ppui_number')" />
                                </div>
                            </div>

                            <div>
                                <x-input-label for="locale" :value="__('admin.users.field_locale')" />
                                <select id="locale" name="locale" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    <option value="" @selected(old('locale', $editUser->locale) === null || old('locale', $editUser->locale) === '')>{{ __('admin.users.locale_default') }}</option>
                                    <option value="id" @selected(old('locale', $editUser->locale) === 'id')>Bahasa Indonesia</option>
                                    <option value="en" @selected(old('locale', $editUser->locale) === 'en')>English</option>
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('locale')" />
                            </div>

                            <div class="space-y-4 rounded-xl border border-amber-100 bg-amber-50/40 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-amber-900">{{ __('admin.users.password_section') }}</p>
                                <div>
                                    <x-input-label for="password" :value="__('admin.users.field_password_new')" />
                                    <x-password-input id="password" name="password" class="mt-1 block w-full" autocomplete="new-password" />
                                    <x-input-error class="mt-2" :messages="$errors->get('password')" />
                                </div>
                                <div>
                                    <x-input-label for="password_confirmation" :value="__('admin.users.field_password_confirm')" />
                                    <x-password-input id="password_confirmation" name="password_confirmation" class="mt-1 block w-full" autocomplete="new-password" />
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
                                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                    {{ __('admin.users.cancel') }}
                                </a>
                                <x-primary-button class="justify-center rounded-xl px-6 py-2.5">
                                    {{ __('admin.users.save') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
