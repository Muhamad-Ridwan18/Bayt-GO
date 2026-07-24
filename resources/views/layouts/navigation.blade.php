{{-- Badge counts from App\View\Composers\NavigationComposer --}}

<nav
    x-data="{
        open: false,
        init() {
            this.$watch('open', (value) => {
                document.body.classList.toggle('overflow-hidden', value && window.innerWidth < 1024);
            });
        },
    }"
    class="sticky top-0 z-[90] border-b border-slate-200/80 bg-white shadow-sm"
    @resize.window="if (window.innerWidth >= 1024) { open = false }"
    @keydown.window.escape="open = false"
>
    <!-- Primary Navigation Menu -->
    <x-page-container>
        <div class="flex min-h-16 min-w-0 items-center justify-between gap-2">
            <div class="flex min-w-0 flex-1 items-center lg:flex-initial lg:gap-0">
                <!-- Logo -->
                <div class="flex shrink-0 items-center">
                    <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2">
                        <x-site-logo variant="nav" />
                        <span class="truncate text-lg font-bold tracking-tight text-baytgo">Bayt<span class="text-gold-muted">Go</span></span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden min-w-0 lg:-my-px lg:ms-10 lg:flex lg:space-x-6 xl:space-x-8">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('nav.home') }}
                    </x-nav-link>
                    @if (Auth::user()->isCustomer())
                        <x-nav-link :href="route('bookings.index')" :active="request()->routeIs('bookings.*')">
                            {{ __('nav.my_bookings') }}
                        </x-nav-link>
                    @endif
                    @if (Auth::user()->isCustomer() || Auth::user()->isMuthowif())
                        <x-nav-link :href="route('support.index')" :active="request()->routeIs('support.*')">
                            {{ __('nav.support_tickets') }}
                        </x-nav-link>
                    @endif
                    @if (Auth::user()->isCustomer())
                        <x-nav-link :href="route('affiliate.index')" :active="request()->routeIs('affiliate.*')">
                            {{ __('nav.affiliate') }}
                        </x-nav-link>
                    @elseif (Auth::user()->isMuthowif() && ! Auth::user()->isVerifiedMuthowif())
                        <x-nav-link :href="route('affiliate.index')" :active="request()->routeIs('affiliate.*')">
                            {{ __('nav.affiliate') }}
                        </x-nav-link>
                    @endif
                    @if (Auth::user()->isAdmin())
                        <x-nav-link :href="route('admin.finance.index')" :active="request()->routeIs('admin.finance.*')">
                            {{ __('nav.finance') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.service_monitor.index')" :active="request()->routeIs('admin.service_monitor.*')">
                            {{ __('nav.service_monitor') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.refunds.index')" :active="request()->routeIs('admin.refunds.*')">
                            {{ __('nav.refund') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.emergency.index')" :active="request()->routeIs('admin.emergency.*')">
                            <span class="inline-flex items-center gap-2">
                                {{ __('nav.emergency') }}
                                <span
                                    x-data="adminEmergencyReportsBadge({
                                        countUrl: @js(route('admin.emergency.open-report-count')),
                                        toastLabel: @js(__('emergency.admin.new_report_toast')),
                                        initialCount: @js($adminOpenEmergencyReportCount),
                                    })"
                                    x-show="count > 0"
                                    x-cloak
                                    class="inline-flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-amber-600 px-1 text-[10px] font-bold leading-none text-white shadow-sm"
                                    x-bind:aria-label="count > 0 ? '{{ __('nav.emergency') }}: ' + displayLabel : null"
                                    x-text="displayLabel"
                                ></span>
                            </span>
                        </x-nav-link>
                        <x-nav-link :href="route('admin.withdrawals.index')" :active="request()->routeIs('admin.withdrawals.*')">
                            {{ __('nav.withdraw') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.muthowif.index', ['status' => 'pending'])" :active="request()->routeIs('admin.muthowif.*')">
                            <span class="inline-flex items-center gap-2">
                                {{ __('nav.verify_muthowif') }}
                                @if ($adminPendingMuthowifCount > 0)
                                    <span class="inline-flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-red-600 px-2 text-[10px] font-semibold leading-none text-white">
                                        {{ $adminPendingMuthowifCount }}
                                    </span>
                                @endif
                            </span>
                        </x-nav-link>
                        <x-nav-link :href="route('admin.settings.index')" :active="$adminHubActive">
                            {{ __('nav.admin_settings') }}
                        </x-nav-link>
                    @endif
                    @if (Auth::user()->isVerifiedMuthowif())
                        <div class="inline-flex items-center self-stretch">
                            <x-dropdown align="left" width="w-56">
                                <x-slot name="trigger">
                                    <button type="button" @class([
                                        'inline-flex items-center gap-1.5 border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none',
                                        'border-gold text-slate-900 focus:border-gold-muted' => $muthowifManageActive,
                                        'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 focus:border-slate-300 focus:text-slate-700' => ! $muthowifManageActive,
                                    ])>
                                        <span>{{ __('nav.manage_menu') }}</span>
                                        <span
                                            x-data="muthowifPendingBookingsBadge({
                                                userId: @js(auth()->id()),
                                                countUrl: @js(route('muthowif.bookings.pending-incoming-count')),
                                                initialCount: @js($muthowifPendingIncomingCount),
                                            })"
                                            x-show="count > 0"
                                            x-cloak
                                            class="inline-flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-none text-white shadow-sm"
                                            x-bind:aria-label="count > 0 ? '{{ __('nav.booking_requests') }}: ' + displayLabel : null"
                                            x-text="displayLabel"
                                        ></span>
                                        <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    <x-dropdown-link :href="route('muthowif.kelola-layanan')">
                                        {{ __('nav.manage_services') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('muthowif.bookings.index')">
                                        <span class="inline-flex w-full items-center justify-between gap-2">
                                            <span>{{ __('nav.booking_requests') }}</span>
                                            <span
                                                x-data="muthowifPendingBookingsBadge({
                                                    userId: @js(auth()->id()),
                                                    countUrl: @js(route('muthowif.bookings.pending-incoming-count')),
                                                    initialCount: @js($muthowifPendingIncomingCount),
                                                })"
                                                x-show="count > 0"
                                                x-cloak
                                                class="inline-flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-none text-white shadow-sm"
                                                x-text="displayLabel"
                                            ></span>
                                        </span>
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('affiliate.index')">
                                        {{ __('nav.affiliate') }}
                                    </x-dropdown-link>
                                </x-slot>
                            </x-dropdown>
                        </div>
                        <x-nav-link :href="route('muthowif.jadwal.index')" :active="request()->routeIs('muthowif.jadwal.*')">
                            {{ __('nav.day_off') }}
                        </x-nav-link>
                        <x-nav-link :href="route('muthowif.portfolio.index')" :active="request()->routeIs('muthowif.portfolio.*')">
                            {{ __('nav.portfolio') }}
                        </x-nav-link>
                        <x-nav-link :href="route('muthowif.emergency-offers.index')" :active="request()->routeIs('muthowif.emergency-offers.*')">
                            <span class="inline-flex items-center gap-2">
                                {{ __('nav.emergency_offers') }}
                                <span
                                    x-data="muthowifEmergencyOffersBadge({
                                        userId: @js(auth()->id()),
                                        countUrl: @js(route('muthowif.emergency-offers.pending-offer-count')),
                                        toastLabel: @js(__('emergency.muthowif.new_offer_toast')),
                                        initialCount: @js($muthowifPendingEmergencyOfferCount),
                                    })"
                                    x-show="count > 0"
                                    x-cloak
                                    class="inline-flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-amber-600 px-1 text-[10px] font-bold leading-none text-white shadow-sm"
                                    x-bind:aria-label="count > 0 ? '{{ __('nav.emergency_offers') }}: ' + displayLabel : null"
                                    x-text="displayLabel"
                                ></span>
                            </span>
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2 sm:ms-4 lg:ms-6">
                <x-language-switcher variant="segment" class="hidden lg:inline-flex" />
                <div class="relative hidden lg:block">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-slate-500 bg-white hover:text-slate-700 focus:outline-none transition ease-in-out duration-150">
                                <div>{{ Auth::user()->name }}</div>

                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('nav.profile') }}
                            </x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
                <button
                    type="button"
                    class="inline-flex shrink-0 items-center justify-center rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 focus:bg-slate-100 focus:text-slate-700 focus:outline-none lg:hidden"
                    @click="open = ! open"
                    :aria-expanded="open"
                    aria-controls="responsive-main-nav"
                >
                    <span class="sr-only">{{ __('nav.open_menu') }}</span>
                    <svg class="h-6 w-6 shrink-0" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </x-page-container>

    <!-- Responsive Navigation Menu: full-screen sheet -->
    <template x-teleport="body">
        <div
            id="responsive-main-nav"
            x-show="open"
            x-cloak
            class="fixed inset-0 z-[300] flex h-[100dvh] flex-col bg-white lg:hidden"
            role="dialog"
            aria-modal="true"
            :aria-hidden="(! open).toString()"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
        >
                <div class="flex min-h-16 shrink-0 items-center justify-between gap-3 border-b border-slate-100 px-4">
                    <a href="{{ route('dashboard') }}" @click="open = false" class="flex min-w-0 items-center gap-2">
                        <x-site-logo variant="nav" />
                        <span class="truncate text-lg font-bold tracking-tight text-baytgo">Bayt<span class="text-gold-muted">Go</span></span>
                    </a>
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50"
                        @click="open = false"
                    >
                        <span class="sr-only">{{ __('nav.open_menu') }}</span>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('nav.language') }}</p>
                        <div class="mt-2 flex justify-start">
                            <x-language-switcher variant="segment" />
                        </div>
                    </div>
                    <div class="space-y-1 py-2">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" @click="open = false">
                {{ __('nav.home') }}
            </x-responsive-nav-link>
            @if (Auth::user()->isCustomer())
                <x-responsive-nav-link :href="route('bookings.index')" :active="request()->routeIs('bookings.*')">
                    {{ __('nav.my_bookings') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->isCustomer() || Auth::user()->isMuthowif())
                <x-responsive-nav-link :href="route('support.index')" :active="request()->routeIs('support.*')">
                    {{ __('nav.support_tickets') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->isCustomer())
                <x-responsive-nav-link :href="route('affiliate.index')" :active="request()->routeIs('affiliate.*')">
                    {{ __('nav.affiliate') }}
                </x-responsive-nav-link>
            @elseif (Auth::user()->isMuthowif() && ! Auth::user()->isVerifiedMuthowif())
                <x-responsive-nav-link :href="route('affiliate.index')" :active="request()->routeIs('affiliate.*')">
                    {{ __('nav.affiliate') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('admin.finance.index')" :active="request()->routeIs('admin.finance.*')">
                    {{ __('nav.finance') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.service_monitor.index')" :active="request()->routeIs('admin.service_monitor.*')">
                    {{ __('nav.service_monitor') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.refunds.index')" :active="request()->routeIs('admin.refunds.*')">
                    {{ __('nav.refund') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.emergency.index')" :active="request()->routeIs('admin.emergency.*')">
                    <span class="inline-flex items-center gap-2">
                        {{ __('nav.emergency') }}
                        <span
                            x-data="adminEmergencyReportsBadge({
                                countUrl: @js(route('admin.emergency.open-report-count')),
                                toastLabel: @js(__('emergency.admin.new_report_toast')),
                                initialCount: @js($adminOpenEmergencyReportCount),
                            })"
                            x-show="count > 0"
                            x-cloak
                            class="inline-flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-amber-600 px-1 text-[10px] font-bold leading-none text-white shadow-sm"
                            x-bind:aria-label="count > 0 ? '{{ __('nav.emergency') }}: ' + displayLabel : null"
                            x-text="displayLabel"
                        ></span>
                    </span>
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.withdrawals.index')" :active="request()->routeIs('admin.withdrawals.*')">
                    {{ __('nav.withdraw') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.muthowif.index', ['status' => 'pending'])" :active="request()->routeIs('admin.muthowif.*')">
                    <span class="inline-flex items-center gap-2">
                        {{ __('nav.verify_muthowif') }}
                        @if ($adminPendingMuthowifCount > 0)
                            <span class="inline-flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-red-600 px-2 text-[10px] font-semibold leading-none text-white">
                                {{ $adminPendingMuthowifCount }}
                            </span>
                        @endif
                    </span>
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.settings.index')" :active="$adminHubActive">
                    {{ __('nav.admin_settings') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->isVerifiedMuthowif())
                <div class="space-y-1" x-data="{ manageOpen: @js($muthowifManageActive) }">
                    <button
                        type="button"
                        @click="manageOpen = ! manageOpen"
                        @class([
                            'flex w-full items-center justify-between gap-2 border-l-4 py-2 pe-4 ps-3 text-start text-base font-medium transition duration-150 ease-in-out focus:outline-none',
                            'border-gold bg-gold-light/30 text-baytgo focus:bg-gold-light/40' => $muthowifManageActive,
                            'border-transparent text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800' => ! $muthowifManageActive,
                        ])
                    >
                        <span class="inline-flex items-center gap-2">
                            {{ __('nav.manage_menu') }}
                            <span
                                x-data="muthowifPendingBookingsBadge({
                                    userId: @js(auth()->id()),
                                    countUrl: @js(route('muthowif.bookings.pending-incoming-count')),
                                    initialCount: @js($muthowifPendingIncomingCount),
                                })"
                                x-show="count > 0"
                                x-cloak
                                class="inline-flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-none text-white shadow-sm"
                                x-text="displayLabel"
                            ></span>
                        </span>
                        <svg class="h-4 w-4 shrink-0 transition" :class="{ 'rotate-180': manageOpen }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="manageOpen" x-cloak class="space-y-1 border-l border-slate-200 ms-3">
                        <x-responsive-nav-link :href="route('muthowif.kelola-layanan')" :active="request()->routeIs(['muthowif.kelola-layanan', 'muthowif.pelayanan.*', 'muthowif.pelayanan-pendukung.*'])">
                            {{ __('nav.manage_services') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('muthowif.bookings.index')" :active="request()->routeIs('muthowif.bookings.*')">
                            <span class="inline-flex items-center gap-2">
                                {{ __('nav.booking_requests') }}
                                <span
                                    x-data="muthowifPendingBookingsBadge({
                                        userId: @js(auth()->id()),
                                        countUrl: @js(route('muthowif.bookings.pending-incoming-count')),
                                        initialCount: @js($muthowifPendingIncomingCount),
                                    })"
                                    x-show="count > 0"
                                    x-cloak
                                    class="inline-flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-none text-white shadow-sm"
                                    x-text="displayLabel"
                                ></span>
                            </span>
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('affiliate.index')" :active="request()->routeIs('affiliate.*')">
                            {{ __('nav.affiliate') }}
                        </x-responsive-nav-link>
                    </div>
                </div>
                <x-responsive-nav-link :href="route('muthowif.jadwal.index')" :active="request()->routeIs('muthowif.jadwal.*')">
                    {{ __('nav.day_off') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('muthowif.portfolio.index')" :active="request()->routeIs('muthowif.portfolio.*')">
                    {{ __('nav.portfolio') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('muthowif.emergency-offers.index')" :active="request()->routeIs('muthowif.emergency-offers.*')">
                    <span class="inline-flex items-center gap-2">
                        {{ __('nav.emergency_offers') }}
                        <span
                            x-data="muthowifEmergencyOffersBadge({
                                userId: @js(auth()->id()),
                                countUrl: @js(route('muthowif.emergency-offers.pending-offer-count')),
                                toastLabel: @js(__('emergency.muthowif.new_offer_toast')),
                                initialCount: @js($muthowifPendingEmergencyOfferCount),
                            })"
                            x-show="count > 0"
                            x-cloak
                            class="inline-flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-amber-600 px-1 text-[10px] font-bold leading-none text-white shadow-sm"
                            x-text="displayLabel"
                        ></span>
                    </span>
                </x-responsive-nav-link>
            @endif
                    </div>
                </div>

                <div class="shrink-0 border-t border-slate-200 bg-white px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
                    <div class="mb-2">
                        <div class="truncate font-medium text-base text-slate-800">{{ Auth::user()->name }}</div>
                        <div class="truncate font-medium text-sm text-slate-500">{{ Auth::user()->email }}</div>
                    </div>
                    <div class="space-y-1">
                        <x-responsive-nav-link :href="route('profile.edit')" @click="open = false">
                            {{ __('nav.profile') }}
                        </x-responsive-nav-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-responsive-nav-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-responsive-nav-link>
                        </form>
                    </div>
                </div>
        </div>
    </template>
</nav>
