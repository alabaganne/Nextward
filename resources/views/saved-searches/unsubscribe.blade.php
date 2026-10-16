<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Opportunity alerts | Nextward</title>
    <link rel="icon" href="/favicon.ico">
    <style>
        body { margin: 0; background: #f6f8fc; color: #0c1322; font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; }
        main { max-width: 520px; margin: 0 auto; padding: 56px 16px; }
        .logo { display: inline-flex; align-items: center; gap: 10px; margin-bottom: 24px; color: #0a1738; font-family: 'Space Grotesk', Inter, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; letter-spacing: -0.02em; text-decoration: none; }
        .logo img { height: 36px; width: 36px; display: block; }
        .card { background: #fff; border-radius: 20px; padding: 32px; }
        h1 { margin: 0 0 12px; color: #0a1738; font-family: 'Space Grotesk', Inter, Helvetica, Arial, sans-serif; font-size: 24px; letter-spacing: -0.02em; line-height: 1.25; }
        p { margin: 0 0 16px; }
        button { background: #0a1738; color: #fff; border: 0; border-radius: 12px; padding: 12px 22px; font: inherit; font-weight: 600; cursor: pointer; }
        button:hover { background: #0f2150; }
        button:focus-visible, a:focus-visible { outline: 2px solid #2563eb; outline-offset: 3px; }
        a { color: #2563eb; }
        .muted { color: #5b6677; font-size: 14px; margin: 20px 0 0; }
    </style>
</head>
<body>
<main>
    <a class="logo" href="{{ url('/') }}"><img src="/brand/nextward/symbol-192.png" alt="" width="36" height="36">Nextward</a>
    <div class="card">
        @if ($done)
            <h1>Alerts paused</h1>
            <p>You won't get email alerts for “{{ $search->name }}” anymore. The saved search stays in your account.</p>
        @else
            <h1>Pause these alerts?</h1>
            <p>Stop email alerts for “{{ $search->name }}”. You can turn them back on from Saved searches.</p>
            <form method="post" action="{{ request()->fullUrl() }}">@csrf<button type="submit">Pause alerts</button></form>
        @endif
        <p class="muted"><a href="{{ route('saved-searches.index') }}">Manage saved searches</a></p>
    </div>
</main>
</body>
</html>
