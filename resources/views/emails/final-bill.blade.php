<!doctype html><html lang="en"><body style="font-family:Arial,sans-serif;color:#172033;line-height:1.6">
<h1 style="font-size:24px">Your final bill</h1>
<p>Hello {{ $guestStay->guest_name ?: 'Guest' }},</p>
<p>Attached is the final bill for your stay at {{ $guestStay->company->name }} in {{ $guestStay->room->displayName() }}.</p>
@if($additionalDescription)<p>The additional service details are attached as a separate file.</p>@endif
<p>Thank you for staying with us.</p>
</body></html>
