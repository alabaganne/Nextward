<header class="d-head d-head-block">
    <h1 class="d-name">{{ $cv['name'] }}</h1>
    @if (filled($cv['headline']))<div class="d-headline">{{ $cv['headline'] }}</div>@endif
    @if ($contact['items'] || $contact['links'])<div class="d-contact">@foreach ([...$contact['items'], ...$contact['links']] as $item)<span>@include('cv.partials.link')</span>@endforeach</div>@endif
</header>
