<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Buat akun</h1>
        <p class="mt-1 text-sm text-slate-500">Pilih peran Anda. Muthowif wajib melengkapi biodata, passport, foto, dan dokumen.</p>
        @if ($otpEnabled ?? false)
            <p class="mt-2 text-sm text-brand-800 bg-brand-50/80 border border-brand-100 rounded-lg px-3 py-2">
                Setelah Anda mengirim formulir, langkah berikutnya adalah verifikasi nomor WhatsApp dengan kode OTP sebelum akun dibuat.
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

        <div>
            <span class="block text-sm font-medium text-slate-700 mb-2">Saya mendaftar sebagai</span>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="relative flex cursor-pointer rounded-xl border-2 p-4 transition has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/80 border-slate-200 hover:border-brand-300">
                    <input type="radio" name="role" value="customer" class="mt-0.5 text-brand-600 focus:ring-brand-500" {{ old('role', 'customer') === 'customer' ? 'checked' : '' }} required x-on:change="selectedRole = 'customer'">
                    <span class="ms-3">
                        <span class="block text-sm font-semibold text-slate-900">Jamaah</span>
                        <span class="block text-xs text-slate-500 mt-0.5">Buat permintaan pendampingan</span>
                    </span>
                </label>
                <label class="relative flex cursor-pointer rounded-xl border-2 p-4 transition has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/80 border-slate-200 hover:border-brand-300">
                    <input type="radio" name="role" value="muthowif" class="mt-0.5 text-brand-600 focus:ring-brand-500" {{ old('role') === 'muthowif' ? 'checked' : '' }} x-on:change="selectedRole = 'muthowif'">
                    <span class="ms-3">
                        <span class="block text-sm font-semibold text-slate-900">Muthowif</span>
                        <span class="block text-xs text-slate-500 mt-0.5">Lengkapi dokumen &amp; riwayat</span>
                    </span>
                </label>
            </div>
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
        </div>

        <fieldset class="space-y-3 border-0 p-0 m-0" x-bind:disabled="selectedRole !== 'customer'">
            <legend class="sr-only">Tipe jamaah</legend>
            {{-- Jangan pakai x-cloak di sini: bisa selamanya tersembunyi jika Alpine lambat/error. SSR + x-show saja. --}}
            <div x-show="selectedRole === 'customer'" @if (old('role', 'customer') !== 'customer') style="display: none" @endif>
                <span class="block text-sm font-medium text-slate-700 mb-2">Tipe jamaah</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="relative flex cursor-pointer rounded-xl border-2 p-4 transition has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/80 border-slate-200 hover:border-brand-300">
                        <input
                            type="radio"
                            name="customer_type"
                            value="personal"
                            class="mt-0.5 text-brand-600 focus:ring-brand-500"
                            x-model="customerType"
                            {{ old('customer_type', 'personal') === 'personal' ? 'checked' : '' }}
                            required
                        >
                        <span class="ms-3">
                            <span class="block text-sm font-semibold text-slate-900">Personal</span>
                            <span class="block text-xs text-slate-500 mt-0.5">Individu</span>
                        </span>
                    </label>
                    <label class="relative flex cursor-pointer rounded-xl border-2 p-4 transition has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/80 border-slate-200 hover:border-brand-300">
                        <input
                            type="radio"
                            name="customer_type"
                            value="company"
                            class="mt-0.5 text-brand-600 focus:ring-brand-500"
                            x-model="customerType"
                            {{ old('customer_type') === 'company' ? 'checked' : '' }}
                        >
                        <span class="ms-3">
                            <span class="block text-sm font-semibold text-slate-900">Perusahaan</span>
                            <span class="block text-xs text-slate-500 mt-0.5">Badan usaha</span>
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

        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
            <a class="text-sm text-slate-600 hover:text-brand-700 font-medium text-center sm:text-left" href="{{ route('login') }}">
                Sudah punya akun? Masuk
            </a>
            <x-primary-button class="w-full sm:w-auto justify-center" type="submit">
                Daftar
            </x-primary-button>
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
