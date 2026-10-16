<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', ($page['props']['locale'] ?? app()->getLocale())) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" href="/brand/nextward/Nextward-colorful-symbol.png" type="image/png" sizes="1254x1254">
        <link rel="apple-touch-icon" href="/brand/nextward/Nextward-colorful-symbol.png">
        <meta name="theme-color" content="#2563EB">
        <link rel="manifest" href="/site.webmanifest">
        @php($blogLocale = ($page['props']['locale'] ?? 'en') === 'fr' ? 'fr' : 'en')
        <link rel="alternate" type="application/rss+xml" title="{{ $blogLocale === 'fr' ? 'Guides carrière Nextward' : 'Nextward career guides' }}" href="{{ $blogLocale === 'fr' ? '/fr/blog/feed.xml' : '/blog/feed.xml' }}">

        <!-- Scripts -->
        @inertiaHead
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
