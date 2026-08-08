@php
    $compact = $compact ?? false;
    $progress = $order->status->guestProgressPercent();
    $currentStep = $order->status->guestProgressStep();
    $stepFill = $currentStep * 20;
    $cancelled = $order->status === \App\Enums\RequestStatus::Cancelled;
    $waiting = $order->status === \App\Enums\RequestStatus::WaitingGuest;
    $steps = ['received', 'accepted', 'working', 'ready', 'completed'];
@endphp

@if($compact)
    <div class="order-progress {{ $cancelled ? 'is-cancelled' : '' }}" data-progress="{{ $progress }}">
        <span class="order-progress-copy">{{ $cancelled ? __('guest.progress_cancelled') : ($waiting ? __('guest.progress_waiting') : __('guest.progress_percent', ['percent' => $progress])) }}</span>
        <span class="order-progress-track" role="progressbar" aria-label="{{ __('guest.progress') }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}"><span style="width: {{ $progress }}%"></span></span>
    </div>
@else
    <section class="guest-work-progress {{ $cancelled ? 'is-cancelled' : '' }}" data-progress="{{ $progress }}">
        <div class="guest-work-progress-heading">
            <div><span class="eyebrow">{{ __('guest.work_progress') }}</span><h2>{{ $cancelled ? __('guest.progress_cancelled') : ($waiting ? __('guest.progress_waiting') : __('guest.progress_percent', ['percent' => $progress])) }}</h2></div>
            @unless($cancelled)<strong>{{ $progress }}%</strong>@endunless
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
