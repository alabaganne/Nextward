<x-mail::message>
# New roles for "{!! \App\Support\MailText::plain($search->name, 120) !!}"

These open roles match your saved search. Check each employer's requirements before you apply.

@php($arrangements = ['remote' => 'Remote', 'hybrid' => 'Hybrid', 'onsite' => 'On-site'])
@foreach ($opportunities as $opportunity)
<div class="role">
<p class="role-meta"><span class="tag">{{ $opportunity->kind === 'job' ? 'Job' : 'Internship' }}</span>@if (isset($arrangements[$opportunity->work_type]))&nbsp; {{ $arrangements[$opportunity->work_type] }}@endif</p>
<h2><a href="{{ $opportunity->public_url }}">{{ $opportunity->title }}</a></h2>
<p class="role-meta">{{ $opportunity->company?->display_name }} · {{ $opportunity->location_label }}</p>
@if ($opportunity->work_type === 'remote' && $opportunity->remote_eligibility === 'unknown')
<p class="role-note">Not confirmed that you can work on this remotely from Tunisia.</p>
@endif
<p class="role-note">{{ $opportunity->posted_at ? 'Posted '.$opportunity->posted_at->format('j M Y') : 'Posting date not given' }}</p>
</div>
@endforeach

<x-mail::button :url="$browseUrl">
See all matching roles
</x-mail::button>

The {{ config('app.name') }} team

<x-slot:subcopy>
You get {{ $search->frequency }} alerts for this saved search. [Pause these alerts]({{ $unsubscribeUrl }}) or [manage your saved searches]({{ route('saved-searches.index') }}).
</x-slot:subcopy>
</x-mail::message>
