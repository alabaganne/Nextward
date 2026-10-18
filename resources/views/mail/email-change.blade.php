<x-mail::message>
# Confirm your new email

Hi {!! $name !!},

Someone asked to change the email of your Nextward account.

<x-mail::panel>
From: {!! $old !!}<br>
To: {!! $new !!}
</x-mail::panel>

If this was you, confirm the change. We will then send a link to the new address so you can verify it too.

<x-mail::button :url="$url">
Confirm the change
</x-mail::button>

The link works for {{ $minutes }} minutes. Until you confirm, nothing changes and you keep signing in with this address.

If you did not ask for this, ignore this email and change your password.

The {{ config('app.name') }} team

<x-slot:subcopy>
If the button doesn't work, open this link: <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
