<x-mail::message>
# Your job leads for today

@foreach ($leads as $lead)
**{!! $lead['company'] !!}**: {!! $lead['title'] !!}<br>
{!! $lead['reason'] !!}

@endforeach
<x-mail::button :url="$url">
Open job leads
</x-mail::button>

The {{ config('app.name') }} team

<x-slot:subcopy>
You get this email because job lead emails are on (LEADS_DIGEST_EMAIL). If the button doesn't work, open this link: <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
