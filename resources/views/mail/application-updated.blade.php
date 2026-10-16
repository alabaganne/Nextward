<x-mail::message>
# {!! $headline !!}

Hi {!! $candidate !!},

{!! $body !!}

<x-mail::panel>
**{!! $role !!}**<br>
{!! $company !!} · Status: {!! $statusLabel !!}
</x-mail::panel>

<x-mail::button :url="$url">
See your application
</x-mail::button>

@if ($tip)
{!! $tip !!}

@endif
The {{ config('app.name') }} team

<x-slot:subcopy>
You get this email because you applied to this role on Nextward. If the button doesn't work, open this link: <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
