<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('bookings.invoice.title', ['app' => config('app.name')]) }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #0f172a; margin: 24px; font-size: 14px; }
        h1 { margin: 0 0 4px; font-size: 22px; }
        .muted { color: #64748b; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .right { text-align: right; }
        .total td { font-weight: bold; font-size: 16px; border-bottom: none; padding-top: 12px; }
    </style>
</head>
<body>
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
@endphp
    <p class="muted">{{ config('app.name') }}</p>
    <h1>{{ __('bookings.invoice.heading') }}</h1>
    <p class="muted">{{ __('bookings.invoice.date') }}:
        {{ $booking->paid_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
    </p>

    <table>
        <tr>
            <th>{{ __('bookings.invoice.booking_code') }}</th>
            <td class="right">{{ $booking->booking_code ?: '—' }}</td>
        </tr>
        @if ($payment)
            <tr>
                <th>{{ __('bookings.invoice.order_no') }}</th>
                <td class="right">{{ $payment->order_id }}</td>
            </tr>
        @endif
        <tr>
            <th>{{ __('bookings.invoice.pilgrim') }}</th>
            <td class="right">{{ $booking->customer?->name }}<br><span class="muted">{{ $booking->customer?->email }}</span></td>
        </tr>
        <tr>
            <th>{{ __('bookings.invoice.muthowif') }}</th>
            <td class="right">{{ $booking->muthowifProfile?->user?->name }}</td>
        </tr>
        <tr>
            <th>{{ __('bookings.invoice.service_period') }}</th>
            <td class="right">
                {{ Carbon::parse($booking->starts_on)->format('d/m/Y') }} – {{ Carbon::parse($booking->ends_on)->format('d/m/Y') }}
            </td>
        </tr>
        <tr>
            <th>{{ __('bookings.invoice.subtotal') }}</th>
            <td class="right">Rp {{ $fmt($base) }}</td>
        </tr>
        @if ($customerPlatformFee > 0)
            <tr>
                <th>{{ __('bookings.invoice.platform_fee_pct') }}</th>
                <td class="right">Rp {{ $fmt($customerPlatformFee) }}</td>
            </tr>
        @endif
        <tr class="total">
            <td>{{ __('bookings.invoice.total') }}</td>
            <td class="right">Rp {{ $fmt($gross) }}</td>
        </tr>
    </table>

    <p class="muted" style="margin-top: 24px;">{{ __('bookings.invoice.thank_you', ['app' => config('app.name')]) }}</p>
    <p class="muted">{{ __('bookings.invoice.electronic_doc') }}</p>
</body>
</html>
