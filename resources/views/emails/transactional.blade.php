@php
    /** @var list<string|array{url: string}> $blocks */
    $appName = $appName ?? config('app.name', 'BaytGo');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $appName }}</title>
</head>
<body style="margin:0;padding:0;background:#eef3f1;font-family:'Segoe UI',Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef3f1;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #d7e3df;box-shadow:0 18px 40px rgba(26,61,52,0.12);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#1A3D34 0%,#115e59 60%,#0d9488 100%);padding:24px 28px;color:#ffffff;">
                            @if (! empty($logoUrl))
                                <img src="{{ $logoUrl }}" alt="{{ $appName }}" width="110" style="display:block;max-width:110px;height:auto;border-radius:8px;background:rgba(255,255,255,0.08);padding:6px;">
                            @else
                                <div style="display:inline-block;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);border-radius:10px;padding:8px 12px;font-size:13px;font-weight:700;letter-spacing:0.08em;color:#e8dcb8;">
                                    {{ strtoupper(substr((string) $appName, 0, 2)) }}
                                </div>
                            @endif
                            <p style="margin:14px 0 0;font-size:11px;letter-spacing:0.16em;text-transform:uppercase;color:#ccfbf1;font-weight:700;">{{ $appName }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            @foreach ($blocks as $index => $block)
                                @if (is_array($block) && isset($block['url']))
                                    <p style="margin:{{ $index === 0 ? '0' : '14px' }} 0 0;">
                                        <a href="{{ $block['url'] }}" style="display:inline-block;background:#1A3D34;color:#ffffff;text-decoration:none;font-size:13px;font-weight:700;padding:11px 18px;border-radius:999px;">
                                            {{ __('bookings.invoice.open_link') }}
                                        </a>
                                    </p>
                                    <p style="margin:8px 0 0;font-size:11px;color:#94a3b8;word-break:break-all;">{{ $block['url'] }}</p>
                                @else
                                    <p style="margin:{{ $index === 0 ? '0' : '12px' }} 0 0;font-size:{{ $index === 0 ? '18px' : '14px' }};line-height:1.55;font-weight:{{ $index === 0 ? '700' : '400' }};color:{{ $index === 0 ? '#1A3D34' : '#334155' }};">
                                        {{ $block }}
                                    </p>
                                @endif
                            @endforeach

                            @if (filled($footnote ?? null))
                                <div style="margin-top:22px;padding:12px 14px;border-radius:12px;background:#f0fdfa;border:1px solid #99f6e4;">
                                    <p style="margin:0;font-size:12px;line-height:1.5;color:#0f766e;font-weight:600;">{{ $footnote }}</p>
                                </div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px 24px;border-top:1px solid #e2e8f0;text-align:center;">
                            <div style="height:1px;width:64px;margin:0 auto 12px;background:linear-gradient(90deg,transparent,#C5A059,transparent);"></div>
                            <p style="margin:0;font-size:11px;color:#94a3b8;">{{ __('bookings.invoice.electronic_doc') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
