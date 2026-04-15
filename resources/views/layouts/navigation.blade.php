@php
    $contactRaw = (string) (config('app.contact_whatsapp') ?: config('app.contact_phone'));
    $contactDigits = preg_replace('/\D+/', '', $contactRaw ?? '') ?? '';
    $contactLink = $contactDigits !== '' ? 'https://wa.me/'.$contactDigits : null;
@endphp

<nav x-data="{ open: false }" class="relative z-[90] bg-white/90 backdrop-blur border-b border-slate-200/80 shadow-sm">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ url('/') }}" class="flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-white text-xs font-bold">BG</span>
                        <span class="text-lg font-semibold text-slate-900 hidden sm:inline">Bayt<span class="text-brand-600">Go</span></span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Beranda
                    </x-nav-link>
                    @if (Auth::user()->isCustomer())
                        <x-nav-link :href="route('layanan.index')" :active="request()->routeIs('layanan.*')">
                            Cari muthowif
                        </x-nav-link>
                        <x-nav-link :href="route('bookings.index')" :active="request()->routeIs('bookings.*')">
                            Booking saya
                        </x-nav-link>
                    @endif
                    @if (Auth::user()->isAdmin())
                        <x-nav-link :href="route('admin.finance.index')" :active="request()->routeIs('admin.finance.*')">
                            Keuangan
                        </x-nav-link>
                        <x-nav-link :href="route('admin.refunds.index')" :active="request()->routeIs('admin.refunds.*')">
                            Refund
                        </x-nav-link>
                        <x-nav-link :href="route('admin.withdrawals.index')" :active="request()->routeIs('admin.withdrawals.*')">
                            Withdraw
                        </x-nav-link>
                        <x-nav-link :href="route('admin.muthowif.index')" :active="request()->routeIs('admin.muthowif.*')">
                            Verifikasi muthowif
                        </x-nav-link>
                    @endif
                    @if (Auth::user()->isVerifiedMuthowif())
                        <x-nav-link :href="route('muthowif.pelayanan.edit')" :active="request()->routeIs('muthowif.pelayanan.*')">
                            Pelayanan
                        </x-nav-link>
                        <x-nav-link :href="route('muthowif.jadwal.index')" :active="request()->routeIs('muthowif.jadwal.*')">
                            Jadwal libur
                        </x-nav-link>
                        <x-nav-link :href="route('muthowif.bookings.index')" :active="request()->routeIs('muthowif.bookings.*')">
                            Permintaan booking
                        </x-nav-link>
                    @endif
                    @if ($contactLink)
                        <a href="{{ $contactLink }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center text-sm font-medium text-brand-700 hover:text-brand-800">
                            Contact us
                        </a>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
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
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
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

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                Beranda
            </x-responsive-nav-link>
            @if (Auth::user()->isCustomer())
                <x-responsive-nav-link :href="route('layanan.index')" :active="request()->routeIs('layanan.*')">
                    Cari muthowif
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('bookings.index')" :active="request()->routeIs('bookings.*')">
                    Booking saya
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('admin.finance.index')" :active="request()->routeIs('admin.finance.*')">
                    Keuangan
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.refunds.index')" :active="request()->routeIs('admin.refunds.*')">
                    Refund
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.withdrawals.index')" :active="request()->routeIs('admin.withdrawals.*')">
                    Withdraw
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.muthowif.index')" :active="request()->routeIs('admin.muthowif.*')">
                    Verifikasi muthowif
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->isVerifiedMuthowif())
                <x-responsive-nav-link :href="route('muthowif.pelayanan.edit')" :active="request()->routeIs('muthowif.pelayanan.*')">
                    Pelayanan
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('muthowif.jadwal.index')" :active="request()->routeIs('muthowif.jadwal.*')">
                    Jadwal libur
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('muthowif.bookings.index')" :active="request()->routeIs('muthowif.bookings.*')">
                    Permintaan booking
                </x-responsive-nav-link>
            @endif
            @if ($contactLink)
                <a href="{{ $contactLink }}" target="_blank" rel="noopener noreferrer" class="block px-4 py-2 text-sm font-medium text-brand-700 hover:bg-slate-50">
                    Contact us
                </a>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
