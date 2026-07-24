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
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <x-site-logo variant="nav" />
                        <span class="shrink-0 whitespace-nowrap text-lg font-bold tracking-tight text-baytgo">Bayt<span class="text-gold-muted">Go</span></span>
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
    @php
        $firstName = explode(' ', trim((string) Auth::user()->name))[0] ?: Auth::user()->name;
        $initial = mb_strtoupper(mb_substr($firstName, 0, 1));
    @endphp
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
            <div class="flex min-h-16 shrink-0 items-center justify-between gap-3 px-5 pt-3">
                <a href="{{ route('dashboard') }}" @click="open = false" class="flex items-center gap-2.5">
                    <x-site-logo variant="nav" />
                    <span class="shrink-0 whitespace-nowrap text-lg font-bold tracking-tight text-baytgo">Bayt<span class="text-gold-muted">Go</span></span>
                </a>
                <button
                    type="button"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                    @click="open = false"
                >
                    <span class="sr-only">{{ __('nav.close_menu') }}</span>
                    <x-nav-icon name="x" class="h-6 w-6" />
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 pb-4">
                <div class="flex items-center justify-between gap-3 py-3">
                    <p class="min-w-0 text-[15px] leading-snug text-slate-600">
                        {{ __('nav.greeting') }}
                        <span class="font-bold text-baytgo">{{ $firstName }}</span>
                    </p>
                    <x-language-switcher variant="segment" class="shrink-0" />
                </div>

                <p class="mt-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">{{ __('nav.menu_section') }}</p>
                <div class="mt-2 space-y-0.5">
                    <x-mobile-nav-item :href="route('dashboard')" :active="request()->routeIs('dashboard')" @click="open = false">
                        <x-slot:icon><x-nav-icon name="home" /></x-slot:icon>
                        {{ __('nav.home') }}
                    </x-mobile-nav-item>

                    @if (Auth::user()->isCustomer())
                        <x-mobile-nav-item :href="route('bookings.index')" :active="request()->routeIs('bookings.*')" @click="open = false">
                            <x-slot:icon><x-nav-icon name="clipboard" /></x-slot:icon>
                            {{ __('nav.my_bookings') }}
                        </x-mobile-nav-item>
                    @endif

                    @if (Auth::user()->isCustomer() || Auth::user()->isMuthowif())
                        <x-mobile-nav-item :href="route('support.index')" :active="request()->routeIs('support.*')" @click="open = false">
                            <x-slot:icon><x-nav-icon name="help" /></x-slot:icon>
                            {{ __('nav.support_tickets') }}
                        </x-mobile-nav-item>
                    @endif

                    @if (Auth::user()->isCustomer())
                        <x-mobile-nav-item :href="route('affiliate.index')" :active="request()->routeIs('affiliate.*')" @click="open = false">
                            <x-slot:icon><x-nav-icon name="link" /></x-slot:icon>
                            {{ __('nav.affiliate') }}
                        </x-mobile-nav-item>
                    @elseif (Auth::user()->isMuthowif() && ! Auth::user()->isVerifiedMuthowif())
                        <x-mobile-nav-item :href="route('affiliate.index')" :active="request()->routeIs('affiliate.*')" @click="open = false">
                            <x-slot:icon><x-nav-icon name="link" /></x-slot:icon>
                            {{ __('nav.affiliate') }}
                        </x-mobile-nav-item>
                    @endif

                    @if (Auth::user()->isAdmin())
                        <x-mobile-nav-item :href="route('admin.finance.index')" :active="request()->routeIs('admin.finance.*')" @click="open = false">
                            <x-slot:icon><x-nav-icon name="cash" /></x-slot:icon>
                            {{ __('nav.finance') }}
                        </x-mobile-nav-item>
                        <x-mobile-nav-item :href="route('admin.service_monitor.index')" :active="request()->routeIs('admin.service_monitor.*')" @click="open = false">
                            <x-slot:icon><x-nav-icon name="chart" /></x-slot:icon>
                            {{ __('nav.service_monitor') }}
                        </x-mobile-nav-item>
                        <x-mobile-nav-item :href="route('admin.refunds.index')" :active="request()->routeIs('admin.refunds.*')" @click="open = false">
                            <x-slot:icon><x-nav-icon name="cash" /></x-slot:icon>
                            {{ __('nav.refund') }}
                        </x-mobile-nav-item>
                        <x-mobile-nav-item :href="route('admin.emergency.index')" :active="request()->routeIs('admin.emergency.*')" @click="open = false">
                            <x-slot:icon><x-nav-icon name="shield" /></x-slot:icon>
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
                                    x-text="displayLabel"
                                ></span>
                            </span>
                        </x-mobile-nav-item>
                        <x-mobile-nav-item :href="route('admin.withdrawals.index')" :active="request()->routeIs('admin.withdrawals.*')" @click="open = false">
                            <x-slot:icon><x-nav-icon name="cash" /></x-slot:icon>
                            {{ __('nav.withdraw') }}
                        </x-mobile-nav-item>
                        <x-mobile-nav-item :href="route('admin.muthowif.index', ['status' => 'pending'])" :active="request()->routeIs('admin.muthowif.*')" @click="open = false">
                            <x-slot:icon><x-nav-icon name="users" /></x-slot:icon>
                            <span class="inline-flex items-center gap-2">
                                {{ __('nav.verify_muthowif') }}
                                @if ($adminPendingMuthowifCount > 0)
                                    <span class="inline-flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-red-600 px-2 text-[10px] font-semibold leading-none text-white">
                                        {{ $adminPendingMuthowifCount }}
                                    </span>
                                @endif
                            </span>
                        </x-mobile-nav-item>
                        <x-mobile-nav-item :href="route('admin.settings.index')" :active="$adminHubActive" @click="open = false">
                            <x-slot:icon><x-nav-icon name="cog" /></x-slot:icon>
                            {{ __('nav.admin_settings') }}
                        </x-mobile-nav-item>
                    @endif

                    @if (Auth::user()->isVerifiedMuthowif())
                        <div class="rounded-2xl bg-slate-50/90 p-1.5" x-data="{ manageOpen: @js($muthowifManageActive) }">
                            <button
                                type="button"
                                @click="manageOpen = ! manageOpen"
                                class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-start text-[15px] font-medium text-slate-700 transition hover:bg-white focus:outline-none"
                            >
                                <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center text-slate-500" aria-hidden="true">
                                    <x-nav-icon name="briefcase" />
                                </span>
                                <span class="min-w-0 flex-1 inline-flex items-center gap-2">
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
                                <svg class="h-4 w-4 shrink-0 text-slate-400 transition" :class="{ 'rotate-180': manageOpen }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="manageOpen" x-cloak class="relative ms-5 space-y-0.5 border-s border-dotted border-slate-300 py-1 ps-4">
                                <x-mobile-nav-item :href="route('muthowif.kelola-layanan')" :active="request()->routeIs(['muthowif.kelola-layanan', 'muthowif.pelayanan.*', 'muthowif.pelayanan-pendukung.*'])" @click="open = false">
                                    {{ __('nav.manage_services') }}
                                </x-mobile-nav-item>
                                <x-mobile-nav-item :href="route('muthowif.bookings.index')" :active="request()->routeIs('muthowif.bookings.*')" @click="open = false">
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
                                </x-mobile-nav-item>
                                <x-mobile-nav-item :href="route('affiliate.index')" :active="request()->routeIs('affiliate.*')" @click="open = false">
                                    {{ __('nav.affiliate') }}
                                </x-mobile-nav-item>
                            </div>
                        </div>

                        <x-mobile-nav-item :href="route('muthowif.jadwal.index')" :active="request()->routeIs('muthowif.jadwal.*')" @click="open = false">
                            <x-slot:icon><x-nav-icon name="calendar" /></x-slot:icon>
                            {{ __('nav.day_off') }}
                        </x-mobile-nav-item>
                        <x-mobile-nav-item :href="route('muthowif.portfolio.index')" :active="request()->routeIs('muthowif.portfolio.*')" @click="open = false">
                            <x-slot:icon><x-nav-icon name="images" /></x-slot:icon>
                            {{ __('nav.portfolio') }}
                        </x-mobile-nav-item>
                        <x-mobile-nav-item :href="route('muthowif.emergency-offers.index')" :active="request()->routeIs('muthowif.emergency-offers.*')" @click="open = false">
                            <x-slot:icon><x-nav-icon name="users" /></x-slot:icon>
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
                        </x-mobile-nav-item>
                    @endif
                </div>

                <div class="my-5 border-t border-slate-100"></div>

                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">{{ __('nav.account_section') }}</p>

                <a
                    href="{{ route('profile.edit') }}"
                    @click="open = false"
                    class="relative mt-3 flex items-center gap-3 overflow-hidden rounded-2xl bg-slate-50 px-3.5 py-3 transition hover:bg-slate-100"
                >
                    <span class="pointer-events-none absolute -bottom-4 -end-3 h-20 w-20 rounded-full bg-gold/15"></span>
                    <span class="pointer-events-none absolute -bottom-1 end-6 h-10 w-10 rounded-full bg-gold/10"></span>
                    <span class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-baytgo text-base font-bold text-white">{{ $initial }}</span>
                    <span class="relative min-w-0 flex-1">
                        <span class="block truncate text-[15px] font-semibold text-baytgo">{{ Auth::user()->name }}</span>
                        <span class="mt-0.5 block truncate text-sm text-slate-500">{{ Auth::user()->email }}</span>
                    </span>
                    <x-nav-icon name="chevron-right" class="relative h-5 w-5 shrink-0 text-slate-400" />
                </a>

                <div class="mt-2 space-y-0.5 pb-[max(0.5rem,env(safe-area-inset-bottom))]">
                    <x-mobile-nav-item :href="route('profile.edit')" @click="open = false">
                        <x-slot:icon><x-nav-icon name="user" /></x-slot:icon>
                        {{ __('nav.profile') }}
                    </x-mobile-nav-item>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-mobile-nav-item :href="route('logout')" danger onclick="event.preventDefault(); this.closest('form').submit();">
                            <x-slot:icon><x-nav-icon name="logout" /></x-slot:icon>
                            {{ __('Log Out') }}
                        </x-mobile-nav-item>
                    </form>
                </div>
            </div>
        </div>
    </template>
</nav>
