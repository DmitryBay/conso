@extends('layouts.workspace')
@section('title', __('workspace.client_card'))
@section('content')
@php($money = app(\App\Support\Money::class))
<div class="client-card-heading mb-4">
    <div class="d-flex align-items-center gap-3">
        <a class="btn btn-light btn-sm" href="{{ route('workspace.stays.index') }}" aria-label="{{ __('workspace.back_to_stays') }}"><i class="bi bi-arrow-left"></i></a>
        <div><div class="eyebrow">{{ __('workspace.client_card') }} · #{{ str($guestStay->public_id)->substr(0, 8)->upper() }}</div><h1 class="page-title">{{ $guestStay->guest_name }}</h1><p class="page-subtitle">{{ $guestStay->room->displayLabel() }} · {{ $guestStay->check_in_at->setTimezone($currentCompany->timezone)->format('d.m.Y') }} — {{ $guestStay->check_out_at->setTimezone($currentCompany->timezone)->format('d.m.Y') }}</p></div>
    </div>
    <span class="badge-soft-{{ $guestStay->status->color() }}">{{ __('workspace.stay_status.'.$guestStay->status->value) }}</span>
</div>

<div class="client-card-layout">
    <div>
        <section class="surface-card p-4 mb-3">
            <div class="client-section-heading"><div><div class="eyebrow">{{ __('workspace.client_details') }}</div><h2 class="section-title mt-1">{{ __('workspace.contacts_and_notes') }}</h2></div><span class="metric-icon"><i class="bi bi-person-vcard"></i></span></div>
            <form method="POST" action="{{ route('workspace.stays.update', $guestStay) }}">@csrf @method('PATCH')
                <div class="row g-3 mt-1">
                    <div class="col-md-6"><label class="form-label">{{ __('workspace.guest_name') }}</label><input class="form-control" name="guest_name" value="{{ old('guest_name', $guestStay->guest_name) }}" required maxlength="160"></div>
                    <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="guest_email" value="{{ old('guest_email', $guestStay->guest_email) }}" maxlength="190" placeholder="guest@example.com"></div>
                    <div class="col-12"><label class="form-label">{{ __('workspace.emergency_phone') }}</label><div class="input-group"><span class="input-group-text"><i class="bi bi-telephone"></i></span><input class="form-control" type="tel" name="emergency_phone" value="{{ old('emergency_phone', $guestStay->emergency_phone) }}" maxlength="40" placeholder="+62 812 3456 7890"></div><div class="form-hint mt-1">{{ __('workspace.emergency_phone_hint') }}</div></div>
                    <div class="col-12"><label class="form-label">{{ __('workspace.internal_notes') }}</label><textarea class="form-control" name="internal_notes" rows="5" maxlength="5000" placeholder="{{ __('workspace.internal_notes_hint') }}">{{ old('internal_notes', $guestStay->internal_notes) }}</textarea><div class="form-hint mt-1"><i class="bi bi-lock me-1"></i>{{ __('workspace.internal_notes_private') }}</div></div>
                </div>
                <button class="btn btn-primary mt-3"><i class="bi bi-check2 me-2"></i>{{ __('workspace.save_client_card') }}</button>
            </form>
        </section>

        <section class="surface-card overflow-hidden">
            <div class="p-4 border-bottom"><div class="eyebrow">{{ __('workspace.order_history') }}</div><h2 class="section-title mt-1">{{ __('workspace.client_orders') }}</h2></div>
            <div class="client-order-list">
                @forelse($orders as $order)
                    <a class="client-order-row" href="{{ route('workspace.requests.show', $order) }}">
                        <span class="client-order-icon"><i class="bi bi-bag-check"></i></span>
                        <span class="flex-grow-1"><strong>{{ $order->items->first()?->name_snapshot ?? $order->title }}</strong><small>#{{ str($order->public_id)->substr(0, 8)->upper() }} · {{ $order->created_at->setTimezone($currentCompany->timezone)->format('d.m.Y H:i') }}</small></span>
                        <span class="text-end"><span class="badge-soft-{{ $order->status->color() }}">{{ __('workspace.status.'.$order->status->value) }}</span><strong class="client-order-price">{{ $order->price_minor ? $money->format($order->price_minor, $currentCompany->currency) : __('workspace.free') }}</strong></span>
                        <i class="bi bi-chevron-right text-secondary"></i>
                    </a>
                @empty
                    <div class="empty-builder"><span class="empty-builder-icon"><i class="bi bi-bag"></i></span><h3>{{ __('workspace.no_client_orders') }}</h3></div>
                @endforelse
            </div>
        </section>
    </div>

    <aside>
        <section class="surface-card p-4 sticky-lg-top client-bill-picker">
            <div class="client-section-heading"><div><div class="eyebrow">{{ __('workspace.bill_english') }}</div><h2 class="section-title mt-1">{{ __('workspace.ordered_services') }}</h2></div><span class="metric-icon metric-green"><i class="bi bi-receipt"></i></span></div>
            <p class="client-bill-hint">{{ __('workspace.ordered_services_hint') }}</p>
            <form method="GET" action="{{ route('workspace.stays.bill', $guestStay) }}" target="_blank" data-bill-selection>
                <input type="hidden" name="selection" value="1">
                <div class="bill-service-selection">
                    @forelse($billableOrders as $order)
                        <label><input type="checkbox" name="order_ids[]" value="{{ $order->id }}" @checked(in_array($order->payment_status,['pending','invoiced'],true))><span><strong>{{ $order->items->first()?->name_snapshot ?? $order->title }}</strong><small>{{ $order->created_at->format('d.m.Y') }} · {{ $order->payment_status === 'paid' ? __('workspace.payment_paid') : __('workspace.payment_due') }}</small></span><b>{{ $money->format($order->price_minor, $currentCompany->currency) }}</b></label>
                    @empty
                        <div class="bill-selection-empty"><i class="bi bi-receipt-cutoff"></i><span>{{ __('workspace.no_ordered_services') }}</span></div>
                    @endforelse
                </div>
                @if($billableOrders->isNotEmpty())
                    <div class="bill-selection-total"><span>{{ __('workspace.selected_total') }}</span><strong data-selected-total data-currency="{{ $currentCompany->currency }}">{{ $money->format((int) $billableOrders->whereIn('payment_status',['pending','invoiced'])->sum('price_minor'), $currentCompany->currency) }}</strong></div>
                    @foreach($billableOrders as $order)<input type="hidden" data-order-amount="{{ $order->id }}" value="{{ $order->price_minor }}">@endforeach
                    <button class="btn btn-primary w-100"><i class="bi bi-printer me-2"></i>{{ __('workspace.open_printable_bill') }}</button>
                    <a class="btn btn-outline-primary w-100 mt-2" target="_blank" href="{{ route('workspace.stays.bill',$guestStay) }}"><i class="bi bi-file-earmark-check me-2"></i>{{ __('workspace.final_bill') }}</a>
                    <div class="form-hint text-center mt-2">{{ __('workspace.bill_opens_english') }}</div>
                @endif
            </form>
            @if($guestStay->guest_email)
            <form class="mt-3 border-top pt-3" method="POST" action="{{ route('workspace.stays.bill.email',$guestStay) }}">@csrf
                @foreach($billableOrders->whereIn('payment_status',['pending','invoiced']) as $order)<input type="hidden" name="order_ids[]" value="{{ $order->id }}">@endforeach
                <label class="form-label">{{ __('workspace.additional_services_description') }}</label><textarea class="form-control mb-2" name="additional_description" rows="3" placeholder="{{ __('workspace.additional_services_description_hint') }}"></textarea>
                <button class="btn btn-success w-100"><i class="bi bi-envelope me-2"></i>{{ __('workspace.email_final_bill',['email'=>$guestStay->guest_email]) }}</button>
            </form>
            @endif
        </section>
    </aside>
</div>
@endsection

@push('scripts')
<script>
document.querySelector('[data-bill-selection]')?.addEventListener('change',event=>{
    if(event.target.type!=='checkbox') return;
    const form=event.currentTarget;
    const total=[...form.querySelectorAll('input[type="checkbox"]:checked')].reduce((sum,input)=>sum+Number(form.querySelector(`[data-order-amount="${input.value}"]`)?.value||0),0);
    const output=form.querySelector('[data-selected-total]');
    const currency=output.dataset.currency;
    output.textContent=currency==='IDR'?`Rp ${new Intl.NumberFormat('id-ID').format(total)}`:new Intl.NumberFormat('en-US',{style:'currency',currency}).format(total/100);
});
</script>
@endpush
