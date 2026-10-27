<x-mail::layout>
    {{-- Header --}}
    <x-slot:header>
        <x-mail::header :url="config('app.url')">
            {{ config('app.name') }}
        </x-mail::header>
    </x-slot:header>

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Footer --}}
    <x-slot:footer>
        <x-mail::footer>
            {{ config('app.name') }} · IT internships and jobs around the world
            Browse roles: {{ rtrim(config('app.url'), '/') }}/opportunities
            Settings: {{ rtrim(config('app.url'), '/') }}/settings
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
