@props([
    'profile',
    'listQueryString' => '',
])

@php
    use App\Enums\MuthowifServiceType;

    $profile->loadMissing(['user', 'services']);
    $group = $profile->services->firstWhere('type', MuthowifServiceType::Group);
    $private = $profile->services->firstWhere('type', MuthowifServiceType::PrivateJamaah);
@endphp

@include('layanan.partials.muthowif-card', [
    'profile' => $profile,
    'group' => $group,
    'private' => $private,
    'listQueryString' => $listQueryString,
    'as' => 'div',
])
