<?php

namespace App\Http\Middleware;

use App\Services\AffiliateReferralService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureAffiliateReferral
{
    public function __construct(
        private readonly AffiliateReferralService $referrals,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')) {
            $this->referrals->captureFromRequest($request);
        }

        return $next($request);
    }
}
