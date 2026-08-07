@extends('layouts.workspace')
@section('title', __('workspace.background_library'))
@section('content')
<div class="d-flex align-items-end justify-content-between gap-3 mb-4"><div><div class="eyebrow">{{ __('workspace.guest_catalog') }}</div><h1 class="page-title">{{ __('workspace.background_library') }}</h1><p class="page-subtitle">{{ __('workspace.background_library_intro') }}</p></div>@if(auth()->user()->isOwner())<a class="btn btn-primary" href="#customBackground"><i class="bi bi-cloud-arrow-up me-2"></i>{{ __('workspace.upload_background') }}</a>@endif</div>

@unless(auth()->user()->isOwner())<div class="alert alert-light border d-flex gap-2 align-items-center mb-4"><i class="bi bi-eye"></i><span>{{ __('workspace.background_manager_readonly') }}</span></div>@endunless

<div class="background-pack-grid mb-4">
@foreach($systemSets->concat($customSets) as $set)
    @php($active = $company->background_set_id === $set->id)
    <article class="surface-card background-pack-card {{ $active ? 'is-active' : '' }}">
        <div class="background-pack-preview">
            @foreach($set->images->where('is_active', true)->take(4) as $image)<span style="background-image:url('{{ $image->url() }}');background-position:{{ $image->background_position }};background-size:{{ $image->background_size }}"></span>@endforeach
            @if($set->images->where('is_active', true)->isEmpty())<span class="background-pack-empty"><i class="bi bi-image"></i></span>@endif
            @if($active)<em><i class="bi bi-check-circle-fill"></i> {{ __('workspace.in_use') }}</em>@endif
        </div>
        <div class="background-pack-body"><div><small>{{ $set->is_system ? __('workspace.ready_pack') : __('workspace.hotel_pack') }}</small><h2>{{ $set->name }}</h2><p>{{ __('workspace.background_count', ['count' => $set->images->count()]) }}</p></div>
        @if(auth()->user()->isOwner())
            @if($active)<button class="btn btn-light btn-sm" disabled>{{ __('workspace.selected') }}</button>
            @elseif($set->images->isNotEmpty())<form method="POST" action="{{ route('workspace.backgrounds.activate', $set) }}">@csrf @method('PATCH')<button class="btn btn-outline-primary btn-sm">{{ __('workspace.choose_pack') }}</button></form>@endif
        @endif</div>
        @if(!$set->is_system && $set->images->isNotEmpty())<div class="custom-background-list">@foreach($set->images as $image)<div><span class="custom-background-thumb" style="background-image:url('{{ $image->url() }}')"></span><strong>{{ $image->name }}</strong>@if(auth()->user()->isOwner())<form method="POST" action="{{ route('workspace.backgrounds.destroy', $image) }}" onsubmit="return confirm('{{ __('workspace.delete_background_confirm') }}')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger" aria-label="{{ __('workspace.delete') }}"><i class="bi bi-trash"></i></button></form>@endif</div>@endforeach</div>@endif
    </article>
@endforeach
</div>

@if(auth()->user()->isOwner())
<section class="surface-card background-upload-card" id="customBackground"><div><span class="metric-icon"><i class="bi bi-images"></i></span><h2 class="section-title mt-3">{{ __('workspace.own_backgrounds') }}</h2><p>{{ __('workspace.own_backgrounds_intro') }}</p></div><form method="POST" action="{{ route('workspace.backgrounds.store') }}" enctype="multipart/form-data">@csrf<div><label class="form-label">{{ __('workspace.background_name') }}</label><input class="form-control" name="name" maxlength="80" placeholder="{{ __('workspace.background_name_hint') }}" required></div><div><label class="form-label">{{ __('workspace.image_file') }}</label><input class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/webp" required><div class="form-hint mt-1">JPG, PNG или WebP · до 10 MB</div></div><button class="btn btn-primary"><i class="bi bi-cloud-arrow-up me-2"></i>{{ __('workspace.upload') }}</button></form></section>
@endif
@endsection
