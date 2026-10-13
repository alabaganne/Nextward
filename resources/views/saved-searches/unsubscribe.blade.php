<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><title>Opportunity alerts | Nextward</title></head>
<body style="font-family:system-ui,sans-serif;max-width:600px;margin:64px auto;padding:24px;color:#14253d">
    <a href="{{ url('/') }}">Nextward</a>
    @if ($done)
        <h1>Alerts paused</h1><p>You will no longer receive email alerts for “{{ $search->name }}”. Your saved search remains available in your account.</p>
    @else
        <h1>Pause these opportunity alerts?</h1><p>Stop email alerts for “{{ $search->name }}”. You can turn them on again from Saved searches.</p>
        <form method="post" action="{{ request()->fullUrl() }}">@csrf<button type="submit" style="background:#2563eb;color:white;border:0;padding:12px 20px;border-radius:8px;font:inherit">Pause alerts</button></form>
    @endif
    <p><a href="{{ route('saved-searches.index') }}">Manage saved searches</a></p>
</body>
</html>
