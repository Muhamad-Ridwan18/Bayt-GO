<x-guest-layout>
    @php
        $registerOtpRoutes = [
            'send' => route('register.otp.send'),
            'verify' => route('register.otp.verify'),
            'clear' => route('register.otp.clear'),
        ];
    @endphp

    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Buat akun</h1>
        <p class="mt-1 text-sm text-slate-500">Pilih peran Anda. Muthowif wajib melengkapi biodata, passport, foto, dan dokumen.</p>
    </div>

    <form
        id="register-form"
        method="POST"
        action="{{ route('register') }}"
        enctype="multipart/form-data"
        class="space-y-5"
        x-data="registerFormData()"
    >
        @csrf

        <div>
            <span class="block text-sm font-medium text-slate-700 mb-2">Saya mendaftar sebagai</span>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="relative flex cursor-pointer rounded-xl border-2 p-4 transition has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/80 border-slate-200 hover:border-brand-300">
                    <input type="radio" name="role" value="customer" class="mt-0.5 text-brand-600 focus:ring-brand-500" {{ old('role', 'customer') === 'customer' ? 'checked' : '' }} required x-on:change="role = 'customer'; onPhoneChange()">
                    <span class="ms-3">
                        <span class="block text-sm font-semibold text-slate-900">Jamaah</span>
                        <span class="block text-xs text-slate-500 mt-0.5">Buat permintaan pendampingan</span>
                    </span>
                </label>
                <label class="relative flex cursor-pointer rounded-xl border-2 p-4 transition has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/80 border-slate-200 hover:border-brand-300">
                    <input type="radio" name="role" value="muthowif" class="mt-0.5 text-brand-600 focus:ring-brand-500" {{ old('role') === 'muthowif' ? 'checked' : '' }} x-on:change="role = 'muthowif'; onPhoneChange()">
                    <span class="ms-3">
                        <span class="block text-sm font-semibold text-slate-900">Muthowif</span>
                        <span class="block text-xs text-slate-500 mt-0.5">Lengkapi dokumen &amp; riwayat</span>
                    </span>
                </label>
            </div>
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
        </div>

        <fieldset class="space-y-3 border-0 p-0 m-0" x-bind:disabled="role !== 'customer'">
            <legend class="sr-only">Tipe jamaah</legend>
            <div x-show="role === 'customer'" x-cloak>
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

        <div>
            <x-input-label for="name">
                <span x-text="(role === 'customer' && customerType === 'company') ? 'Nama perusahaan' : 'Nama lengkap'"></span>
            </x-input-label>
            <x-text-input id="name" class="block mt-1 w-full border-slate-300" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <p class="mt-1 text-xs text-slate-500" x-text="(role === 'customer' && customerType === 'company') ? 'Nama badan usaha sesuai dokumen.' : 'Sesuai identitas resmi (KTP / passport).'"></p>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full border-slate-300" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <fieldset class="space-y-5 border-0 p-0 m-0" x-bind:disabled="role !== 'customer'">
            <legend class="sr-only">Kontak jamaah</legend>
            <div x-show="role === 'customer'" x-cloak class="space-y-5">
                <div>
                    <x-input-label for="phone_customer" value="No. WhatsApp" />
                    <x-text-input id="phone_customer" class="block mt-1 w-full border-slate-300" type="text" name="phone" :value="old('phone')" placeholder="08xxxxxxxxxx" autocomplete="tel" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                @include('auth.partials.register-otp', ['roleGate' => 'customer'])

                <div>
                    <x-input-label for="address_customer" value="Alamat" />
                    <textarea id="address_customer" name="address" rows="3" class="block mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" placeholder="Alamat lengkap">{{ old('address') }}</textarea>
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>

                <div x-show="customerType === 'company'" x-cloak>
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

        <fieldset x-show="role === 'muthowif'" x-cloak class="space-y-5 pt-2 border-t border-slate-200 m-0 min-w-0 border-0 p-0" x-bind:disabled="role !== 'muthowif'">
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
                <x-input-file id="photo" name="photo" accept="image/jpeg,image/png,image/webp" />
                <p class="mt-1 text-xs text-slate-500">Wajah jelas — JPG, PNG, atau WebP, maks. 5 MB.</p>
                <x-input-error :messages="$errors->get('photo')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="ktp_image" value="Foto / scan KTP" />
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

            <x-repeating-file-field
                name="supporting_documents"
                label="Dokumen pendukung :"
                item-label="Dokumen"
                add-label="Tambah dokumen"
                hint="PDF atau gambar — tiap file maks. 10 MB, hingga 20 file total."
            />

            <p class="text-sm font-medium text-slate-800 pt-2 border-t border-slate-100">Kontak &amp; identitas</p>

            <div>
                <x-input-label for="phone_muthowif" value="No. HP / WhatsApp" />
                <x-text-input id="phone_muthowif" class="block mt-1 w-full border-slate-300" type="text" name="phone" :value="old('phone')" placeholder="08xxxxxxxxxx" autocomplete="tel" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>

            @include('auth.partials.register-otp', ['roleGate' => 'muthowif'])

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
            <x-primary-button class="w-full sm:w-auto justify-center" x-bind:disabled="otpEnabled && !phoneVerified && (role === 'customer' || role === 'muthowif')">
                Daftar
            </x-primary-button>
        </div>
    </form>

    <script>
        function registerFormData() {
            return {
                otpRoutes: @json($registerOtpRoutes),
                otpWaitTpl: @json(__('auth_otp.wait')),
                otpJs: {
                    phoneRequired: @json(__('auth_otp.js_phone_required')),
                    sendFailed: @json(__('auth_otp.js_send_failed_fallback')),
                    sendOk: @json(__('auth_otp.js_send_ok_fallback')),
                    codeDigits: @json(__('auth_otp.js_code_digits')),
                    verifyFailed: @json(__('auth_otp.js_verify_failed_fallback')),
                    verifyOk: @json(__('auth_otp.js_verify_ok_fallback')),
                },
                role: @json(old('role', 'customer')),
                customerType: @json(old('customer_type', 'personal')),
                otpEnabled: @json($otpEnabled),
                phoneVerified: @json($phoneVerifiedInitial ?? false),
                otpSendLoading: false,
                otpVerifyLoading: false,
                otpFeedback: '',
                otpCode: '',
                resendCooldown: 0,
                _resendTimer: null,
                phoneFieldId() {
                    return this.role === 'customer' ? 'phone_customer' : 'phone_muthowif';
                },
                phoneValue() {
                    const el = document.getElementById(this.phoneFieldId());
                    return el ? String(el.value).trim() : '';
                },
                async sendOtp() {
                    if (!this.otpEnabled) return;
                    this.otpFeedback = '';
                    const phone = this.phoneValue();
                    if (phone.length < 10) {
                        this.otpFeedback = this.otpJs.phoneRequired;
                        return;
                    }
                    this.otpSendLoading = true;
                    try {
                        const res = await fetch(this.otpRoutes.send, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({ phone, role: this.role }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            const msg = data.errors?.phone?.[0] || data.message || this.otpJs.sendFailed;
                            throw new Error(msg);
                        }
                        this.otpFeedback = data.message || this.otpJs.sendOk;
                        this.phoneVerified = false;
                        this.startResendCooldown();
                    } catch (e) {
                        this.otpFeedback = e.message || this.otpJs.sendFailed;
                    } finally {
                        this.otpSendLoading = false;
                    }
                },
                startResendCooldown() {
                    if (this._resendTimer) {
                        clearInterval(this._resendTimer);
                        this._resendTimer = null;
                    }
                    this.resendCooldown = 60;
                    this._resendTimer = setInterval(() => {
                        this.resendCooldown--;
                        if (this.resendCooldown <= 0) {
                            clearInterval(this._resendTimer);
                            this._resendTimer = null;
                        }
                    }, 1000);
                },
                async verifyOtp() {
                    if (!this.otpEnabled) return;
                    this.otpFeedback = '';
                    const phone = this.phoneValue();
                    const otp = String(this.otpCode).replace(/\D/g, '');
                    if (otp.length !== 6) {
                        this.otpFeedback = this.otpJs.codeDigits;
                        return;
                    }
                    this.otpVerifyLoading = true;
                    try {
                        const res = await fetch(this.otpRoutes.verify, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({ phone, otp }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            const msg = data.errors?.otp?.[0] || data.message || this.otpJs.verifyFailed;
                            throw new Error(msg);
                        }
                        this.phoneVerified = true;
                        this.otpFeedback = data.message || this.otpJs.verifyOk;
                    } catch (e) {
                        this.otpFeedback = e.message || this.otpJs.verifyFailed;
                    } finally {
                        this.otpVerifyLoading = false;
                    }
                },
                async clearOtpSession() {
                    try {
                        await fetch(this.otpRoutes.clear, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({}),
                        });
                    } catch (_) {}
                },
                async onPhoneChange() {
                    this.phoneVerified = false;
                    this.otpCode = '';
                    this.otpFeedback = '';
                    await this.clearOtpSession();
                },
            };
        }
    </script>
</x-guest-layout>
