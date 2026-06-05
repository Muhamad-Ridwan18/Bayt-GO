<x-app-layout>
    <div class="py-8 sm:py-12">
        <x-page-container class="space-y-6">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-900 via-brand-900 to-amber-950 p-8 text-white shadow-xl ring-1 ring-white/10">
                <div class="relative">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-200/90">{{ __('admin.settings_hub.badge') }}</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight">{{ __('admin.settings_hub.title') }}</h1>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/80">{{ __('admin.settings_hub.subtitle') }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm hover:bg-brand-50">
                            {{ __('admin.settings_hub.back_dashboard') }}
                        </a>
                    </x-page-container>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <ul class="divide-y divide-slate-100" role="list">
                    <li>
                        <a href="{{ route('admin.site-appearance.edit') }}" class="flex items-center gap-4 py-4 transition hover:bg-slate-50/80">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.384 2.382a1.125 1.125 0 01-.57 1.667l-.908.356c-.378.146-.677.478-.764.892-.068.387-.086.783-.068 1.176.018.394.069.783.173 1.162.106.389.297.743.569 1.044l1.086 1.196a1.125 1.125 0 01.009 1.518l-.93 1.14a1.125 1.125 0 01-1.592.068l-.356-.907c-.196-.332-.596-.547-1.019-.547h-9.094c-.424 0-.823.215-1.019.547l-.356.907a1.125 1.125 0 01-1.592-.068l-.93-1.14a1.125 1.125 0 01.009-1.518l1.086-1.196c.272-.302.463-.656.569-1.044.103-.379.154-.769.173-1.162.019-.394-.068-.789-.069-1.176-.068-.389-.367-.739-.764-.892l-.908-.356a1.125 1.125 0 01-.569-1.667l1.384-2.382a1.125 1.125 0 011.371-.489l1.217.455c.355.134.749.073 1.075-.124.074-.046.146-.086.223-.129.331-.182.579-.489.642-.867l.213-1.281Z" /></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-900">{{ __('admin.settings_hub.card_appearance') }}</p>
                                <p class="text-xs text-slate-500">{{ __('admin.settings_hub.card_appearance_sub') }}</p>
                            </div>
                            <svg class="h-5 w-5 shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.articles.index') }}" class="flex items-center gap-4 py-4 transition hover:bg-slate-50/80">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-100 text-teal-800" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-900">{{ __('admin.settings_hub.card_articles') }}</p>
                                <p class="text-xs text-slate-500">{{ __('admin.settings_hub.card_articles_sub') }}</p>
                            </div>
                            <svg class="h-5 w-5 shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.campaign.index') }}" class="flex items-center gap-4 py-4 transition hover:bg-slate-50/80">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-pink-100 text-pink-700" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-900">Campaign & Event</p>
                                <p class="text-xs text-slate-500">Kelola banner promo dan halaman event.</p>
                            </div>
                            <svg class="h-5 w-5 shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.users.index') }}" class="flex items-center gap-4 py-4 transition hover:bg-slate-50/80">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.813-2.387M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-900">{{ __('admin.settings_hub.card_users') }}</p>
                                <p class="text-xs text-slate-500">{{ __('admin.settings_hub.card_users_sub') }}</p>
                            </div>
                            <svg class="h-5 w-5 shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.whatsapp-broadcast.index') }}" class="flex items-center gap-4 py-4 transition hover:bg-slate-50/80">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-green-100 text-green-700" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-900">{{ __('admin.settings_hub.card_whatsapp_broadcast') }}</p>
                                <p class="text-xs text-slate-500">{{ __('admin.settings_hub.card_whatsapp_broadcast_sub') }}</p>
                            </div>
                            <svg class="h-5 w-5 shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.muthowif.index') }}" class="flex items-center gap-4 py-4 transition hover:bg-slate-50/80">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-900">{{ __('admin.settings_hub.card_verify_muthowif') }}</p>
                                <p class="text-xs text-slate-500">{{ __('admin.settings_hub.card_verify_muthowif_sub') }}</p>
                            </div>
                            <svg class="h-5 w-5 shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.support-tickets.index') }}" class="flex items-center gap-4 py-4 transition hover:bg-slate-50/80">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-800" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.016.69.059 1.02.12l2.218-2.217a.75.75 0 01.579-.24h1.515a2.25 2.25 0 002.25-2.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-900">{{ __('admin.settings_hub.card_support_admin') }}</p>
                                <p class="text-xs text-slate-500">{{ __('admin.settings_hub.card_support_admin_sub') }}</p>
                            </div>
                            <svg class="h-5 w-5 shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.moota_webhooks.live') }}" class="flex items-center gap-4 py-4 transition hover:bg-slate-50/80">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-200 text-slate-800" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.036a9.056 9.056 0 00-.22-.966l-.522-2.08a25.794 25.794 0 01-9.086-16.982V9.087l2.047.746a26.086 26.086 0 0115.087 22.547l-.546 3.894M15 8.25l3.097 3.097M12 21v-8.25" /></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-900">{{ __('admin.settings_hub.card_moota_live') }}</p>
                                <p class="text-xs text-slate-500">{{ __('admin.settings_hub.card_moota_live_sub') }}</p>
                            </div>
                            <svg class="h-5 w-5 shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.moota_webhooks.testing') }}" class="flex items-center gap-4 py-4 transition hover:bg-slate-50/80">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-100 text-orange-800" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655-5.653a2.548 2.548 0 010-3.586L11.42 2.83a2.548 2.548 0 013.586 0l4.365 5.661" /></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-900">{{ __('admin.settings_hub.card_moota_test') }}</p>
                                <p class="text-xs text-slate-500">{{ __('admin.settings_hub.card_moota_test_sub') }}</p>
                            </div>
                            <svg class="h-5 w-5 shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('log-viewer.index') }}" class="flex items-center gap-4 py-4 transition hover:bg-slate-50/80">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600" aria-hidden="true">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-900">{{ __('admin.settings_hub.card_logs') }}</p>
                                <p class="text-xs text-slate-500">{{ __('admin.settings_hub.card_logs_sub') }}</p>
                            </div>
                            <svg class="h-5 w-5 shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
