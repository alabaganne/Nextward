<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', ($page['props']['locale'] ?? app()->getLocale())) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" href="/brand/nextward/Nextward-colorful-symbol.png" type="image/png" sizes="1254x1254">
        <link rel="apple-touch-icon" href="/brand/nextward/Nextward-colorful-symbol.png">
        <meta name="theme-color" content="#2563EB">

        <!-- Fonts: Inter for public pages, Plus Jakarta Sans for the workspace, and Space Grotesk for headings. -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap">

        <!-- Scripts -->
        @inertiaHead
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
