<x-mail::message>
# Your Nextward account is ready

Hi {!! $name !!},

An account was created for you on Nextward, where students and professionals find internships and jobs in Tunisia and worldwide.

Choose a password to sign in. This also confirms your email address.

<x-mail::button :url="$url">
Choose my password
</x-mail::button>

The link works for {{ $minutes }} minutes. After that, use "Forgot password" on the sign-in page to get a new one.

The {{ config('app.name') }} team

<x-slot:subcopy>
If you did not expect this email, you can ignore it. If the button doesn't work, open this link: <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
