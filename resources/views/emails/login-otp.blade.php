<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('guest.login_otp.email_subject', ['app' => config('app.name', 'BaytGo')]) }}</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #334155;">
    <p>{{ __('guest.login_otp.email_greeting', ['name' => $user->name]) }}</p>
    <p>{{ __('guest.login_otp.email_body', ['app' => config('app.name', 'BaytGo')]) }}</p>
    <p style="font-size: 28px; font-weight: bold; letter-spacing: 0.25em; color: #0f766e;">{{ $otp }}</p>
    <p style="font-size: 13px; color: #64748b;">{{ __('guest.login_otp.email_footer') }}</p>
</body>
</html>
