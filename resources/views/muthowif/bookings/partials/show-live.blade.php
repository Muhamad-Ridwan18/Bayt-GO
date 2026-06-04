<x-page-container class="pb-8">
    <a href="{{ route('muthowif.bookings.index') }}" class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-brand-700 transition hover:text-brand-800">
        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
        </svg>
        {{ __('muthowif.booking_show.back_list') }}
    </a>

    @include('muthowif.bookings.partials.show-grid', [
        'booking' => $booking,
        'addonsById' => $addonsById,
        'peerRecommendTargets' => $peerRecommendTargets ?? collect(),
        'referralRewardFromPay' => $referralRewardFromPay ?? 0,
    ])
</x-page-container>
