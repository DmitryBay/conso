@php
    $backgroundImage = $service->resolvedBackground($company);
    $legacyBackground = asset('images/service-backgrounds/'.($service->background_key ?: 'wellness').'.jpg');
@endphp
@if($service->isGuide())
<button class="guest-service-row guest-area-guide-row" type="button" data-bs-toggle="modal" data-bs-target="#guestAreaGuide-{{ $service->id }}">
    <span class="service-row-image" style="--service-bg:url('{{ $backgroundImage?->url() ?? $legacyBackground }}');--service-position:{{ $backgroundImage?->background_position ?? 'center' }};--service-size:{{ $backgroundImage?->background_size ?? 'cover' }}"><i class="bi {{ $service->icon ?: 'bi-compass' }}"></i></span>
    <div><h3>{{ $service->localizedName() }}</h3><p>{{ str($service->localizedDescription())->limit(115) }}</p><small><i class="bi bi-compass"></i> {{ __('workspace.guide_type') }}</small></div>
    <span class="service-row-action guide"><i class="bi bi-arrow-up-right"></i></span>
</button>
@else
<a class="guest-service-row" href="{{ route('guest.orders.create', [$company, $service]) }}">
    <span class="service-row-image" style="--service-bg:url('{{ $backgroundImage?->url() ?? $legacyBackground }}');--service-position:{{ $backgroundImage?->background_position ?? 'center' }};--service-size:{{ $backgroundImage?->background_size ?? 'cover' }}"><i class="bi {{ $service->icon ?: 'bi-stars' }}"></i></span>
    <div><h3>{{ $service->localizedName() }}</h3><p>{{ $service->localizedDescription() }}</p><small>@if($service->sla_minutes)<i class="bi bi-clock"></i> {{ __('guest.minutes', ['count'=>$service->sla_minutes]) }}@endif</small></div>
    <span class="service-row-action"><span>@if($service->price_minor){{ $money->format($service->price_minor, $company->currency) }}<small>{{ $money->approximateUsd($service->price_minor, $company->currency) }}</small>@else{{ __('guest.free') }}@endif</span><i class="bi bi-chevron-right"></i></span>
</a>
@endif
