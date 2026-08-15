<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bill #{{ str($guestStay->public_id)->substr(0, 8)->upper() }} · {{ $currentCompany->name }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="print-bill-body">
@php($money = app(\App\Support\Money::class))
<div class="print-bill-actions"><a href="{{ route('workspace.stays.show', $guestStay) }}" class="btn btn-light"><i class="bi bi-arrow-left me-2"></i>Back to client card</a><button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-2"></i>Print bill</button></div>
<main class="print-bill-sheet">
    <header class="print-bill-header"><div><span class="print-bill-mark"><i class="bi bi-buildings"></i></span><strong>{{ $currentCompany->name }}</strong><small>{{ $currentCompany->legal_name ?: 'Guest Services' }}</small></div><div><span>BILL</span><strong>#{{ str($guestStay->public_id)->substr(0, 8)->upper() }}</strong><small>Issued {{ now($currentCompany->timezone)->format('F j, Y') }}</small></div></header>
    <section class="print-bill-parties"><div><small>BILL TO</small><strong>{{ $guestStay->guest_name ?: 'Guest' }}</strong>@if($guestStay->guest_email)<span>{{ $guestStay->guest_email }}</span>@endif</div><div><small>STAY DETAILS</small><strong>{{ $guestStay->room->displayName() }}</strong><span>{{ $guestStay->check_in_at->setTimezone($currentCompany->timezone)->format('M j, Y') }} — {{ $guestStay->check_out_at->setTimezone($currentCompany->timezone)->format('M j, Y') }}</span></div></section>
    <table class="print-bill-table"><thead><tr><th>Service</th><th>Date</th><th>Payment</th><th>Amount</th></tr></thead><tbody>
        @forelse($orders as $order)<tr><td><strong>{{ $order->items->first()?->service?->localizedName('en') ?? $order->title }}</strong><small>Order #{{ str($order->public_id)->substr(0, 8)->upper() }}</small></td><td>{{ $order->created_at->setTimezone($currentCompany->timezone)->format('M j, Y') }}</td><td>{{ $order->payment_status === 'paid' ? 'Paid' : 'Room charge' }}</td><td>{{ $money->format($order->price_minor, $currentCompany->currency) }}</td></tr>
        @empty<tr><td colspan="4" class="print-bill-empty">No paid services selected.</td></tr>@endforelse
    </tbody></table>
    <section class="print-bill-total"><span>Total</span><strong>{{ $money->format($total, $currentCompany->currency) }}</strong></section>
    <footer><p>Thank you for choosing {{ $currentCompany->name }}.</p><span>Currency: {{ $currentCompany->currency }} · This bill includes only the services selected by the hotel team.</span></footer>
</main>
</body></html>
