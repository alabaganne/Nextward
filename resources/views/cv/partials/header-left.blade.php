<header class="d-head d-head-left">
    <div>
        <h1 class="d-name">{{ $cv['name'] }}</h1>
        @if (filled($cv['headline']))<div class="d-headline">{{ $cv['headline'] }}</div>@endif
    </div>
    @if ($contact['items'] || $contact['links'])
        <div class="d-contact-col">
            @foreach ($contact['items'] as $item)<span>@include('cv.partials.link')</span>@endforeach
            @foreach ($contact['links'] as $item)<span class="d-link">@include('cv.partials.link')</span>@endforeach
        </div>
    @endif
</header>
