@if (session('status'))
    @php
        $statusKey = session('status');
        $statusMessage = match ($statusKey) {
            'profile-updated', 'password-updated' => __('profile.saved'),
            'public-profile-updated' => __('profile_public.saved'),
            'verification-link-sent' => __('profile.verification.sent'),
            default => $statusKey,
        };
    @endphp
    <x-page-container class="ui-flash-wrap">
        <x-ui.alert type="success">{{ $statusMessage }}</x-ui.alert>
    </x-page-container>
@endif

@if (session('error'))
    <x-page-container class="ui-flash-wrap">
        <x-ui.alert type="error">{{ session('error') }}</x-ui.alert>
    </x-page-container>
@endif
