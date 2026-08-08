@php
    $compact = $compact ?? false;
    $progress = $order->status->guestProgressPercent();
    $currentStep = $order->status->guestProgressStep();
    $stepFill = $currentStep * 20;
    $cancelled = $order->status === \App\Enums\RequestStatus::Cancelled;
    $completed = $order->status === \App\Enums\RequestStatus::Completed;
    $waiting = $order->status === \App\Enums\RequestStatus::WaitingGuest;
    $statusText = $cancelled ? __('guest.progress_cancelled') : __('guest.status.'.$order->status->value);
    $steps = ['received', 'accepted', 'processing', 'confirmation', 'completed'];
@endphp

@if($compact)
    <div class="order-progress {{ $cancelled ? 'is-cancelled' : ($completed ? 'is-completed' : '') }}" data-progress="{{ $progress }}">
        <span class="order-progress-copy"><span>{{ __('guest.progress') }}</span><strong>{{ $statusText }}</strong></span>
        <span class="order-progress-segments" role="progressbar" aria-label="{{ __('guest.progress') }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}">
            @foreach($steps as $index => $step)<i class="{{ !$cancelled && $index <= $currentStep ? 'is-reached' : '' }}" title="{{ __('guest.progress_steps.'.$step) }}"></i>@endforeach
        </span>
    </div>
@else
    <section class="guest-work-progress {{ $cancelled ? 'is-cancelled' : ($completed ? 'is-completed' : '') }}" data-progress="{{ $progress }}">
        <div class="guest-work-progress-heading">
            <div><span class="eyebrow">{{ __('guest.work_progress') }}</span><h2>{{ $statusText }}</h2></div>
        </div>
        @if($cancelled)
            <p>{{ __('guest.cancelled_hint') }}</p>
        @else
            <div class="guest-progress-steps" role="progressbar" aria-label="{{ __('guest.progress') }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}">
                <span class="guest-progress-steps-fill" style="width: {{ $stepFill }}%"></span>
                @foreach($steps as $index => $step)
                    <div class="guest-progress-step {{ $index <= $currentStep ? 'is-reached' : '' }} {{ $index === $currentStep ? 'is-current' : '' }}">
                        <span>@if($index < $currentStep || $order->status === \App\Enums\RequestStatus::Completed)<i class="bi bi-check-lg"></i>@else{{ $index + 1 }}@endif</span>
                        <small>{{ __('guest.progress_steps.'.$step) }}</small>
                    </div>
                @endforeach
            </div>
            @if($waiting)<p class="guest-progress-attention"><i class="bi bi-person-raised-hand"></i>{{ __('guest.waiting_hint') }}</p>@endif
        @endif
    </section>
@endif
