@php($dates = fn ($row) => \App\Support\CvTemplates::dateRange($row['start'] ?? '', $row['end'] ?? '', (bool) ($row['current'] ?? false), $locale))
@foreach ($sections as $section)
    @if ($section === 'summary' && filled($cv['summary']))
        <section class="d-sec">
            <h3 class="d-sec-title">{{ $summaryLabel }}</h3>
            <p class="d-summary">{{ $cv['summary'] }}</p>
        </section>
    @elseif ($section === 'experience' && $cv['experience'])
        <section class="d-sec">
            <h3 class="d-sec-title">{{ $labels['experience'] }}</h3>
            @foreach ($cv['experience'] as $job)
                <div class="d-entry">
                    <div class="d-entry-head">
                        <div>
                            <div class="d-role">{{ $job['title'] }}</div>
                            @if (filled($job['company']) || filled($job['location']))<div class="d-org">{{ implode(' · ', array_filter([$job['company'], $job['location']])) }}</div>@endif
                        </div>
                        <div class="d-dates">{{ $dates($job) }}</div>
                    </div>
                    @if ($job['highlights'])<ul class="d-bullets">@foreach ($job['highlights'] as $line)<li>{{ $line }}</li>@endforeach</ul>@endif
                </div>
            @endforeach
        </section>
    @elseif ($section === 'education' && $cv['education'])
        <section class="d-sec">
            <h3 class="d-sec-title">{{ $labels['education'] }}</h3>
            @foreach ($cv['education'] as $school)
                @php($degree = \App\Support\CvTemplates::join([$school['degree'], $school['field']], $locale))
                <div class="d-entry">
                    <div class="d-entry-head">
                        <div>
                            <div class="d-role">{{ $degree ?: $school['school'] }}</div>
                            @if ($degree)<div class="d-org">{{ $school['school'] }}</div>@endif
                        </div>
                        <div class="d-dates">{{ $dates($school) }}</div>
                    </div>
                </div>
            @endforeach
        </section>
    @elseif ($section === 'skills' && $cv['skills'])
        <section class="d-sec">
            <h3 class="d-sec-title">{{ $labels['skills'] }}</h3>
            <div class="d-skills"><div class="d-skill-row">{{ \App\Support\CvTemplates::join($cv['skills'], $locale) }}</div></div>
        </section>
    @elseif ($section === 'projects' && $cv['projects'])
        <section class="d-sec">
            <h3 class="d-sec-title">{{ $labels['projects'] }}</h3>
            @foreach ($cv['projects'] as $project)
                <div class="d-entry">
                    <div class="d-entry-head">
                        <div class="d-role">{{ $project['name'] }}@if (filled($project['url']))<span class="d-proj-link"> · @include('cv.partials.link', ['item' => \App\Support\CvTemplates::contact([$project['url']])['links'][0] ?? ['text' => $project['url'], 'href' => null]])</span>@endif</div>
                    </div>
                    @if (filled($project['summary']))<div class="d-note">{{ $project['summary'] }}</div>@endif
                </div>
            @endforeach
        </section>
    @elseif ($section === 'languages' && $cv['languages'])
        <section class="d-sec">
            <h3 class="d-sec-title">{{ $labels['languages'] }}</h3>
            <div class="d-skills"><div class="d-skill-row">{{ \App\Support\CvTemplates::join($cv['languages'], $locale) }}</div></div>
        </section>
    @endif
@endforeach
