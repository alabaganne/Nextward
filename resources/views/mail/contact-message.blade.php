<x-mail::message>
# New message from the contact form

**{!! $name !!}** · {!! $email !!}<br>
Topic: {!! $topic !!}

<x-mail::panel>
{!! $body !!}
</x-mail::panel>

@if ($pageUrl)
Sent from: {!! $pageUrl !!}

@endif
Reply to this email to answer {!! $name !!} directly.

<x-slot:subcopy>
Sent from the Nextward contact form on {{ $sentAt }}.
</x-slot:subcopy>
</x-mail::message>
