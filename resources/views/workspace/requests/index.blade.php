@extends('layouts.workspace')
@section('title',__('workspace.kanban'))
@section('content')
<div class="d-flex align-items-end justify-content-between gap-3 mb-3"><div><div class="eyebrow">{{ __('workspace.service_shift') }}</div><h1 class="page-title">{{ __('workspace.guest_requests') }}</h1><p class="page-subtitle">{{ __('workspace.kanban_intro') }}</p></div><div class="page-actions"><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRequestModal"><i class="bi bi-plus-lg me-sm-2"></i><span>{{ __('workspace.new_request') }}</span></button></div></div>
<div class="kanban-toolbar surface-card">
    <form class="kanban-filter-form" method="GET">
        <div class="kanban-filter-fields">
            <select class="form-select form-select-sm priority-filter" name="priority" onchange="this.form.submit()"><option value="">{{ __('workspace.all_priorities') }}</option>@foreach(\App\Enums\RequestPriority::cases() as $priority)<option value="{{ $priority->value }}" @selected(request('priority')===$priority->value)>{{ __('workspace.priority.'.$priority->value) }}</option>@endforeach</select>
            <select class="form-select form-select-sm client-filter" name="guest_stay_id" onchange="this.form.submit()"><option value="">{{ __('workspace.all_clients') }}</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected((int)request('guest_stay_id')===$client->id)>{{ $client->guest_name }} · {{ $client->room->displayName() }}</option>@endforeach</select>
            <input class="form-control form-control-sm client-name-filter" name="guest_name" value="{{ request('guest_name') }}" placeholder="{{ __('workspace.client_name_filter') }}">
        </div>
        <div class="kanban-filter-toggles">
            <label class="filter-check"><input type="checkbox" name="mine" value="1" @checked(request()->boolean('mine')) onchange="this.form.submit()"><span><i class="bi bi-person-check me-1"></i>{{ __('workspace.mine_only') }}</span></label>
            <label class="filter-check"><input type="checkbox" name="refund" value="1" @checked(request()->boolean('refund')) onchange="this.form.submit()"><span><i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('workspace.with_refund') }}</span></label>
            <label class="filter-check"><input type="checkbox" name="cancelled" value="1" @checked(request()->boolean('cancelled')) onchange="this.form.submit()"><span><i class="bi bi-x-circle me-1"></i>{{ __('workspace.cancelled_filter') }}</span></label>
            <label class="filter-check"><input type="checkbox" name="archive" value="1" @checked(request()->boolean('archive')) onchange="this.form.submit()"><span><i class="bi bi-archive me-1"></i>{{ __('workspace.archive') }}</span></label>
            <label class="filter-check"><input type="checkbox" name="all_stays" value="1" @checked(request()->boolean('all_stays')) onchange="this.form.submit()"><span>{{ __('workspace.all_stays_filter') }}</span></label>
            @if(request()->hasAny(['mine','priority','guest_stay_id','guest_name','refund','cancelled','archive','all_stays']))<a class="btn btn-sm btn-light" href="{{ route('workspace.requests.index') }}">{{ __('workspace.reset') }}</a>@endif
        </div>
    </form>
    <div class="text-secondary d-none d-md-block" style="font-size:11px"><i class="bi bi-arrows-move me-1"></i>{{ __('workspace.drag_hint') }}</div>
</div>
<div class="kanban-board">
@foreach(request()->boolean('cancelled') ? [\App\Enums\RequestStatus::Cancelled] : \App\Enums\RequestStatus::kanban() as $status)
@php($column=$requests[$status->value] ?? collect())
<section class="kanban-column" data-kanban-column data-status="{{ $status->value }}"><header class="kanban-column-header"><div class="d-flex align-items-center gap-2"><span class="column-dot bg-{{ $status->color() }}"></span><h2>{{ __('workspace.status.'.$status->value) }}</h2><span class="column-count">{{ $column->count() }}</span></div>@if($status===\App\Enums\RequestStatus::New)<button class="btn btn-link text-secondary p-0" data-bs-toggle="modal" data-bs-target="#newRequestModal"><i class="bi bi-plus-lg"></i></button>@endif</header><div class="kanban-cards">@forelse($column as $item)@include('workspace.requests._card',['item'=>$item])@empty<div class="kanban-empty"><i class="bi bi-inbox"></i><span>{{ __('workspace.no_requests') }}</span></div>@endforelse</div></section>
@endforeach
</div>
@endsection
@push('modals')
<div class="modal fade request-detail-modal" id="requestDetailModal" tabindex="-1" aria-hidden="true" data-loading-label="{{ __('workspace.loading_request') }}" data-error-label="{{ __('workspace.request_load_error') }}" data-retry-label="{{ __('workspace.retry') }}">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <button type="button" class="btn-close request-detail-close" data-bs-dismiss="modal" aria-label="{{ __('workspace.cancel') }}"></button>
            <div class="modal-body request-detail-modal-body" data-request-modal-body></div>
        </div>
    </div>
</div>
<div class="modal fade" id="newRequestModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><form class="modal-content" method="POST" action="{{ route('workspace.requests.store') }}">@csrf<div class="modal-header"><div><h2 class="modal-title fs-5">{{ __('workspace.new_request') }}</h2><div class="text-secondary small">{{ __('workspace.manual_request_hint') }}</div></div><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><div class="row g-3">
<div class="col-md-4"><label class="form-label">{{ __('workspace.room') }} *</label><select class="form-select" name="room_number" required><option value="">{{ __('workspace.choose_room') }}</option>@foreach($rooms as $room)<option value="{{ $room->number }}" @selected(old('room_number')===$room->number)>{{ $room->displayLabel() }}</option>@endforeach</select></div><div class="col-md-8"><label class="form-label">{{ __('workspace.guest_name') }}</label><input class="form-control" name="guest_name" placeholder="{{ __('workspace.name_or_surname') }}"></div>
<div class="col-md-7"><label class="form-label">{{ __('workspace.catalog_service') }}</label><select class="form-select" name="service_node_id"><option value="">{{ __('workspace.other_request') }}</option>@foreach($services as $service)<option value="{{ $service->id }}">{{ $service->localizedName() }}</option>@endforeach</select></div><div class="col-md-5"><label class="form-label">{{ __('workspace.priority_label') }} *</label><select class="form-select" name="priority">@foreach(\App\Enums\RequestPriority::cases() as $priority)<option value="{{ $priority->value }}" @selected($priority===\App\Enums\RequestPriority::Normal)>{{ __('workspace.priority.'.$priority->value) }}</option>@endforeach</select></div>
<div class="col-12"><label class="form-label">{{ __('workspace.short_title') }} *</label><input class="form-control" name="title" required placeholder="{{ __('workspace.title_example') }}"></div><div class="col-12"><label class="form-label">{{ __('workspace.comment') }}</label><textarea class="form-control" name="description" rows="3" placeholder="{{ __('workspace.comment_hint') }}"></textarea></div>
<div class="col-md-6"><label class="form-label">{{ __('workspace.assign') }}</label><select class="form-select" name="assigned_to"><option value="">{{ __('workspace.leave_unassigned') }}</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">{{ __('workspace.due_at') }}</label><input class="form-control" type="datetime-local" name="due_at"></div><div class="col-md-3"><label class="form-label">{{ __('workspace.cost',['currency'=>$currentCompany->currency]) }}</label><input class="form-control" type="number" min="0" name="price"></div>
</div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('workspace.cancel') }}</button><button class="btn btn-primary">{{ __('workspace.create_request') }}</button></div></form></div></div>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    let dragged = null;
    document.querySelectorAll('[data-request-card]').forEach(card => {
        card.addEventListener('dragstart', () => { dragged = card; card.classList.add('is-dragging'); });
        card.addEventListener('dragend', () => card.classList.remove('is-dragging'));
    });
    document.querySelectorAll('[data-kanban-column]').forEach(column => {
        column.addEventListener('dragover', event => { event.preventDefault(); column.classList.add('is-over'); });
        column.addEventListener('dragleave', () => column.classList.remove('is-over'));
        column.addEventListener('drop', async event => {
            event.preventDefault();
            column.classList.remove('is-over');
            if (!dragged) return;
            const response = await fetch(dragged.dataset.statusUrl, {
                method: 'PATCH',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify({status: column.dataset.status}),
            });
            if (response.ok) { column.querySelector('.kanban-cards').prepend(dragged); window.location.reload(); }
        });
    });

    const modalElement = document.getElementById('requestDetailModal');
    const modalBody = modalElement.querySelector('[data-request-modal-body]');
    const detailModal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
    let currentUrl = null;
    let boardChanged = false;

    const loader = () => `<div class="request-detail-state"><span class="request-detail-spinner" aria-hidden="true"></span><span>${modalElement.dataset.loadingLabel}</span></div>`;
    const errorState = () => `<div class="request-detail-state text-danger"><i class="bi bi-exclamation-circle fs-3"></i><span>${modalElement.dataset.errorLabel}</span><button type="button" class="btn btn-light btn-sm" data-request-retry>${modalElement.dataset.retryLabel}</button></div>`;

    const loadRequest = async url => {
        currentUrl = url;
        modalBody.innerHTML = loader();
        modalElement.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, {
                headers: {'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest'},
                credentials: 'same-origin',
                cache: 'no-store',
            });
            if (!response.ok) throw new Error('Request loading failed');
            const documentFragment = new DOMParser().parseFromString(await response.text(), 'text/html');
            const detail = documentFragment.querySelector('[data-request-detail]');
            if (!detail) throw new Error('Request detail not found');
            modalBody.replaceChildren(detail);
        } catch (error) {
            modalBody.innerHTML = errorState();
        } finally {
            modalElement.removeAttribute('aria-busy');
        }
    };

    document.querySelectorAll('[data-request-modal-link]').forEach(link => link.addEventListener('click', event => {
        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        detailModal.show();
        loadRequest(link.href);
    }));

    modalBody.addEventListener('click', event => {
        if (event.target.closest('[data-request-retry]') && currentUrl) loadRequest(currentUrl);
    });

    modalBody.addEventListener('change', event => {
        const priceItem = event.target.closest('[data-price-item-select]');
        if (priceItem) {
            const form = priceItem.closest('[data-price-adjustment-form]');
            const price = form?.querySelector('[data-adjusted-price]');
            if (price) price.value = priceItem.selectedOptions[0]?.dataset.price ?? '';
            return;
        }
        const type = event.target.closest('[data-refund-type]');
        if (!type) return;
        const form = type.closest('[data-refund-form]');
        const amount = form.querySelector('[data-refund-amount]');
        if (type.value === 'full') amount.value = form.dataset.serviceAmount;
        if (type.value === 'none') amount.value = '';
        amount.readOnly = type.value === 'full';
        amount.disabled = type.value === 'none';
    });

    modalBody.addEventListener('submit', async event => {
        const form = event.target.closest('form');
        if (!form) return;
        event.preventDefault();
        const submitButton = event.submitter;
        submitButton?.setAttribute('disabled', 'disabled');
        modalElement.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(form.action, {
                method: form.method || 'POST',
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                credentials: 'same-origin',
                body: new FormData(form),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const message = Object.values(payload.errors || {}).flat()[0] || payload.message || modalElement.dataset.errorLabel;
                throw new Error(message);
            }
            boardChanged = true;
            await loadRequest(currentUrl);
        } catch (error) {
            let alert = modalBody.querySelector('[data-request-form-error]');
            if (!alert) {
                alert = document.createElement('div');
                alert.className = 'alert alert-danger mb-3';
                alert.dataset.requestFormError = '';
                modalBody.prepend(alert);
            }
            alert.textContent = error.message;
        } finally {
            submitButton?.removeAttribute('disabled');
            modalElement.removeAttribute('aria-busy');
        }
    });

    modalElement.addEventListener('hidden.bs.modal', () => {
        modalBody.replaceChildren();
        currentUrl = null;
        if (boardChanged) window.location.reload();
    });
});
</script>
@endpush
