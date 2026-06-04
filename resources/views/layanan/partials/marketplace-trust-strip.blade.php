<section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm ring-1 ring-slate-100/90 sm:p-6" aria-label="{{ __('marketplace.trust.verified_title') }}">
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 sm:gap-6">
        @foreach ([
            ['icon' => 'shield', 'title' => 'marketplace.trust.verified_title', 'desc' => 'marketplace.trust.verified_desc', 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-700'],
            ['icon' => 'calendar', 'title' => 'marketplace.trust.schedule_title', 'desc' => 'marketplace.trust.schedule_desc', 'bg' => 'bg-sky-50', 'text' => 'text-sky-700'],
            ['icon' => 'lock', 'title' => 'marketplace.trust.payment_title', 'desc' => 'marketplace.trust.payment_desc', 'bg' => 'bg-violet-50', 'text' => 'text-violet-700'],
            ['icon' => 'headset', 'title' => 'marketplace.trust.support_title', 'desc' => 'marketplace.trust.support_desc', 'bg' => 'bg-amber-50', 'text' => 'text-amber-800'],
        ] as $item)
            <div class="text-center sm:text-left">
                <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl {{ $item['bg'] }} {{ $item['text'] }} sm:mx-0" aria-hidden="true">
                    @if ($item['icon'] === 'shield')
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 01.466.747l-.286 2.051a.75.75 0 01-.548.582 11.319 11.319 0 00-4.702 2.271.75.75 0 01-.826-.033l-1.64-1.117a.75.75 0 00-.987.052l-1.378 1.378a.75.75 0 00.052.987l1.117 1.64a.75.75 0 01.033.826 11.32 11.32 0 00-2.27 4.702.75.75 0 01-.582.548l-2.051.286a.75.75 0 00-.747.466V12a.75.75 0 00.747-.466l-2.051-.286a.75.75 0 01-.548-.582 11.32 11.32 0 00-2.27-4.702.75.75 0 01.033-.826l1.117-1.64a.75.75 0 00.052-.987L18.72 9.53a.75.75 0 00-.987-.052l-1.64 1.117a.75.75 0 01-.826.033 11.317 11.317 0 00-4.702-2.27.75.75 0 01-.582-.548l-.286-2.051A.75.75 0 0012.516 2.17z" clip-rule="evenodd" /></svg>
                    @elseif ($item['icon'] === 'calendar')
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" /></svg>
                    @elseif ($item['icon'] === 'lock')
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                    @else
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.72 1.072c-.442.663-1.32.902-2.027.55a12.284 12.284 0 01-7.4-7.4c-.352-.707-.113-1.585.55-2.027l1.072-.72c.363-.271.527-.732.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 6.75z" /></svg>
                    @endif
                </span>
                <p class="mt-2 text-xs font-bold text-slate-900 sm:text-sm">{{ __($item['title']) }}</p>
                <p class="mt-0.5 text-[10px] leading-snug text-slate-500 sm:text-[11px]">{{ __($item['desc']) }}</p>
            </div>
        @endforeach
    </div>
</section>
