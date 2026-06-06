<x-app-layout>
    <div id="profile-container" class="ui-page-y-compact min-h-[calc(100vh-4rem)] bg-slate-50">
        @if ($muthowifProfile)
            @php
                $profileChecks = [
                    filled($user->name) && filled($user->email),
                    filled($muthowifProfile->phone) && filled($muthowifProfile->address) && filled($muthowifProfile->birth_date),
                    filled($muthowifProfile->photo_path),
                    filled($muthowifProfile->ktp_image_path),
                    filled($muthowifProfile->passport_number),
                    count($muthowifProfile->languagesForDisplay()) > 0 || count($muthowifProfile->workExperiencesForDisplay()) > 0,
                ];
                $profileComplete = collect($profileChecks)->filter()->count();
                $profilePercent = (int) round(($profileComplete / count($profileChecks)) * 100);
                $publicPreviewUrl = $muthowifProfile->isApproved() ? route('layanan.show', $muthowifProfile) : null;
            @endphp

            <x-page-container class="grid grid-cols-1 gap-6 lg:grid-cols-[17rem_minmax(0,1fr)]">
                <aside class="space-y-4 lg:sticky lg:top-6 lg:self-start">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="h-20 bg-gradient-to-br from-brand-500 to-emerald-500"></div>
                        <div class="-mt-12 px-5 pb-5 text-center">
                            <div class="relative mx-auto h-24 w-24 overflow-hidden rounded-full border-4 border-white bg-slate-100 shadow-sm">
                                @if (filled($muthowifProfile->photo_path))
                                    <img src="{{ route('profile.public.photo') }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-2xl font-bold text-slate-400">
                                        {{ strtoupper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>

                            <h2 class="mt-3 text-base font-bold text-slate-950">{{ $user->name }}</h2>
                            <p class="text-xs font-medium text-slate-500">Muthowif Profesional</p>

                            <div class="mt-5 text-left">
                                <div class="flex items-center justify-between text-xs font-semibold text-slate-700">
                                    <span>Profil Anda</span>
                                    <span>{{ $profilePercent }}% lengkap</span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-brand-600" style="width: {{ $profilePercent }}%"></div>
                                </div>
                            </div>

                            <div class="mt-5 space-y-2 text-left text-xs">
                                <div class="flex items-center gap-2 text-slate-700">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full {{ $profileChecks[0] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">✓</span>
                                    Data pribadi
                                </div>
                                <div class="flex items-center gap-2 text-slate-700">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full {{ $profileChecks[2] && $profileChecks[3] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">✓</span>
                                    Dokumen identitas
                                </div>
                                <div class="flex items-center gap-2 text-slate-700">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full {{ $profileChecks[5] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">✓</span>
                                    Pengalaman
                                </div>
                                <div class="flex items-center gap-2 text-slate-700">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full {{ filled($muthowifProfile->referral_code) || filled($muthowifProfile->referred_by_muthowif_profile_id) ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-400' }}">✓</span>
                                    Referensi
                                </div>
                            </div>

                            @if ($publicPreviewUrl)
                                <a href="{{ $publicPreviewUrl }}" target="_blank" rel="noopener" class="mt-5 inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-brand-200 hover:bg-brand-50">
                                    Preview Profil Publik
                                </a>
                            @else
                                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-center text-xs font-medium text-slate-500">
                                    Preview aktif setelah disetujui
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-sm font-bold text-slate-900">Butuh bantuan?</p>
                        <p class="mt-1 text-xs text-slate-500">Hubungi tim jika ada kendala dokumen atau identitas.</p>
                        @if (Route::has('support.index'))
                            <a href="{{ route('support.index') }}" class="mt-4 inline-flex w-full items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                Tiket Bantuan
                                <span>&rarr;</span>
                            </a>
                        @endif
                    </div>
                </aside>

                <main class="min-w-0">
                    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        @include('profile.partials.update-public-profile-form', ['publicPreviewUrl' => $publicPreviewUrl])
                    </div>

                    <div id="profile-security" class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <details>
                            <summary class="flex cursor-pointer items-center justify-between gap-4 px-5 py-4 text-sm font-semibold text-slate-900 transition hover:bg-slate-50 sm:px-6">
                                <span>Keamanan akun</span>
                                <span class="text-xs font-medium text-slate-500">Password & hapus akun</span>
                            </summary>

                            <div class="grid grid-cols-1 gap-6 border-t border-slate-100 p-5 sm:p-6 lg:grid-cols-2">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                    @include('profile.partials.update-password-form')
                                </div>
                                <div class="rounded-2xl border border-red-100 bg-red-50/50 p-4">
                                    @include('profile.partials.delete-user-form')
                                </div>
                            </div>
                        </details>
                    </div>
                </main>
            </x-page-container>
        @else
            <x-page-container class="relative ui-stack-compact">
                <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/80 shadow-sm ring-1 ring-slate-100/80">
                    <div class="flex min-w-0">
                        <div class="w-1 shrink-0 bg-brand-500" aria-hidden="true"></div>
                        <div class="min-w-0 flex-1 p-5 sm:p-6">
                            <div class="max-w-xl">
                                @include('profile.partials.update-profile-information-form')
                            </div>
                        </div>
                    </div>
                </div>
                <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80">
                    <details>
                        <summary class="flex cursor-pointer items-center justify-between gap-4 px-5 py-4 text-sm font-semibold text-slate-900 transition hover:bg-slate-50 sm:px-6">
                            <span>Keamanan akun</span>
                            <span class="text-xs font-medium text-slate-500">Password & hapus akun</span>
                        </summary>
                        <div class="grid grid-cols-1 gap-6 border-t border-slate-100 p-5 sm:p-6 lg:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                @include('profile.partials.update-password-form')
                            </div>
                            <div class="rounded-2xl border border-red-100 bg-red-50/50 p-4">
                                @include('profile.partials.delete-user-form')
                            </div>
                        </div>
                    </details>
                </div>
            </x-page-container>
        @endif
    </div>
</x-app-layout>
