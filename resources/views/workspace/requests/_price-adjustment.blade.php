@php
    $priceItems = $serviceRequest->items;
    $selectedPriceItem = $priceItems->first();
    $minorToInput = fn (int $minor) => $currentCompany->currency === 'IDR'
        ? (string) $minor
        : number_format($minor / 100, 2, '.', '');
    $currentPriceInput = $minorToInput((int) ($selectedPriceItem?->total_price_minor ?? $serviceRequest->price_minor ?? 0));
@endphp
<hr class="my-3">
<section class="request-price-adjustment">
    <h3 class="section-title mb-1">{{ __('workspace.price_adjustment') }}</h3>
    <p class="form-hint mb-3">{{ __('workspace.price_adjustment_hint') }}</p>
    <form method="POST" action="{{ route('workspace.requests.price', $serviceRequest) }}" data-price-adjustment-form>
        @csrf @method('PATCH')
        <div class="mb-2">
            <label class="form-label">{{ __('workspace.price_service') }}</label>
            @if($priceItems->count() > 1)
                <select class="form-select" name="service_request_item_id" data-price-item-select required>
                    @foreach($priceItems as $item)
                        <option value="{{ $item->id }}" data-price="{{ $minorToInput($item->total_price_minor) }}">{{ $item->name_snapshot }}</option>
                    @endforeach
                </select>
            @elseif($selectedPriceItem)
                <input type="hidden" name="service_request_item_id" value="{{ $selectedPriceItem->id }}">
                <div class="price-adjustment-service"><i class="bi bi-bag-check"></i><span>{{ $selectedPriceItem->name_snapshot }}</span></div>
            @else
                <div class="price-adjustment-service"><i class="bi bi-bag-check"></i><span>{{ $serviceRequest->title }}</span></div>
            @endif
        </div>
        <div class="mb-2">
            <label class="form-label">{{ __('workspace.new_service_price', ['currency' => $currentCompany->currency]) }}</label>
            <input class="form-control" type="number" min="0" max="999999999" step="{{ $currentCompany->currency === 'IDR' ? 1 : '.01' }}" name="price" value="{{ $currentPriceInput }}" data-adjusted-price required>
        </div>
        <div class="mb-2">
            <label class="form-label">{{ __('workspace.price_adjustment_comment') }}</label>
            <textarea class="form-control" name="comment" rows="2" maxlength="1000" required placeholder="{{ __('workspace.price_adjustment_comment_hint') }}"></textarea>
        </div>
        <button class="btn btn-outline-primary w-100"><i class="bi bi-cash-coin me-2"></i>{{ __('workspace.save_price_adjustment') }}</button>
    </form>

    @if($serviceRequest->priceAdjustments->isNotEmpty())
        <div class="price-adjustment-history">
            <strong>{{ __('workspace.price_adjustments_history') }}</strong>
            @foreach($serviceRequest->priceAdjustments as $adjustment)
                <article>
                    <span>{{ $adjustment->service_name_snapshot }}</span>
                    <b>{{ $money->format($adjustment->previous_price_minor, $currentCompany->currency) }} → {{ $money->format($adjustment->new_price_minor, $currentCompany->currency) }}</b>
                    <p>{{ $adjustment->comment }}</p>
                    <small>{{ $adjustment->manager?->name ?? __('workspace.system') }} · {{ $adjustment->created_at->setTimezone($currentCompany->timezone)->format('d.m.Y H:i') }}</small>
                </article>
            @endforeach
        </div>
    @endif
</section>
