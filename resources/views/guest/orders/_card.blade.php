<a class="guest-order-card" data-order-id="{{ $order->public_id }}" data-order-status="{{ $order->status->value }}" data-payment-status="{{ $order->payment_status }}" href="{{ route('guest.orders.show', [$company, $order]) }}">
    <span class="order-status-icon status-{{ $order->status->value }}"><i class="bi {{ in_array($order->status, [\App\Enums\RequestStatus::Ready, \App\Enums\RequestStatus::Completed], true) ? 'bi-check-lg' : ($order->status === \App\Enums\RequestStatus::Cancelled ? 'bi-x-lg' : 'bi-hourglass-split') }}"></i></span>
    <div class="order-card-copy">
        <div><strong>{{ __('guest.order_number', ['number'=>str($order->public_id)->substr(0, 8)->upper()]) }}</strong><time>{{ $order->created_at->format('d.m · H:i') }}</time></div>
        <h2>{{ $order->items->first()?->service?->localizedName() ?? $order->title }}</h2>
        <p>{{ __('guest.items_count', ['count'=>$order->items->sum('quantity')]) }} · @if($order->price_minor){{ $money->format($order->price_minor, $company->currency) }}@else{{ __('guest.free') }}@endif</p>
        @include('guest.orders._progress', ['order' => $order, 'compact' => true])
    </div>
    <i class="bi bi-chevron-right order-chevron"></i>
</a>
