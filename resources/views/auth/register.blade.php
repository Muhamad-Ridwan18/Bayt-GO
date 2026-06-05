<x-guest-layout variant="register" :wide="true">
    <div class="mb-8">
        <span class="mb-5 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-baytgo ring-1 ring-emerald-100">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
            </svg>
        </span>
        <h1 class="text-[1.75rem] font-bold tracking-tight text-slate-900 sm:text-3xl">{{ __('guest.register.title') }}</h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ __('guest.register.subtitle') }}</p>
        @if ($otpEnabled ?? false)
            <p class="mt-4 flex gap-3 rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                <span>{{ __('guest.register.otp_notice') }}</span>
            </p>
        @endif
    </div>

    {{-- x-data pakai kutip tunggal: @json mengeluarkan "..." sehingga tidak memutus atribut HTML --}}
    <form
        id="register-form"
        x-ref="registerForm"
        method="POST"
        action="{{ route('register') }}"
        enctype="multipart/form-data"
        class="space-y-5"
        x-data='{
            selectedRole: @json(old("role", "customer")),
            customerType: @json(old("customer_type", "personal")),
            termsModalOpen: false,
            termsAccepted: false,

            handleRegisterSubmit() {
                if (this.termsAccepted) {
                    this.$refs.registerForm.submit();
                    return;
                }

                this.termsModalOpen = true;
            },

            agreeAndSubmit() {
                this.termsAccepted = true;
                this.termsModalOpen = false;
                this.$nextTick(() => {
                    this.$refs.registerForm.submit();
                });
            },
        }'
        data-submit-lock="off"
        @submit.prevent="handleRegisterSubmit"
    >
        @csrf

        <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4 sm:p-5">
            <span class="mb-3 block text-sm font-semibold text-slate-800">{{ __('guest.register.role_heading') }}</span>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label class="group relative flex cursor-pointer items-start gap-3 rounded-2xl border-2 border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-200 hover:shadow-md has-[:checked]:border-baytgo has-[:checked]:bg-emerald-50/50 has-[:checked]:shadow-md">
                    <input type="radio" name="role" value="customer" class="sr-only" {{ old('role', 'customer') === 'customer' ? 'checked' : '' }} required x-on:change="selectedRole = 'customer'">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-700 ring-1 ring-sky-100 transition group-has-[:checked]:bg-baytgo group-has-[:checked]:text-white group-has-[:checked]:ring-baytgo/30">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                    </span>
                    <span class="min-w-0 pt-0.5">
                        <span class="block text-sm font-bold text-slate-900">{{ __('guest.register.role_customer') }}</span>
                        <span class="mt-0.5 block text-xs text-slate-500">{{ __('guest.register.role_customer_sub') }}</span>
                    </span>
                </label>
                <label class="group relative flex cursor-pointer items-start gap-3 rounded-2xl border-2 border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-200 hover:shadow-md has-[:checked]:border-baytgo has-[:checked]:bg-emerald-50/50 has-[:checked]:shadow-md">
                    <input type="radio" name="role" value="muthowif" class="sr-only" {{ old('role') === 'muthowif' ? 'checked' : '' }} x-on:change="selectedRole = 'muthowif'">
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

        <fieldset class="space-y-3 border-0 p-0 m-0" x-bind:disabled="selectedRole !== 'customer'">
            <legend class="sr-only">Tipe jamaah</legend>
            {{-- Jangan pakai x-cloak di sini: bisa selamanya tersembunyi jika Alpine lambat/error. SSR + x-show saja. --}}
            <div x-show="selectedRole === 'customer'" @if (old('role', 'customer') !== 'customer') style="display: none" @endif>
                <span class="mb-3 block text-sm font-semibold text-slate-800">Tipe jamaah</span>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label class="relative flex cursor-pointer rounded-2xl border-2 border-slate-200 bg-white p-4 transition hover:border-emerald-200 has-[:checked]:border-baytgo has-[:checked]:bg-emerald-50/50">
                        <input
                            type="radio"
                            name="customer_type"
                            value="personal"
                            class="mt-1 text-baytgo focus:ring-baytgo/30"
                            x-model="customerType"
                            {{ old('customer_type', 'personal') === 'personal' ? 'checked' : '' }}
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
                            {{ old('customer_type') === 'company' ? 'checked' : '' }}
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
            <x-input-label for="name">
                <span x-text="(selectedRole === 'customer' && customerType === 'company') ? 'Nama perusahaan' : 'Nama lengkap'"></span>
            </x-input-label>
            <x-text-input id="name" class="block mt-1 w-full border-slate-300" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <p class="mt-1 text-xs text-slate-500" x-text="(selectedRole === 'customer' && customerType === 'company') ? 'Nama badan usaha sesuai dokumen.' : 'Sesuai identitas resmi (KTP / passport).'"></p>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full border-slate-300" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <fieldset class="space-y-5 border-0 p-0 m-0" x-bind:disabled="selectedRole !== 'customer'">
            <legend class="sr-only">Alamat jamaah</legend>
            <div x-show="selectedRole === 'customer'" class="space-y-5" @if (old('role', 'customer') !== 'customer') style="display: none" @endif>
                <div>
                    <x-input-label for="address_customer" value="Alamat" />
                    <textarea id="address_customer" name="address" rows="3" class="block mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" placeholder="Alamat lengkap">{{ old('address') }}</textarea>
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>

                <div x-show="customerType === 'company'" @if (old('customer_type') !== 'company') style="display: none" @endif>
                    <x-input-label for="ppui_number" value="Nomor PPUI" />
                    <x-text-input id="ppui_number" class="block mt-1 w-full border-slate-300" type="text" name="ppui_number" :value="old('ppui_number')" autocomplete="off" x-bind:disabled="customerType !== 'company'" />
                    <p class="mt-1 text-xs text-slate-500">Wajib untuk jamaah tipe perusahaan.</p>
                    <x-input-error :messages="$errors->get('ppui_number')" class="mt-2" />
                </div>
            </div>
        </fieldset>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-password-input id="password" class="block mt-1 w-full border-slate-300" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Konfirmasi password')" />
            <x-password-input id="password_confirmation" class="block mt-1 w-full border-slate-300" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <fieldset
            x-show="selectedRole === 'muthowif'"
            class="space-y-5 pt-2 border-t border-slate-200 m-0 min-w-0 border-0 p-0"
            x-bind:disabled="selectedRole !== 'muthowif'"
            @if (old('role') !== 'muthowif') style="display: none" @endif
        >
            <legend class="sr-only">Data muthowif</legend>
            <p class="text-sm font-medium text-slate-800">Data muthowif</p>
            <p class="text-xs text-slate-500 -mt-3">Gunakan tombol <strong>+ Tambah</strong> untuk menambah baris bahasa, studi, pengalaman, atau dokumen.</p>

            <div>
                <x-input-label for="birth_date" value="Tanggal lahir" />
                <x-text-input id="birth_date" class="block mt-1 w-full border-slate-300" type="date" name="birth_date" :value="old('birth_date')" />
                <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="passport_number" value="No. passport" />
                <x-text-input id="passport_number" class="block mt-1 w-full border-slate-300" type="text" name="passport_number" :value="old('passport_number')" autocomplete="off" />
                <x-input-error :messages="$errors->get('passport_number')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="photo" value="Upload foto profil" />
                @if (session()->has('registration_files.photo'))
                    <div class="mb-2 p-2 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center justify-between text-xs text-emerald-800">
                        <span class="font-medium">✓ File terunggah: {{ session('registration_files.photo.original_name') }}</span>
                        <span class="text-slate-400 font-normal italic">(Unggah file baru untuk mengganti)</span>
                    </div>
                @endif
                <x-input-file id="photo" name="photo" accept="image/jpeg,image/png,image/webp" />
                <p class="mt-1 text-xs text-slate-500">Wajah jelas — JPG, PNG, atau WebP, maks. 5 MB.</p>
                <x-input-error :messages="$errors->get('photo')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="ktp_image" value="Foto / scan KTP" />
                @if (session()->has('registration_files.ktp_image'))
                    <div class="mb-2 p-2 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center justify-between text-xs text-emerald-800">
                        <span class="font-medium">✓ File terunggah: {{ session('registration_files.ktp_image.original_name') }}</span>
                        <span class="text-slate-400 font-normal italic">(Unggah file baru untuk mengganti)</span>
                    </div>
                @endif
                <x-input-file id="ktp_image" name="ktp_image" accept="image/jpeg,image/png,image/webp" />
                <p class="mt-1 text-xs text-slate-500">Pastikan teks pada KTP terbaca.</p>
                <x-input-error :messages="$errors->get('ktp_image')" class="mt-2" />
            </div>

            <x-repeating-text-field
                name="languages"
                label="Penguasaan bahasa :"
                item-label="Bahasa"
                placeholder="Contoh: Arab (fasih), Inggris"
                add-label="Tambah Bahasa"
                :items="['']"
            />

            <x-repeating-text-field
                name="educations"
                label="Studi / pendidikan (opsional) :"
                item-label="Studi"
                placeholder="Riwayat studi atau pendidikan formal"
                add-label="Tambah Studi"
                :items="['']"
            />

            <x-repeating-text-field
                name="work_experiences"
                label="Pengalaman kerja :"
                item-label="Pengalaman"
                placeholder="Masukkan pengalaman kerja sebagai Muthowif"
                add-label="Tambah Pengalaman"
                :items="['']"
            />

            <div>
                <x-input-label for="reference_text" value="Referensi muthowif (opsional)" />
                <textarea id="reference_text" name="reference_text" rows="3" class="block mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" placeholder="Nama lembaga, kontak, atau keterangan referensi">{{ old('reference_text') }}</textarea>
                <x-input-error :messages="$errors->get('reference_text')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="muthowif_referral_code" :value="__('auth_custom.muthowif_referral_code_label')" />
                <x-text-input id="muthowif_referral_code" class="block mt-1 w-full border-slate-300 font-mono uppercase" type="text" name="muthowif_referral_code" :value="old('muthowif_referral_code')" maxlength="16" autocomplete="off" placeholder="{{ __('auth_custom.muthowif_referral_code_placeholder') }}" />
                <p class="mt-1 text-xs text-slate-500">{{ __('auth_custom.muthowif_referral_code_hint') }}</p>
                <x-input-error :messages="$errors->get('muthowif_referral_code')" class="mt-2" />
            </div>

            @if (session()->has('registration_files.supporting_documents') && count(session('registration_files.supporting_documents')) > 0)
                <div class="space-y-2">
                    <span class="block text-sm font-medium text-slate-700">Dokumen pendukung yang sudah terunggah:</span>
                    <div class="grid grid-cols-1 gap-2">
                        @foreach (session('registration_files.supporting_documents') as $doc)
                            <div class="p-2 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center justify-between text-xs text-emerald-800 font-medium">
                                <span>✓ {{ $doc['original_name'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-slate-500 italic mt-1">Anda bisa menambahkan dokumen baru di bawah ini jika diperlukan:</p>
                </div>
            @endif

            <x-repeating-file-field
                name="supporting_documents"
                label="Dokumen pendukung :"
                item-label="Dokumen"
                add-label="Tambah dokumen"
                hint="Contoh: CV, portofolio, sertifikat; PDF atau gambar — tiap file maks. 10 MB, hingga 20 file total."
            />

            <p class="text-sm font-medium text-slate-800 pt-2 border-t border-slate-100">Alamat &amp; identitas</p>

            <div>
                <x-input-label for="address_muthowif" value="Alamat lengkap" />
                <textarea id="address_muthowif" name="address" rows="3" class="block mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" placeholder="Alamat domisili sesuai KTP">{{ old('address') }}</textarea>
                <x-input-error :messages="$errors->get('address')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="nik" value="NIK (16 digit)" />
                <x-text-input id="nik" class="block mt-1 w-full border-slate-300" type="text" name="nik" inputmode="numeric" maxlength="16" :value="old('nik')" placeholder="3201xxxxxxxxxxxx" />
                <x-input-error :messages="$errors->get('nik')" class="mt-2" />
            </div>
        </fieldset>

        <div class="space-y-4 border-t border-slate-100 pt-6">
            <x-primary-button class="w-full justify-center rounded-xl bg-baytgo py-3.5 text-base font-semibold shadow-md shadow-baytgo/15 hover:bg-baytgo-800" type="submit">
                {{ __('guest.register.submit') }}
            </x-primary-button>

            <div class="relative my-2">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-slate-200"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="bg-white px-3 text-slate-400">{{ __('guest.or') }}</span>
                </div>
            </div>

            <a href="{{ route('login') }}" class="flex w-full items-center justify-center rounded-xl bg-slate-100 px-4 py-3.5 text-sm font-medium text-slate-600 transition hover:bg-slate-200/80">
                {{ __('guest.register.has_account') }}
                <span class="ms-1 font-bold text-baytgo">{{ __('guest.register.login_link') }}</span>
            </a>
        </div>

        <div
            x-show="termsModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60"
            role="dialog"
            aria-modal="true"
        >
            <div class="max-w-lg w-full rounded-3xl bg-white shadow-2xl ring-1 ring-slate-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Syarat & Ketentuan</h2>
                    <p class="mt-2 text-sm text-slate-600">Sebelum mendaftar, pastikan Anda sudah membaca dan menyetujui syarat & ketentuan.</p>
                </div>
                <div class="px-6 py-5 space-y-4 text-sm text-slate-700">
                    <p>Dengan mengklik "Setuju dan Daftar", Anda menyetujui <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-700 hover:text-brand-800">Syarat & Ketentuan</a> kami.</p>
                    <p class="text-slate-500">Jika ingin memeriksa kembali dokumen sebelum mendaftar, klik "Batal" dan baca syarat terlebih dahulu.</p>
                </div>
                <div class="px-6 py-4 bg-slate-50 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        @click="termsModalOpen = false"
                        class="inline-flex justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="agreeAndSubmit"
                        class="inline-flex justify-center rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
                    >
                        Setuju dan Daftar
                    </button>
                </div>
            </div>
        </div>
    </form>

    
</x-guest-layout>
