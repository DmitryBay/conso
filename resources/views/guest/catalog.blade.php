@extends('layouts.guest')
@section('title', __('guest.services'))
@section('content')
@php
    $firstName = app('guestStay')->guest_name ? str(app('guestStay')->guest_name)->before(' ') : null;
    $greeting = now()->hour < 12 ? __('guest.morning') : (now()->hour < 18 ? __('guest.afternoon') : __('guest.evening'));
    $money = app(\App\Support\Money::class);
@endphp
<section class="guest-hero"><div><span class="eyebrow">{{ $greeting }}{{ $firstName ? ', '.$firstName : '' }}</span><h1>{{ __('guest.how_help') }}</h1><p>{{ __('guest.catalog_intro') }}</p></div><span class="guest-hero-icon"><i class="bi bi-bell"></i></span></section>

@php
    $guideIcons = ['one_day' => 'bi-compass', 'two_days' => 'bi-map', 'three_days' => 'bi-signpost-2', 'five_days' => 'bi-calendar2-week'];
    $guideDurations = ['one_day' => 1, 'two_days' => 2, 'three_days' => 3, 'five_days' => 5];
    $miniGuides = __('guest.mini_guides');
    $stayNights = app('guestStay')->stay->nights;
    $recommendedDuration = collect($guideDurations)->filter(fn ($days) => $days <= $stayNights)->max() ?: 1;
@endphp
<section class="guest-section guest-guides"><div class="guest-section-title"><div><span class="eyebrow">{{ __('guest.guides_eyebrow') }}</span><h2>{{ __('guest.guides_title') }}</h2></div><span>{{ count($miniGuides) }}</span></div>
<div class="guest-guides-scroll">
@foreach($miniGuides as $guideKey => $guide)
    <button class="guest-guide-card {{ $guideDurations[$guideKey] === $recommendedDuration ? 'recommended' : '' }}" type="button" data-bs-toggle="modal" data-bs-target="#guestGuide-{{ $guideKey }}">
        <span class="guest-guide-icon"><i class="bi {{ $guideIcons[$guideKey] ?? 'bi-book' }}"></i></span>
        <span><strong>{{ $guide['title'] }}</strong><small>{{ $guide['summary'] }}</small>@if($guideDurations[$guideKey] === $recommendedDuration)<em>{{ __('guest.guide_for_stay') }}</em>@endif</span>
        <i class="bi bi-arrow-up-right"></i>
    </button>
@endforeach
</div></section>

<section class="guest-section service-catalog"><div class="guest-section-title"><div><span class="eyebrow">{{ __('guest.catalog') }}</span><h2>{{ __('guest.all_services') }}</h2></div><span>{{ $categories->count() }}</span></div>
<div class="guest-main-menu">
@forelse($categories as $category)
    @php
        $categoryServices = $category->children->filter(fn($node) => in_array($node->type, [\App\Enums\ServiceNodeType::Service, \App\Enums\ServiceNodeType::Guide], true))->merge($category->children->filter(fn($node) => $node->type === \App\Enums\ServiceNodeType::Category)->flatMap(fn($node) => $node->children))->filter(fn($node) => in_array($node->type, [\App\Enums\ServiceNodeType::Service, \App\Enums\ServiceNodeType::Guide], true));
        $menuBackground = $category->resolvedBackground($company);
        $legacyKey = match($loop->index) { 0=>'food',1=>'room',2=>'transport',3=>'wellness',4=>'transport',default=>'room' };
    @endphp
    <button class="guest-menu-tile" type="button" data-bs-toggle="modal" data-bs-target="#guestCategory-{{ $category->id }}" style="--service-bg:url('{{ $menuBackground?->url() ?? asset('images/service-backgrounds/'.$legacyKey.'.jpg') }}');--service-position:{{ $menuBackground?->background_position ?? 'center' }};--service-size:{{ $menuBackground?->background_size ?? 'cover' }}"><span><i class="bi {{ $category->icon ?: 'bi-grid' }}"></i></span><strong>{{ $category->localizedName() }}</strong><small>{{ __('guest.services_count',['count'=>$categoryServices->count()]) }}</small><i class="bi bi-arrow-up-right"></i></button>
@empty
    <div class="guest-empty"><i class="bi bi-stars"></i><h2>{{ __('guest.catalog_empty') }}</h2><p>{{ __('guest.catalog_empty_hint') }}</p></div>
@endforelse
</div></section>

@foreach($categories as $category)
    @php
        $directServices = $category->children->filter(fn($node) => in_array($node->type, [\App\Enums\ServiceNodeType::Service, \App\Enums\ServiceNodeType::Guide], true));
        $subcategories = $category->children->filter(fn($node) => $node->type === \App\Enums\ServiceNodeType::Category);
        $nestedServices = $subcategories->flatMap(fn($node) => $node->children);
        $services = $directServices->merge($nestedServices)->filter(fn($node) => in_array($node->type, [\App\Enums\ServiceNodeType::Service, \App\Enums\ServiceNodeType::Guide], true));
    @endphp
    <div class="modal fade guest-category-modal" id="guestCategory-{{ $category->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><div class="modal-header guest-panel-heading"><span class="category-icon"><i class="bi {{ $category->icon ?: 'bi-grid' }}"></i></span><div><small>{{ __('guest.catalog') }}</small><h2 class="modal-title">{{ $category->localizedName() }}</h2></div><strong>{{ $services->count() }}</strong><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="{{ __('guest.close') }}"></button></div><div class="modal-body category-services">
    @foreach($directServices as $service)
        @include('guest._service-row', ['service'=>$service])
    @endforeach
    @foreach($subcategories as $subcategory)
        @php
            $subcategoryServices = $subcategory->children->filter(
                fn ($node) => in_array($node->type, [\App\Enums\ServiceNodeType::Service, \App\Enums\ServiceNodeType::Guide], true)
            );
        @endphp
        <details class="guest-subcategory" {{ $loop->first ? 'open' : '' }}><summary><span><i class="bi {{ $subcategory->icon ?: 'bi-folder2-open' }}"></i><strong>{{ $subcategory->localizedName() }}</strong></span><span><small>{{ $subcategoryServices->count() }}</small><i class="bi bi-chevron-down"></i></span></summary><div class="guest-subcategory-services">
        @forelse($subcategoryServices as $service)
            @include('guest._service-row', ['service'=>$service])
        @empty<div class="guest-empty compact">{{ __('guest.category_empty') }}</div>@endforelse
        </div></details>
    @endforeach
    @if($directServices->isEmpty() && $subcategories->isEmpty())<div class="guest-empty compact">{{ __('guest.category_empty') }}</div>@endif
    </div></div></div></div>
@endforeach

@php
    $areaGuides = $categories->flatMap(function ($category) {
        $direct = $category->children->filter(fn ($node) => $node->type === \App\Enums\ServiceNodeType::Guide);
        $nested = $category->children->filter(fn ($node) => $node->type === \App\Enums\ServiceNodeType::Category)
            ->flatMap(fn ($node) => $node->children)
            ->filter(fn ($node) => $node->type === \App\Enums\ServiceNodeType::Guide);
        return $direct->merge($nested);
    })->unique('id');
@endphp
@foreach($areaGuides as $guide)
    @php($guideBackground = $guide->resolvedBackground($company))
    <div class="modal fade guest-guide-modal guest-area-guide-modal" id="guestAreaGuide-{{ $guide->id }}" tabindex="-1" aria-labelledby="guestAreaGuideTitle-{{ $guide->id }}" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <div class="guest-area-guide-cover" style="--service-bg:url('{{ $guideBackground?->url() ?? asset('images/service-backgrounds/transport.jpg') }}');--service-position:{{ $guideBackground?->background_position ?? 'center' }};--service-size:{{ $guideBackground?->background_size ?? 'cover' }}"><button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="{{ __('guest.close') }}"></button><span><i class="bi {{ $guide->icon ?: 'bi-compass' }}"></i></span></div>
        <div class="modal-body"><small class="eyebrow">{{ __('workspace.guide_type') }}</small><h2 class="modal-title mt-1" id="guestAreaGuideTitle-{{ $guide->id }}">{{ $guide->localizedName() }}</h2><div class="row g-4 mt-1"><div class="col-lg-8"><div class="guest-area-guide-copy">@foreach(preg_split('/\R{2,}/u', $guide->localizedDescription() ?: '') as $paragraph)@php($hasLabel=str_contains($paragraph, ':'))<p>@if($hasLabel)<strong>{{ str($paragraph)->before(':') }}:</strong> {{ str($paragraph)->after(':')->trim() }}@else{{ $paragraph }}@endif</p>@endforeach</div></div><div class="col-lg-4">@if($guide->external_links)<aside class="guest-guide-maps"><div class="guest-guide-maps-title"><span><i class="bi bi-geo-alt-fill"></i></span><div><strong>{{ __('guest.open_in_maps') }}</strong><small>{{ __('guest.maps_hint') }}</small></div></div><div class="guest-guide-map-links">@foreach($guide->external_links as $link)<a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer"><span><i class="bi bi-geo-alt"></i>{{ $link['label'] }}</span><i class="bi bi-box-arrow-up-right"></i></a>@endforeach</div></aside>@endif</div></div></div>
        <div class="modal-footer"><button class="guest-guide-close" type="button" data-bs-dismiss="modal">{{ __('guest.got_it') }}</button></div>
    </div></div></div>
@endforeach

@foreach($miniGuides as $guideKey => $guide)
<div class="modal fade guest-guide-modal" id="guestGuide-{{ $guideKey }}" tabindex="-1" aria-labelledby="guestGuideTitle-{{ $guideKey }}" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><span class="guest-guide-modal-icon"><i class="bi {{ $guideIcons[$guideKey] ?? 'bi-book' }}"></i></span><div><small>{{ __('guest.trip_plan') }}</small><h2 class="modal-title" id="guestGuideTitle-{{ $guideKey }}">{{ $guide['title'] }}</h2></div><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="{{ __('guest.close') }}"></button></div><div class="modal-body"><p>{{ $guide['intro'] }}</p><ul>@foreach($guide['tips'] as $tip)<li><i class="bi bi-geo-alt"></i><span>{{ $tip }}</span></li>@endforeach</ul></div><div class="modal-footer"><button class="guest-guide-close" type="button" data-bs-dismiss="modal">{{ __('guest.got_it') }}</button></div></div></div></div>
@endforeach
@endsection
