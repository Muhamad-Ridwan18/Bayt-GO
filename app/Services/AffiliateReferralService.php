<?php

namespace App\Services;

use App\Enums\AffiliateStatus;
use App\Models\Affiliate;
use App\Models\AffiliateClick;
use App\Models\MuthowifBooking;
use App\Support\AffiliateReferralCapture;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AffiliateReferralService
{
    public function __construct(
        private readonly AffiliateRegistrationService $registration,
    ) {}

    /**
     * Capture ?ref= / /r/{code}, persist session+cookie, and record a click (deduped per session).
     */
    public function captureFromRequest(Request $request): ?Affiliate
    {
        $raw = $request->query('ref');
        if (! is_string($raw) || $raw === '') {
            if ($request->routeIs('affiliate.landing')) {
                $raw = $request->route('code');
            }
        }

        $code = $this->registration->normalizeCode(is_string($raw) ? $raw : null);
        if ($code === null) {
            return null;
        }

        /** @var Affiliate|null $affiliate */
        $affiliate = Affiliate::query()
            ->where('code', $code)
            ->where('status', AffiliateStatus::Active->value)
            ->first();

        if ($affiliate === null) {
            return null;
        }

        AffiliateReferralCapture::remember($request, $affiliate->code);
        $this->recordClick($request, $affiliate);

        return $affiliate;
    }

    public function recordClick(Request $request, Affiliate $affiliate): ?AffiliateClick
    {
        $sessionFlag = AffiliateReferralCapture::CLICK_SESSION_PREFIX.$affiliate->id;
        if ($request->session()->get($sessionFlag) === true) {
            return null;
        }

        $visitorKey = $this->visitorKey($request);
        $click = AffiliateClick::query()->create([
            'affiliate_id' => $affiliate->id,
            'code_snapshot' => $affiliate->code,
            'visitor_key' => $visitorKey,
            'ip_hash' => $request->ip() ? hash('sha256', $request->ip()) : null,
            'user_agent' => Str::limit((string) $request->userAgent(), 512, ''),
            'landing_path' => Str::limit('/'.$request->path().($request->getQueryString() ? '?'.$request->getQueryString() : ''), 512, ''),
            'created_at' => now(),
        ]);

        $request->session()->put($sessionFlag, true);

        return $click;
    }

    public function markConverted(MuthowifBooking $booking, ?Request $request = null): void
    {
        if ($booking->affiliate_id === null) {
            return;
        }

        $request ??= request();

        // One conversion per capture: do not keep attributing later bookings to the same ?ref=.
        AffiliateReferralCapture::clear($request);

        $visitorKey = $this->visitorKey($request);

        /** @var AffiliateClick|null $click */
        $click = AffiliateClick::query()
            ->where('affiliate_id', $booking->affiliate_id)
            ->whereNull('converted_at')
            ->where('visitor_key', $visitorKey)
            ->orderByDesc('created_at')
            ->first();

        if ($click === null) {
            $click = AffiliateClick::query()
                ->where('affiliate_id', $booking->affiliate_id)
                ->whereNull('converted_at')
                ->orderByDesc('created_at')
                ->first();
        }

        if ($click === null) {
            return;
        }

        $click->converted_booking_id = $booking->id;
        $click->converted_at = now();
        $click->save();
    }

    private function visitorKey(Request $request): string
    {
        $sessionId = $request->session()->getId();
        if (is_string($sessionId) && $sessionId !== '') {
            return substr(hash('sha256', 'session:'.$sessionId), 0, 64);
        }

        $ip = $request->ip() ?? 'unknown';
        $ua = (string) $request->userAgent();

        return substr(hash('sha256', 'anon:'.$ip.'|'.$ua), 0, 64);
    }
}
