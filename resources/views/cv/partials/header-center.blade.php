<header class="d-head d-head-center">
    <h1 class="d-name">{{ $cv['name'] }}</h1>
    @if (filled($cv['headline']))<div class="d-headline">{{ $cv['headline'] }}</div>@endif
    @if ($contact['items'])<div class="d-contact">@foreach ($contact['items'] as $item)<span>@include('cv.partials.link')</span>@endforeach</div>@endif
    @if ($contact['links'])<div class="d-links">@foreach ($contact['links'] as $item)<span>@include('cv.partials.link')</span>@endforeach</div>@endif
</header>
