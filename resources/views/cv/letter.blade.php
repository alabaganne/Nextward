@php($contact = \App\Support\CvTemplates::contact($cv['contact']))
@php($template = $style['template'])
@include('cv.partials.head')
<body class="{{ $print ? 'is-print' : 'is-preview' }}">
<article class="tpl tpl-letter tpl-{{ $template === 'soren' ? 'halden' : $template }} doc-{{ $style['font'] }} paper-{{ $style['paper'] }}" style="--doc-accent: {{ $accent }}">
    @include('cv.partials.header-'.match ($template) { 'halden', 'soren' => 'left', 'atlas' => 'block', default => 'center' })
    <div class="d-body">
        <div class="d-date">{{ $date }}</div>
        <div class="d-letter">{{ $letter }}</div>
    </div>
</article>
</body>
</html>
