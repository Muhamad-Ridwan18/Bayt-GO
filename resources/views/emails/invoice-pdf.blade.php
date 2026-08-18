@php
    use App\Support\IndonesianNumber;
    use App\Support\PlatformFee;
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
    $appName = config('app.name', 'BaytGo');
    $paidAt = $booking->paid_at?->timezone(config('app.timezone'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('bookings.invoice.title', ['app' => $appName]) }}</title>
    <style>
        @page { margin: 28px 32px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #0f172a;
            margin: 0;
            line-height: 1.45;
        }
        .header {
            background: #1A3D34;
            color: #ffffff;
            padding: 22px 24px;
            border-radius: 12px;
        }
        .brand {
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #ccfbf1;
            margin: 0 0 6px;
        }
        .title {
            font-size: 26px;
            font-weight: bold;
            margin: 0;
            color: #ffffff;
        }
        .subtitle {
            margin: 8px 0 0;
            color: #99f6e4;
            font-size: 11px;
        }
        .badge {
            display: inline-block;
            margin-top: 12px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #065f46;
            color: #d1fae5;
            font-size: 10px;
            font-weight: bold;
        }
        .meta {
            width: 100%;
            margin-top: 16px;
            border-collapse: collapse;
        }
        .meta td {
            vertical-align: top;
            width: 50%;
        }
        .card {
            background: #f8faf9;
            border: 1px solid #d7e3df;
            border-radius: 10px;
            padding: 12px 14px;
            margin-top: 16px;
        }
        .label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            font-weight: bold;
            margin: 0 0 4px;
        }
        .value {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
        }
        .muted {
            color: #64748b;
            font-size: 11px;
            margin: 3px 0 0;
        }
        .section {
            margin-top: 18px;
        }
        .grid {
            width: 100%;
            border-collapse: collapse;
        }
        .grid td {
            width: 50%;
            vertical-align: top;
            padding-right: 8px;
        }
        .grid td + td {
            padding-right: 0;
            padding-left: 8px;
        }
        .box {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px;
            background: #ffffff;
        }
        .amounts {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }
        .amounts td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .amounts tr:last-child td {
            border-bottom: none;
            background: #1A3D34;
            color: #ffffff;
            font-weight: bold;
        }
        .amounts .gold {
            color: #e8dcb8;
            font-size: 15px;
        }
        .right { text-align: right; }
        .footer {
            margin-top: 24px;
            text-align: center;
            color: #94a3b8;
            font-size: 10px;
            border-top: 1px solid #e2e8f0;
            padding-top: 14px;
        }
        .logo {
            height: 36px;
            margin-bottom: 10px;
        }
        .total-side {
            text-align: right;
        }
        .total-side .big {
            font-size: 18px;
            font-weight: bold;
            color: #ffffff;
            margin: 4px 0 0;
        }
    </style>
</head>
<body>
    <div class="header">
        @if (! empty($logoDataUri))
            <img class="logo" src="{{ $logoDataUri }}" alt="{{ $appName }}">
        @endif
        <table class="meta" cellspacing="0" cellpadding="0">
            <tr>
                <td>
                    <p class="brand">{{ $appName }}</p>
                    <h1 class="title">{{ __('bookings.invoice.heading') }}</h1>
                    <p class="subtitle">{{ $subtitle }}</p>
                    <span class="badge">{{ __('bookings.invoice.paid_badge') }}</span>
                </td>
                <td class="total-side">
                    <p class="label" style="color:#99f6e4;">{{ __('bookings.invoice.date') }}</p>
                    <p class="big">{{ $paidAt?->format('d/m/Y') ?? '—' }}</p>
                    <p class="subtitle">{{ $paidAt?->format('H:i') ?? '' }}</p>
                    <p class="label" style="color:#e8dcb8;margin-top:12px;">{{ __('bookings.invoice.total') }}</p>
                    <p class="big">Rp {{ $fmt($gross) }}</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="card">
        <table class="grid" cellspacing="0" cellpadding="0">
            <tr>
                <td>
                    <p class="label">{{ __('bookings.invoice.booking_code') }}</p>
                    <p class="value">{{ $docRef }}</p>
                </td>
                <td>
                    <p class="label">{{ __('bookings.invoice.order_no') }}</p>
                    <p class="value" style="font-size:11px;">{{ $payment?->order_id ?: '—' }}</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <table class="grid" cellspacing="0" cellpadding="0">
            <tr>
                <td>
                    <div class="box">
                        <p class="label">{{ __('bookings.invoice.billed_to') }}</p>
                        <p class="value">{{ $booking->customer?->name }}</p>
                        <p class="muted">{{ $booking->customer?->email }}</p>
                    </div>
                </td>
                <td>
                    <div class="box">
                        <p class="label">{{ __('bookings.invoice.provider') }}</p>
                        <p class="value">{{ $booking->muthowifProfile?->user?->name }}</p>
                        <p class="muted">{{ __('bookings.invoice.muthowif') }}</p>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <table class="grid" cellspacing="0" cellpadding="0">
            <tr>
                <td>
                    <div class="box">
                        <p class="label">{{ $isSupport ? __('bookings.invoice.package') : __('bookings.invoice.service') }}</p>
                        <p class="value">
                            @if ($isSupport)
                                {{ $packageLabel ?? '—' }}
                            @else
                                {{ $booking->service_type?->label() ?? '—' }}
                            @endif
                        </p>
                    </div>
                </td>
                <td>
                    <div class="box">
                        <p class="label">{{ __('bookings.invoice.schedule') }}</p>
                        <p class="value">{{ $scheduleLabel }}</p>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <p class="label">{{ __('bookings.invoice.amount_summary') }}</p>
        <table class="amounts" cellspacing="0" cellpadding="0">
            <tr>
                <td>{{ __('bookings.invoice.subtotal') }}</td>
                <td class="right">Rp {{ $fmt($base) }}</td>
            </tr>
            @if ($customerPlatformFee > 0)
                <tr>
                    <td>{{ __('bookings.invoice.platform_fee_pct') }}</td>
                    <td class="right">Rp {{ $fmt($customerPlatformFee) }}</td>
                </tr>
            @endif
            <tr>
                <td>{{ __('bookings.invoice.total') }}</td>
                <td class="right gold">Rp {{ $fmt($gross) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p style="margin:0 0 6px;color:#1A3D34;font-weight:bold;font-size:12px;">{{ __('bookings.invoice.thank_you', ['app' => $appName]) }}</p>
    </div>
</body>
</html>
