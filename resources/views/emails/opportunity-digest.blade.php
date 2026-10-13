<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>{{ $search->name }} | Nextward</title></head>
<body style="font-family:Arial,sans-serif;color:#14253d;background:#f6f8fc;padding:24px">
<main style="max-width:620px;margin:auto;background:white;border-radius:16px;padding:32px">
    <p style="color:#2563eb;font-weight:bold">Nextward</p><h1 style="font-size:25px">Opportunities for {{ $search->name }}</h1>
    <p>These active opportunities match your saved search. Check the employer's requirements before applying.</p>
    @foreach ($opportunities as $opportunity)
        <article style="padding:20px 0;border-bottom:1px solid #e3e8f0">
            <h2 style="font-size:19px;margin:0 0 8px"><a style="color:#2563eb" href="{{ $opportunity->public_url }}">{{ $opportunity->title }}</a></h2>
            <p style="margin:4px 0">{{ $opportunity->company?->display_name }} · {{ $opportunity->location_label }}</p>
            <p style="margin:4px 0">{{ $opportunity->kind === 'job' ? 'Job' : 'Internship' }} · {{ ucfirst($opportunity->work_type) }}</p>
            @if ($opportunity->work_type === 'remote' && $opportunity->remote_eligibility === 'unknown')
                <p style="font-size:13px">Eligibility to work remotely from Tunisia is not confirmed.</p>
            @endif
            <p style="font-size:13px;color:#536276">{{ $opportunity->posted_at ? 'Posted '.$opportunity->posted_at->format('j M Y') : 'Original posting date not supplied' }}</p>
        </article>
    @endforeach
    <p><a href="{{ $browseUrl }}">View all matching opportunities</a></p>
    <p style="font-size:12px;color:#536276">You opted in to {{ $search->frequency }} opportunity alerts. <a href="{{ $unsubscribeUrl }}">Pause these alerts</a> or <a href="{{ route('saved-searches.index') }}">manage saved searches</a>.</p>
</main>
</body></html>
