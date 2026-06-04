<section x-data="profileForm">
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-950">Akun & Profil</h1>
                <p class="mt-1 text-sm text-slate-500">Kelola informasi profil publik muthowif Anda.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if (($publicPreviewUrl ?? null) !== null)
                    <a href="{{ $publicPreviewUrl }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        Preview Profil
                    </a>
                @endif
                <button form="profile-main-form" type="submit" x-bind:disabled="loading" class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="loading" class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" x-cloak></span>
                    <span x-text="loading ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                </button>
            </div>
        </div>

        <nav class="mt-5 flex gap-1 overflow-x-auto border-b border-slate-100 text-sm font-semibold text-slate-500" aria-label="Navigasi profil">
            <a href="#profile-basic" class="border-b-2 border-brand-600 px-3 py-3 text-brand-700">Profil</a>
            <a href="#profile-documents" class="border-b-2 border-transparent px-3 py-3 hover:text-brand-700">Dokumen</a>
            <a href="#profile-experience" class="border-b-2 border-transparent px-3 py-3 hover:text-brand-700">Pengalaman</a>
            <a href="#profile-referral" class="border-b-2 border-transparent px-3 py-3 hover:text-brand-700">Referensi</a>
            <a href="#profile-security" class="border-b-2 border-transparent px-3 py-3 hover:text-brand-700">Keamanan</a>
        </nav>
    </div>

    <form id="profile-main-form" method="post" action="{{ route('profile.public.update') }}" enctype="multipart/form-data" data-submit-lock="off" @submit="submit" class="space-y-6 p-5 sm:p-6">
        @csrf
        @method('patch')

        <!-- Error alert banner -->
        <div x-show="errorMessage" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 shadow-sm" x-cloak>
            <p class="font-semibold" x-text="errorMessage"></p>
            <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs text-red-850">
                <template x-for="(messages, key) in errors" :key="key">
                    <template x-for="msg in messages" :key="msg">
                        <li x-text="msg"></li>
                    </template>
                </template>
            </ul>
        </div>

        <section id="profile-basic" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-xs font-bold text-brand-700">ID</span>
                <div>
                    <h2 class="text-sm font-bold text-slate-950">Informasi Dasar</h2>
                    <p class="text-xs text-slate-500">Data utama yang tampil di profil publik.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="public_name" :value="__('profile.fields.name')" />
                    <x-text-input id="public_name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autocomplete="name" />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" field="name" />
                </div>

                <div>
                    <x-input-label for="public_email" :value="__('profile.fields.email')" />
                    <x-text-input id="public_email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
                    <x-input-error class="mt-2" :messages="$errors->get('email')" field="email" />
                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <p class="mt-2 text-xs text-slate-700">
                            {{ __('profile.verification.unverified') }}
                            <x-submit-button form="send-verification" class="font-medium text-brand-700 underline decoration-brand-700/30 underline-offset-2 hover:text-brand-800">
                                {{ __('profile.verification.resend') }}
                            </x-submit-button>
                        </p>
                    @endif
                </div>

                <div>
                    <x-input-label for="public_phone" :value="__('profile_public.phone')" />
                    <x-text-input id="public_phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $muthowifProfile->phone)" autocomplete="tel" />
                    <x-input-error class="mt-2" :messages="$errors->get('phone')" field="phone" />
                </div>

                <div>
                    <x-input-label for="public_passport_number" :value="__('profile_public.passport')" />
                    <x-text-input id="public_passport_number" name="passport_number" type="text" class="mt-1 block w-full" :value="old('passport_number', $muthowifProfile->passport_number)" />
                    <x-input-error class="mt-2" :messages="$errors->get('passport_number')" field="passport_number" />
                </div>

                <div>
                    <x-input-label for="public_birth_date" :value="__('profile_public.birth_date')" />
                    <x-text-input id="public_birth_date" name="birth_date" type="date" class="mt-1 block w-full" :value="old('birth_date', optional($muthowifProfile->birth_date)->toDateString())" />
                    <x-input-error class="mt-2" :messages="$errors->get('birth_date')" field="birth_date" />
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="public_address" :value="__('profile_public.address')" />
                    <textarea id="public_address" name="address" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('address', $muthowifProfile->address) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('address')" field="address" />
                </div>
            </div>
        </section>

        <section id="profile-documents" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-700">DOC</span>
                <div>
                    <h2 class="text-sm font-bold text-slate-950">Dokumen Pendukung</h2>
                    <p class="text-xs text-slate-500">Upload dokumen untuk verifikasi dan profil publik Anda.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3" x-data="{ showInput: false }">
                    <p class="text-sm font-semibold text-slate-900">Foto Profil</p>
                    <p class="text-xs text-slate-500">Foto profesional opsional</p>
                    <div class="mt-3 overflow-hidden rounded-lg bg-white ring-1 ring-slate-200">
                        @if (filled($muthowifProfile->photo_path))
                            <img src="{{ route('profile.public.photo') }}" alt="{{ __('profile_public.current_photo') }}" class="h-36 w-full object-cover" />
                        @else
                            <div class="flex h-36 items-center justify-center text-xs text-slate-400">Belum ada foto</div>
                        @endif
                    </div>
                    <button type="button" @click="showInput = !showInput" class="mt-3 inline-flex items-center gap-2 rounded-md bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                        </svg>
                        <span x-text="showInput ? 'Batal' : 'Ubah foto'"></span>
                    </button>
                    <div x-show="showInput" x-transition class="mt-2">
                        <x-input-file id="public_photo" name="photo" accept="image/jpeg,image/png,image/webp" />
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('photo')" field="photo" />
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3" x-data="{ showInput: false }">
                    <p class="text-sm font-semibold text-slate-900">KTP</p>
                    <p class="text-xs text-slate-500">Maksimal 5MB, JPG/PNG/WebP</p>
                    <div class="mt-3 overflow-hidden rounded-lg bg-white ring-1 ring-slate-200">
                        @if (filled($muthowifProfile->ktp_image_path))
                            <img src="{{ route('profile.public.ktp') }}" alt="{{ __('profile_public.current_ktp_image') }}" class="h-36 w-full object-cover" />
                        @else
                            <div class="flex h-36 items-center justify-center text-xs text-slate-400">Belum ada KTP</div>
                        @endif
                    </div>
                    <button type="button" @click="showInput = !showInput" class="mt-3 inline-flex items-center gap-2 rounded-md bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                        </svg>
                        <span x-text="showInput ? 'Batal' : 'Ubah KTP'"></span>
                    </button>
                    <div x-show="showInput" x-transition class="mt-2">
                        <x-input-file id="public_ktp_image" name="ktp_image" accept="image/jpeg,image/png,image/webp" />
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('ktp_image')" field="ktp_image" />
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 md:col-span-2 xl:col-span-1">
                    <p class="text-sm font-semibold text-slate-900">{{ __('profile_public.supporting_documents') }}</p>
                    <p class="text-xs text-slate-500">PDF atau gambar pendukung</p>

                    <div class="mt-3 max-h-36 space-y-2 overflow-y-auto pr-1">
                        @forelse ($muthowifProfile->supportingDocuments as $document)
                            @php
                                $documentName = $document->original_name ?? basename($document->path);
                                $documentUrl = route('profile.public.document', $document);
                            @endphp
                            <div class="flex items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs">
                                <a href="{{ $documentUrl }}" target="_blank" rel="noopener" class="min-w-0 flex-1 truncate font-medium text-slate-700 hover:text-brand-700">{{ $documentName }}</a>
                                <label class="inline-flex shrink-0 items-center gap-1 text-rose-700">
                                    <input type="checkbox" name="delete_supporting_documents[]" value="{{ $document->id }}" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500" />
                                    Hapus
                                </label>
                            </div>
                        @empty
                            <div class="flex h-20 items-center justify-center rounded-lg border border-dashed border-slate-200 bg-white text-xs text-slate-400">
                                {{ __('profile_public.no_supporting_documents') }}
                            </div>
                        @endforelse
                    </div>

                    <details class="mt-3 rounded-lg border border-dashed border-slate-300 bg-white">
                        <summary class="cursor-pointer px-3 py-2 text-xs font-semibold text-brand-700">Tambah dokumen</summary>
                        <div class="border-t border-slate-100 p-3">
                            <x-repeating-file-field
                                name="supporting_documents"
                                :item-label="__('profile_public.supporting_document_item')"
                                :add-label="__('profile_public.supporting_document_add')"
                                :hint="__('profile_public.supporting_documents_hint')"
                            />
                        </div>
                    </details>
                    <x-input-error class="mt-2" :messages="$errors->get('delete_supporting_documents')" field="delete_supporting_documents" />
                </div>
            </div>
        </section>

        <section id="profile-experience" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-xs font-bold text-amber-700">CV</span>
                <div>
                    <h2 class="text-sm font-bold text-slate-950">Pengalaman</h2>
                    <p class="text-xs text-slate-500">Bahasa, pendidikan, dan pengalaman kerja.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <x-repeating-text-field
                    name="languages"
                    :label="__('profile_public.languages')"
                    :item-label="__('profile_public.language_item')"
                    :placeholder="__('profile_public.language_placeholder')"
                    :add-label="__('profile_public.language_add')"
                    :items="$muthowifProfile->languagesForDisplay() ?: ['']"
                />

                <x-repeating-text-field
                    name="educations"
                    :label="__('profile_public.education')"
                    :item-label="__('profile_public.education_item')"
                    :placeholder="__('profile_public.education_placeholder')"
                    :add-label="__('profile_public.education_add')"
                    :items="$muthowifProfile->educationsForDisplay() ?: ['']"
                />

                <x-repeating-text-field
                    name="work_experiences"
                    :label="__('profile_public.work')"
                    :item-label="__('profile_public.work_item')"
                    :placeholder="__('profile_public.work_placeholder')"
                    :add-label="__('profile_public.work_add')"
                    :items="$muthowifProfile->workExperiencesForDisplay() ?: ['']"
                />
            </div>
        </section>

        <section id="profile-referral" class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <x-input-label for="public_reference_text" :value="__('profile_public.reference')" />
                <textarea id="public_reference_text" name="reference_text" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('reference_text', $muthowifProfile->reference_text) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('reference_text')" field="reference_text" />
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h3 class="text-sm font-bold text-slate-950">{{ __('profile_public.referral_section_title') }}</h3>
                <p class="mt-1 text-xs text-slate-500">{{ __('profile_public.referral_section_subtitle') }}</p>

                <div class="mt-4 rounded-xl border border-slate-200/90 bg-slate-50/80 p-4 ring-1 ring-slate-100/80">
                <x-input-label :value="__('profile_public.my_referral_code_label')" />
                @if (filled($muthowifProfile->referral_code))
                    <p class="mt-2 font-mono text-lg font-semibold tracking-wide text-slate-900 select-all">{{ $muthowifProfile->referral_code }}</p>
                    <p class="mt-2 text-xs text-slate-600">{{ __('profile_public.my_referral_code_hint') }}</p>
                @else
                    <p class="mt-2 text-sm text-slate-600">{{ __('profile_public.my_referral_code_pending') }}</p>
                @endif
            </div>

                <div class="mt-4">
                @if ($muthowifProfile->referred_by_muthowif_profile_id !== null && $muthowifProfile->referredBy)
                    <x-input-label :value="__('profile_public.inviter_label')" />
                    <p class="mt-2 text-sm text-slate-800">
                        {{ $muthowifProfile->referredBy->user?->name ?? '—' }}
                        @if (filled($muthowifProfile->referredBy->referral_code))
                            <span class="text-slate-500">·</span>
                            <span class="font-mono font-medium text-slate-900">{{ $muthowifProfile->referredBy->referral_code }}</span>
                        @endif
                    </p>
                    <p class="mt-2 text-xs text-slate-500">{{ __('profile_public.inviter_locked_hint') }}</p>
                @else
                    <x-input-label for="inviter_referral_code" :value="__('profile_public.inviter_code_label')" />
                    <x-text-input
                        id="inviter_referral_code"
                        name="inviter_referral_code"
                        type="text"
                        class="mt-1 block w-full font-mono uppercase"
                        :value="old('inviter_referral_code')"
                        maxlength="16"
                        autocomplete="off"
                        :placeholder="__('profile_public.inviter_code_placeholder')"
                    />
                    <p class="mt-1 text-xs text-slate-500">{{ __('profile_public.inviter_code_hint') }}</p>
                    <x-input-error class="mt-2" :messages="$errors->get('inviter_referral_code')" field="inviter_referral_code" />
                @endif
            </div>
            </div>
        </section>
    </form>
</section>
