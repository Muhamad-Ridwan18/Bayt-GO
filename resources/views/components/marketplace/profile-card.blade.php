@props([
    'profile',
    'listQueryString' => '',
])

@php
    $card = \App\ViewModels\Layanan\MarketplaceProfileCardData::fromProfile($profile, $listQueryString);
@endphp

@include('layanan.partials.muthowif-card', [
    'card' => $card,
    'as' => 'div',
])
