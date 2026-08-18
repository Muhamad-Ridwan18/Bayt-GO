@php
    use App\Support\IndonesianNumber;
    use App\Support\PlatformFee;
    use App\Support\SiteBrand;
    use Carbon\Carbon;

    $fmt = fn (float|int $n) => IndonesianNumber::formatThousands((string) (int) round((float) $n));
    $isCompany = $booking->customer?->isCompanyCustomer() ?? false;
    $split = PlatformFee::split((float) $booking->resolvedAmountDue(), $isCompany);
    $base = (float) ($split['base'] ?? 0.0);
    $customerPlatformFee = (float) ($split['customer_fee'] ?? 0.0);
    $gross = (float) ($split['customer_gross'] ?? 0.0);
    $isSupport = $booking->isSupport();
    $packageLabel = $booking->package_name_snapshot
        ?? $booking->supportPackage?->name
        ?? null;
    $scheduleLabel = $isSupport
        ? ($booking->starts_at
            ? $booking->starts_at->timezone(config('app.timezone'))->format('d/m/Y H:i')
            : ($booking->starts_on?->format('d/m/Y') ?? '—'))
        : (Carbon::parse($booking->starts_on)->format('d/m/Y').' – '.Carbon::parse($booking->ends_on)->format('d/m/Y'));
    $subtitle = $isSupport
        ? __('bookings.invoice.subtitle_support')
        : __('bookings.invoice.subtitle');
    $docRef = filled($booking->booking_code) ? $booking->booking_code : (string) $booking->getKey();
    $logoUrl = $logoUrl ?? SiteBrand::logoPublicUrl();
    $appName = config('app.name', 'BaytGo');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('bookings.invoice.title', ['app' => $appName]) }}</title>
</head>
<body style="margin:0;padding:0;background:#eef3f1;font-family:'Segoe UI',Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef3f1;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #d7e3df;box-shadow:0 18px 40px rgba(26,61,52,0.12);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#1A3D34 0%,#115e59 55%,#0d9488 100%);padding:28px 28px 24px;color:#ffffff;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        @if (! empty($logoUrl))
                                            <img src="{{ $logoUrl }}" alt="{{ $appName }}" width="120" style="display:block;max-width:120px;height:auto;border-radius:8px;background:rgba(255,255,255,0.08);padding:6px;">
                                        @else
                                            <div style="display:inline-block;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);border-radius:10px;padding:8px 12px;font-size:13px;font-weight:700;letter-spacing:0.08em;color:#e8dcb8;">
                                                {{ strtoupper(substr((string) $appName, 0, 2)) }}
                                            </div>
                                        @endif
                                        <p style="margin:14px 0 0;font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:#ccfbf1;font-weight:700;">{{ $appName }}</p>
                                        <h1 style="margin:8px 0 0;font-size:28px;line-height:1.2;font-weight:700;">{{ __('bookings.invoice.heading') }}</h1>
                                        <p style="margin:8px 0 0;font-size:13px;line-height:1.5;color:#ccfbf1;">{{ $subtitle }}</p>
                                    </td>
                                    <td style="vertical-align:top;text-align:right;width:150px;">
                                        <div style="display:inline-block;background:rgba(16,185,129,0.18);border:1px solid rgba(110,231,183,0.35);border-radius:999px;padding:6px 12px;font-size:11px;font-weight:700;color:#d1fae5;">
                                            {{ __('bookings.invoice.paid_badge') }}
                                        </div>
                                        <p style="margin:14px 0 0;font-size:11px;color:#99f6e4;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;">{{ __('bookings.invoice.date') }}</p>
                                        <p style="margin:4px 0 0;font-size:18px;font-weight:700;color:#ffffff;">
                                            {{ $booking->paid_at?->timezone(config('app.timezone'))->format('d/m/Y') ?? '—' }}
                                        </p>
                                        <p style="margin:2px 0 0;font-size:12px;color:#99f6e4;">
                                            {{ $booking->paid_at?->timezone(config('app.timezone'))->format('H:i') ?? '' }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 28px;background:#f8faf9;border-bottom:1px solid #e2e8f0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="50%" style="padding-right:8px;vertical-align:top;">
                                        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:12px 14px;">
                                            <p style="margin:0;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:#64748b;font-weight:700;">{{ __('bookings.invoice.booking_code') }}</p>
                                            <p style="margin:6px 0 0;font-family:Consolas,Monaco,monospace;font-size:15px;font-weight:700;color:#1A3D34;">{{ $docRef }}</p>
                                        </div>
                                    </td>
                                    <td width="50%" style="padding-left:8px;vertical-align:top;">
                                        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:12px 14px;">
                                            <p style="margin:0;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:#64748b;font-weight:700;">{{ __('bookings.invoice.total') }}</p>
                                            <p style="margin:6px 0 0;font-size:18px;font-weight:700;color:#0f766e;">Rp {{ $fmt($gross) }}</p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 28px 8px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="50%" style="padding-right:12px;vertical-align:top;">
                                        <p style="margin:0;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:#0f766e;font-weight:700;">{{ __('bookings.invoice.billed_to') }}</p>
                                        <p style="margin:8px 0 0;font-size:15px;font-weight:700;color:#0f172a;">{{ $booking->customer?->name }}</p>
                                        <p style="margin:4px 0 0;font-size:12px;color:#64748b;">{{ $booking->customer?->email }}</p>
                                    </td>
                                    <td width="50%" style="padding-left:12px;vertical-align:top;">
                                        <p style="margin:0;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:#0f766e;font-weight:700;">{{ __('bookings.invoice.provider') }}</p>
                                        <p style="margin:8px 0 0;font-size:15px;font-weight:700;color:#0f172a;">{{ $booking->muthowifProfile?->user?->name }}</p>
                                        <p style="margin:4px 0 0;font-size:12px;color:#64748b;">{{ __('bookings.invoice.muthowif') }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 28px;">
                            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:16px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td width="50%" style="vertical-align:top;padding-right:10px;">
                                            <p style="margin:0;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:#64748b;font-weight:700;">
                                                {{ $isSupport ? __('bookings.invoice.package') : __('bookings.invoice.service') }}
                                            </p>
                                            <p style="margin:8px 0 0;font-size:14px;font-weight:700;color:#0f172a;">
                                                @if ($isSupport)
                                                    {{ $packageLabel ?? '—' }}
                                                @else
                                                    {{ $booking->service_type?->label() ?? '—' }}
                                                @endif
                                            </p>
                                        </td>
                                        <td width="50%" style="vertical-align:top;padding-left:10px;">
                                            <p style="margin:0;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:#64748b;font-weight:700;">{{ __('bookings.invoice.schedule') }}</p>
                                            <p style="margin:8px 0 0;font-size:14px;font-weight:700;color:#0f172a;">{{ $scheduleLabel }}</p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:8px 28px 24px;">
                            <p style="margin:0 0 10px;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:#64748b;font-weight:700;">{{ __('bookings.invoice.amount_summary') }}</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
                                <tr>
                                    <td style="padding:12px 16px;background:#ffffff;border-bottom:1px solid #f1f5f9;font-size:13px;color:#475569;">{{ __('bookings.invoice.subtotal') }}</td>
                                    <td style="padding:12px 16px;background:#ffffff;border-bottom:1px solid #f1f5f9;font-size:13px;font-weight:700;text-align:right;color:#0f172a;">Rp {{ $fmt($base) }}</td>
                                </tr>
                                @if ($customerPlatformFee > 0)
                                    <tr>
                                        <td style="padding:12px 16px;background:#ffffff;border-bottom:1px solid #f1f5f9;font-size:13px;color:#475569;">{{ __('bookings.invoice.platform_fee_pct') }}</td>
                                        <td style="padding:12px 16px;background:#ffffff;border-bottom:1px solid #f1f5f9;font-size:13px;font-weight:700;text-align:right;color:#0f172a;">Rp {{ $fmt($customerPlatformFee) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td style="padding:14px 16px;background:#1A3D34;font-size:14px;font-weight:700;color:#ffffff;">{{ __('bookings.invoice.total') }}</td>
                                    <td style="padding:14px 16px;background:#1A3D34;font-size:18px;font-weight:700;text-align:right;color:#e8dcb8;">Rp {{ $fmt($gross) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 28px 28px;border-top:1px solid #e2e8f0;text-align:center;background:#ffffff;">
                            <div style="height:1px;width:72px;margin:0 auto 14px;background:linear-gradient(90deg,transparent,#C5A059,transparent);"></div>
                            <p style="margin:0;font-size:13px;font-weight:700;color:#1A3D34;">{{ __('bookings.invoice.thank_you', ['app' => $appName]) }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
