<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Role alerts | Nextward</title>
    <link rel="icon" href="/favicon.ico">
    @vite('resources/css/app.css')
</head>
<body class="flex min-h-screen flex-col bg-pale font-sans text-navy antialiased">
<header class="line-art bg-navy">
    <div class="public-container flex h-[84px] items-center">
        <a class="focus-ring inline-flex items-center gap-2.5 rounded-lg text-[21px] font-semibold text-white focus-visible:outline-white" href="{{ url('/') }}"><img src="/brand/nextward/symbol-192.png" alt="" width="36" height="36" class="size-9">Nextward</a>
    </div>
</header>
<main class="public-container flex flex-1 items-start justify-center py-20 max-[768px]:py-12">
    <div class="w-full max-w-[560px] rounded-card border border-line bg-white p-10 max-[768px]:p-6">
        <p class="landing-label">Role alerts</p>
        @if ($done)
            <h1 class="mt-3 text-[32px] leading-[1.25] text-navy">Alerts paused</h1>
            <p class="mt-4 text-[16px] leading-[1.7] text-subtle">You won't get email alerts for “{{ $search->name }}” anymore. The saved search stays in your account.</p>
        @else
            <h1 class="mt-3 text-[32px] leading-[1.25] text-navy">Pause these alerts?</h1>
            <p class="mt-4 text-[16px] leading-[1.7] text-subtle">Stop email alerts for “{{ $search->name }}”. You can turn them back on from Saved searches.</p>
            <form method="post" action="{{ request()->fullUrl() }}" class="mt-8">@csrf<button type="submit" class="cta cta-primary focus-ring">Pause alerts</button></form>
        @endif
        <p class="mt-8 border-t border-line pt-6 text-[15px]"><a class="landing-link focus-ring" href="{{ route('saved-searches.index') }}">Manage saved searches</a></p>
    </div>
</main>
</body>
</html>
