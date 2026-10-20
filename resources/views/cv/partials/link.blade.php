@if ($print && $item['href'])<a href="{{ $item['href'] }}">{{ $item['text'] }}</a>@else{{ $item['text'] }}@endif
