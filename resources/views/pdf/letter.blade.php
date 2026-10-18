<!doctype html>
<html lang="{{ $kit->locale }}">
<head>
<meta charset="utf-8">
<title>{{ $name }}</title>
<style>
    @page { margin: 56px 64px; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 11px; line-height: 1.65; color: #1e293b; }
    .name { font-size: 15px; font-weight: bold; color: #0d2353; }
    .contact { margin-top: 2px; color: #475569; font-size: 9.5px; }
    .date { margin: 28px 0 22px; color: #475569; }
    .body { white-space: pre-line; }
</style>
</head>
<body>
    <div class="name">{{ $name }}</div>
    @if ($contact)<div class="contact">{{ implode('  ·  ', $contact) }}</div>@endif
    <div class="date">{{ now()->locale($kit->locale)->translatedFormat('j F Y') }}</div>
    <div class="body">{{ $kit->cover_letter }}</div>
</body>
</html>
