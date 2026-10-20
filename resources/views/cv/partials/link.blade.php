@if ($print && $item['href'])<a href="{{ $item['href'] }}" dir="ltr">{{ $item['text'] }}</a>@elseif ($item['href'])<bdi dir="ltr">{{ $item['text'] }}</bdi>@else<bdi>{{ $item['text'] }}</bdi>@endif
