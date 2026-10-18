@php
    $fr = $locale === 'fr';
    $t = fn ($en, $french) => $fr ? $french : $en;
    $dates = fn ($row) => trim(implode(' – ', array_filter([$row['start'] ?? '', $row['end'] ?? ''])));
@endphp
<!doctype html>
<html lang="{{ $locale }}">
<head>
<meta charset="utf-8">
<title>{{ $cv['name'] ?? 'CV' }}</title>
<style>
    @page { margin: 34px 40px; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 10.5px; line-height: 1.5; color: #1e293b; }
    h1 { margin: 0; font-size: 22px; color: #0d2353; }
    .headline { margin-top: 2px; font-size: 12.5px; color: #2563eb; font-weight: bold; }
    .contact { margin-top: 6px; color: #475569; font-size: 9.5px; }
    h2 { margin: 18px 0 6px; font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: #0d2353; }
    .row { margin-bottom: 9px; }
    .row-head { width: 100%; }
    .row-head td { padding: 0; vertical-align: top; }
    .title { font-weight: bold; color: #0f172a; }
    .dates { text-align: right; color: #64748b; white-space: nowrap; width: 110px; }
    .sub { color: #475569; }
    ul { margin: 3px 0 0 14px; padding: 0; }
    li { margin-bottom: 2px; }
    .skills span { display: inline-block; margin: 0 4px 4px 0; padding: 2px 7px; background: #eef4ff; color: #1e3a8a; border-radius: 4px; }
</style>
</head>
<body>
    <h1>{{ $cv['name'] ?? '' }}</h1>
    @if (!empty($cv['headline']))<div class="headline">{{ $cv['headline'] }}</div>@endif
    @if (!empty($cv['contact']))<div class="contact">{{ implode('  ·  ', $cv['contact']) }}</div>@endif

    @if (!empty($cv['summary']))
        <h2>{{ $t('Profile', 'Profil') }}</h2>
        <div>{{ $cv['summary'] }}</div>
    @endif

    @if (!empty($cv['experience']))
        <h2>{{ $t('Experience', 'Expérience') }}</h2>
        @foreach ($cv['experience'] as $job)
            <div class="row">
                <table class="row-head"><tr><td><span class="title">{{ $job['title'] }}</span>@if (!empty($job['company'])) <span class="sub">· {{ $job['company'] }}</span>@endif @if (!empty($job['location'])) <span class="sub">· {{ $job['location'] }}</span>@endif</td><td class="dates">{{ $dates($job) }}</td></tr></table>
                @if (!empty($job['highlights']))<ul>@foreach ($job['highlights'] as $line)<li>{{ $line }}</li>@endforeach</ul>@endif
            </div>
        @endforeach
    @endif

    @if (!empty($cv['education']))
        <h2>{{ $t('Education', 'Formation') }}</h2>
        @foreach ($cv['education'] as $school)
            <div class="row">
                <table class="row-head"><tr><td><span class="title">{{ $school['school'] }}</span>@if (!empty($school['degree']) || !empty($school['field'])) <span class="sub">· {{ implode(', ', array_filter([$school['degree'] ?? '', $school['field'] ?? ''])) }}</span>@endif</td><td class="dates">{{ $dates($school) }}</td></tr></table>
            </div>
        @endforeach
    @endif

    @if (!empty($cv['projects']))
        <h2>{{ $t('Projects', 'Projets') }}</h2>
        @foreach ($cv['projects'] as $project)
            <div class="row"><span class="title">{{ $project['name'] }}</span>@if (!empty($project['url'])) <span class="sub">· {{ $project['url'] }}</span>@endif
                @if (!empty($project['summary']))<div>{{ $project['summary'] }}</div>@endif
            </div>
        @endforeach
    @endif

    @if (!empty($cv['skills']))
        <h2>{{ $t('Skills', 'Compétences') }}</h2>
        <div class="skills">@foreach ($cv['skills'] as $skill)<span>{{ $skill }}</span>@endforeach</div>
    @endif

    @if (!empty($cv['languages']))
        <h2>{{ $t('Languages', 'Langues') }}</h2>
        <div>{{ implode(', ', $cv['languages']) }}</div>
    @endif
</body>
</html>
