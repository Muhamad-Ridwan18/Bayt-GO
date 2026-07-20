<fieldset
    x-show="selectedRole === 'muthowif'"
    class="m-0 min-w-0 space-y-5 border-0 border-t border-slate-200 p-0 pt-2"
    x-bind:disabled="selectedRole !== 'muthowif'"
    @style(['display: none' => ! $page->isMuthowif()])
>
    <legend class="sr-only">Data muthowif</legend>
    <p class="text-sm font-medium text-slate-800">Data muthowif</p>
    <p class="mt-[-0.75rem] text-xs text-slate-500">Gunakan tombol <strong>+ Tambah</strong> untuk menambah baris bahasa, studi, pengalaman, atau dokumen.</p>

    <div>
        <x-input-label for="birth_date" value="Tanggal lahir" required />
        <x-text-input id="birth_date" class="mt-1 block w-full border-slate-300" type="date" name="birth_date" :value="old('birth_date')" />
        <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="passport_number" value="No. passport" required />
        <x-text-input id="passport_number" class="mt-1 block w-full border-slate-300" type="text" name="passport_number" :value="old('passport_number')" autocomplete="off" />
        <x-input-error :messages="$errors->get('passport_number')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="photo" value="Upload foto profil" required />
        @if ($page->cachedPhoto)
            <x-auth.cached-upload
                :label="$page->cachedPhoto['label']"
                :remove-payload="['type' => 'photo']"
            />
        @endif
        <x-input-file id="photo" name="photo" accept="image/jpeg,image/png,image/webp" />
        <p class="mt-1 text-xs text-slate-500">Wajah jelas — JPG, PNG, atau WebP, maks. 5 MB.</p>
        <x-input-error :messages="$errors->get('photo')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="ktp_image" value="Foto / scan KTP" required />
        @if ($page->cachedKtp)
            <x-auth.cached-upload
                :label="$page->cachedKtp['label']"
                :remove-payload="['type' => 'ktp_image']"
            />
        @endif
        <x-input-file id="ktp_image" name="ktp_image" accept="image/jpeg,image/png,image/webp" />
        <p class="mt-1 text-xs text-slate-500">Pastikan teks pada KTP terbaca.</p>
        <x-input-error :messages="$errors->get('ktp_image')" class="mt-2" />
    </div>

    <x-repeating-text-field
        name="languages"
        label="Penguasaan bahasa :"
        :required="true"
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
        <textarea id="reference_text" name="reference_text" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Nama lembaga, kontak, atau keterangan referensi">{{ old('reference_text') }}</textarea>
        <x-input-error :messages="$errors->get('reference_text')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="muthowif_referral_code" :value="__('auth_custom.muthowif_referral_code_label')" />
        <x-text-input id="muthowif_referral_code" class="mt-1 block w-full border-slate-300 font-mono uppercase" type="text" name="muthowif_referral_code" :value="old('muthowif_referral_code')" maxlength="16" autocomplete="off" placeholder="{{ __('auth_custom.muthowif_referral_code_placeholder') }}" />
        <p class="mt-1 text-xs text-slate-500">{{ __('auth_custom.muthowif_referral_code_hint') }}</p>
        <x-input-error :messages="$errors->get('muthowif_referral_code')" class="mt-2" />
    </div>

    @if ($page->hasCachedSupportingDocuments())
        <div class="space-y-2">
            <span class="block text-sm font-medium text-slate-700">Dokumen pendukung yang sudah terunggah:</span>
            <div class="grid grid-cols-1 gap-2">
                @foreach ($page->cachedSupportingDocuments as $doc)
                    <x-auth.cached-upload
                        class="mb-0"
                        :label="$doc['original_name']"
                        :remove-payload="$doc['remove']"
                    />
                @endforeach
            </div>
            <p class="mt-1 text-xs italic text-slate-500">Anda bisa menambahkan dokumen baru di bawah ini jika diperlukan:</p>
        </div>
    @endif

    <x-repeating-file-field
        name="supporting_documents"
        label="Dokumen pendukung :"
        item-label="Dokumen"
        add-label="Tambah dokumen"
        hint="Contoh: CV, portofolio, sertifikat; PDF atau gambar — tiap file maks. 10 MB, hingga 20 file total."
    />

    <p class="border-t border-slate-100 pt-2 text-sm font-medium text-slate-800">Alamat &amp; identitas</p>

    <div>
        <x-input-label for="address_muthowif" value="Alamat lengkap" required />
        <textarea id="address_muthowif" name="address" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Alamat domisili sesuai KTP">{{ old('address') }}</textarea>
        <x-input-error :messages="$errors->get('address')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="work_location" :value="__('profile_public.work_location')" required />
        <x-text-input id="work_location" class="mt-1 block w-full border-slate-300" type="text" name="work_location" :value="old('work_location')" maxlength="255" autocomplete="address-level2" :placeholder="__('profile_public.work_location_placeholder')" />
        <p class="mt-1 text-xs text-slate-500">{{ __('profile_public.work_location_hint') }}</p>
        <x-input-error :messages="$errors->get('work_location')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="nik" value="NIK (16 digit)" required />
        <x-text-input id="nik" class="mt-1 block w-full border-slate-300" type="text" name="nik" inputmode="numeric" maxlength="16" :value="old('nik')" placeholder="3201xxxxxxxxxxxx" />
        <x-input-error :messages="$errors->get('nik')" class="mt-2" />
    </div>
</fieldset>
