@extends('layouts.workspace')
@section('title', __('workspace.manager_action_log'))
@section('content')
<div class="d-flex align-items-end justify-content-between gap-3 mb-4"><div><div class="eyebrow">{{ __('workspace.management') }}</div><h1 class="page-title">{{ __('workspace.manager_action_log') }}</h1><p class="page-subtitle">{{ __('workspace.manager_action_log_intro') }}</p></div></div>

<form class="surface-card audit-filter mb-3" method="GET">
    @if($selectedRequest)<input type="hidden" name="service_request_id" value="{{ $selectedRequest->id }}">@endif
    <select class="form-select form-select-sm" name="manager_id" onchange="this.form.submit()"><option value="">{{ __('workspace.all_managers') }}</option>@foreach($managers as $manager)<option value="{{ $manager->id }}" @selected((int)request('manager_id')===$manager->id)>{{ $manager->name }}</option>@endforeach</select>
    @if($selectedRequest)<span class="audit-request-filter"><i class="bi bi-funnel"></i>{{ __('workspace.log_for_request',['request'=>$selectedRequest->title]) }}</span><a class="btn btn-light btn-sm" href="{{ route('workspace.manager-actions.index') }}">{{ __('workspace.clear_request_filter') }}</a>@endif
</form>

<div class="surface-card overflow-hidden"><div class="table-responsive"><table class="table"><thead><tr><th>{{ __('workspace.action_time') }}</th><th>{{ __('workspace.manager') }}</th><th>{{ __('workspace.action') }}</th><th>{{ __('workspace.request') }}</th><th>{{ __('workspace.action_details') }}</th></tr></thead><tbody>
@forelse($logs as $log)
<tr><td class="small text-nowrap">{{ $log->created_at->setTimezone($currentCompany->timezone)->format('d.m.Y H:i:s') }}</td><td><strong class="small">{{ $log->manager?->name ?? '—' }}</strong><div class="text-secondary" style="font-size:10px">{{ $log->ip_address }}</div></td><td><span class="badge-soft-secondary">{{ __('workspace.action_log_actions.'.str_replace('.','_',$log->action)) }}</span></td><td>@if($log->serviceRequest)<a class="small fw-semibold" href="{{ route('workspace.requests.show',$log->serviceRequest) }}">{{ $log->serviceRequest->title }}</a><div class="text-secondary" style="font-size:10px">#{{ str($log->serviceRequest->public_id)->substr(0,8)->upper() }}</div>@else<span class="text-secondary">—</span>@endif</td><td class="small">@forelse($log->metadata ?? [] as $key=>$value)<span class="audit-meta"><b>{{ __('workspace.action_log_fields.'.$key) }}:</b> {{ is_array($value) ? implode(', ', $value) : (is_bool($value) ? ($value ? __('workspace.yes') : __('workspace.no')) : $value) }}</span>@empty<span class="text-secondary">—</span>@endforelse</td></tr>
@empty<tr><td colspan="5"><div class="empty-builder"><span class="empty-builder-icon"><i class="bi bi-journal-text"></i></span><h3>{{ __('workspace.manager_action_log_empty') }}</h3></div></td></tr>@endforelse
</tbody></table></div></div><div class="mt-3">{{ $logs->links() }}</div>
@endsection
