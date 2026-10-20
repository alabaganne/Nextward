@php($contact = \App\Support\CvTemplates::contact($cv['contact']))
@php($template = $style['template'])
@include('cv.partials.head')
<body class="{{ $print ? 'is-print' : 'is-preview' }}">
<article class="tpl tpl-{{ $template }} doc-{{ $style['font'] }} paper-{{ $style['paper'] }}" style="--doc-accent: {{ $accent }}">
@if ($template === 'soren')
    <aside class="d-side">
        <h1 class="d-name">{{ $cv['name'] }}</h1>
        @if (filled($cv['headline']))<div class="d-headline">{{ $cv['headline'] }}</div>@endif
        @if ($contact['items'] || $contact['links'])
            <div class="d-side-sec">
                <h4>{{ $labels['contact'] }}</h4>
                @foreach ([...$contact['items'], ...$contact['links']] as $item)<div class="d-side-line">@include('cv.partials.link')</div>@endforeach
            </div>
        @endif
        @if ($cv['skills'])
            <div class="d-side-sec"><h4>{{ $labels['skills'] }}</h4><div class="d-side-skill"><span>{{ implode(', ', $cv['skills']) }}</span></div></div>
        @endif
        @if ($cv['education'])
            <div class="d-side-sec">
                <h4>{{ $labels['education'] }}</h4>
                @foreach ($cv['education'] as $school)
                    @php($degree = implode(', ', array_filter([$school['degree'], $school['field']])))
                    <div class="d-side-skill">
                        <strong>{{ $degree ?: $school['school'] }}</strong>
                        <span>{{ implode(' · ', array_filter([$degree ? $school['school'] : '', \App\Support\CvTemplates::dateRange($school['start'], $school['end'], false, $locale)])) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
        @if ($cv['languages'])
            <div class="d-side-sec"><h4>{{ $labels['languages'] }}</h4>@foreach ($cv['languages'] as $language)<div class="d-side-line">{{ $language }}</div>@endforeach</div>
        @endif
    </aside>
    <div class="d-main">
        @include('cv.partials.sections', ['sections' => ['summary', 'experience', 'projects'], 'summaryLabel' => $labels['profile']])
    </div>
@else
    @include('cv.partials.header-'.match ($template) { 'halden' => 'left', 'atlas' => 'block', default => 'center' })
    <div class="d-body">
        @include('cv.partials.sections', ['sections' => ['summary', 'experience', 'education', 'skills', 'projects', 'languages'], 'summaryLabel' => $labels['summary']])
    </div>
@endif
</article>
</body>
</html>
