<x-mail::message>
# {{ $topic }}

**{!! $name !!}** · {!! $email !!}

<x-mail::panel>
{!! $body !!}
</x-mail::panel>

@if ($pageUrl)
Page: {!! $pageUrl !!}<br>
@endif
@if ($account)
Account: {!! $account !!}<br>
@else
Account: not signed in<br>
@endif
@if ($browser)
Browser: {!! $browser !!}<br>
@endif
@if ($hasScreenshot)
Screenshot attached.<br>
@endif

Reply to this email to answer {!! $name !!} directly.

<x-slot:subcopy>
Sent from the Nextward contact form on {{ $sentAt }}.
</x-slot:subcopy>
</x-mail::message>
