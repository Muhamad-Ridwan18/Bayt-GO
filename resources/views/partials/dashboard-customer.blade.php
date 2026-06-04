@php
    use App\Enums\BookingStatus;
    use App\Enums\MuthowifVerificationStatus;
    use App\Enums\PaymentStatus;
    use App\Enums\SupportTicketStatus;
    use App\Models\BookingReview;
    use App\Models\MuthowifBooking;
    use App\Models\MuthowifProfile;
    use App\Models\SupportTicket;

    $user = Auth::user();
    $userId = $user->getKey();

    $welcomeHeroBg = null;
    foreach (['webp', 'png', 'jpg', 'jpeg'] as $ext) {
        if (file_exists(public_path('images/bg-welcome.'.$ext))) {
            $welcomeHeroBg = asset('images/bg-welcome.'.$ext);
            break;
        }
    }
    if ($welcomeHeroBg === null && is_dir(public_path('images/bg-welcome'))) {
        $entries = array_diff(scandir(public_path('images/bg-welcome')) ?: [], ['.', '..']);
        sort($entries, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($entries as $name) {
            if (preg_match('/\.(jpe?g|png|webp)$/i', $name)) {
                $welcomeHeroBg = asset('images/bg-welcome/'.$name);
                break;
            }
        }
    }
    if ($welcomeHeroBg === null) {
        $welcomeHeroBg = file_exists(public_path('images/welcome-hero.jpg'))
            ? asset('images/welcome-hero.jpg')
            : 'https://images.unsplash.com/photo-1519817914152-22d216bb9170?q=85&w=2160&auto=format&fit=crop';
    }

    $activeBookingCount = (int) MuthowifBooking::query()
        ->where('customer_id', $userId)
        ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
        ->count();

    $supportOpenCount = (int) SupportTicket::query()
        ->where('user_id', $userId)
        ->whereIn('status', [
            SupportTicketStatus::Open,
            SupportTicketStatus::InProgress,
            SupportTicketStatus::AwaitingCustomer,
        ])
        ->count();

    $upcomingTripCount = (int) MuthowifBooking::query()
        ->where('customer_id', $userId)
        ->whereNotIn('status', [BookingStatus::Cancelled])
        ->whereDate('starts_on', '>=', now()->toDateString())
        ->count();

    $reviewsGivenCount = (int) BookingReview::query()
        ->where('customer_id', $userId)
        ->count();

    $nextBooking = MuthowifBooking::query()
        ->where('customer_id', $userId)
        ->whereNotIn('status', [BookingStatus::Cancelled])
        ->whereDate('ends_on', '>=', now()->toDateString())
        ->orderBy('starts_on')
        ->with(['muthowifProfile.user'])
        ->first();

    $featuredMuthowifs = MuthowifProfile::query()
        ->with(['user:id,name', 'services:id,muthowif_profile_id,daily_price'])
        ->where('verification_status', MuthowifVerificationStatus::Approved)
        ->withCount('bookingReviews')
        ->withAvg('bookingReviews', 'rating')
        ->withCount(['bookings as completed_trips_count' => fn ($q) => $q->where('status', BookingStatus::Completed)])
        ->orderByDesc('booking_reviews_count')
        ->orderByDesc('verified_at')
        ->limit(8)
        ->get();

    $contactWaRaw = (string) (config('app.contact_whatsapp') ?: config('app.contact_phone'));
    $contactDigits = preg_replace('/\D+/', '', $contactWaRaw) ?? '';
    $contactWaLink = $contactDigits !== '' ? 'https://wa.me/'.$contactDigits : null;
    $contactPhoneDisplay = config('app.contact_phone') ?: config('app.contact_whatsapp');
    $contactEmail = (string) (config('mail.from.address') ?? '');

    $customerGuideCards = __('dashboard.customer_guide_cards');
    if (! is_array($customerGuideCards)) {
        $customerGuideCards = [];
    }

    $supportHref = Route::has('support.create')
        ? route('support.create')
        : (Route::has('support.index') ? route('support.index') : null);
@endphp

<div class="scroll-smooth">
    @include('partials.dashboard-customer-layout')
</div>
