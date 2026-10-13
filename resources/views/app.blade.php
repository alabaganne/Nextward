<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $baseTitle = 'Internly';
            $title = 'Internly - Find internships and manage student applications';
            $description = 'Internly is a public internship discovery platform where students find internships, apply online, and companies manage student applications from one clean workspace.';
            $type = 'website';
            $canonical = url()->current();
            $keywords = 'internships, student internships, internship platform, internship applications, hiring students, companies, career opportunities';
            $structuredData = [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => 'Internly',
                'url' => config('app.url'),
                'description' => $description,
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => config('app.url') . '/internships?search={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ];

            if (request()->routeIs('home')) {
                $title = 'Internly - Public internship board for students and companies';
                $description = 'Discover internships on Internly. Students can browse public internship opportunities, apply online, and connect with companies looking for emerging talent.';
            } elseif (request()->routeIs('internships.index')) {
                $title = 'Browse internships - Internly';
                $description = 'Browse public internship opportunities by field, city, and company on Internly. Find student internships and apply with a student account.';
            } elseif (request()->routeIs('internships.show') && request()->route('internship')) {
                $internship = request()->route('internship');
                $companyName = optional(optional($internship->company)->user)->name;
                $cityName = optional($internship->city)->name;
                $fieldName = optional($internship->field)->name;
                $title = $internship->title . ' internship' . ($companyName ? ' at ' . $companyName : '') . ' - Internly';
                $description = \Illuminate\Support\Str::limit(strip_tags($internship->description), 155);
                $type = 'article';
                $structuredData = [
                    '@context' => 'https://schema.org',
                    '@type' => 'JobPosting',
                    'title' => $internship->title,
                    'description' => strip_tags($internship->description),
                    'datePosted' => optional($internship->created_at)->toDateString(),
                    'validThrough' => optional($internship->closing_at)->toDateString(),
                    'employmentType' => 'INTERN',
                    'hiringOrganization' => [
                        '@type' => 'Organization',
                        'name' => $companyName ?: 'Internly company',
                    ],
                    'jobLocation' => [
                        '@type' => 'Place',
                        'address' => [
                            '@type' => 'PostalAddress',
                            'addressLocality' => $cityName ?: '',
                        ],
                    ],
                    'industry' => $fieldName ?: 'Internship',
                    'url' => $canonical,
                ];
            } elseif (request()->routeIs('blog.index')) {
                $title = 'Internship blog - Student and company guides | Internly';
                $description = 'Read Internly guides about finding internships, applying better, hiring interns, and managing internship applications.';
                $keywords = 'internship blog, internship guides, student internship advice, hire interns, internship platform';
                $structuredData = [
                    '@context' => 'https://schema.org',
                    '@type' => 'Blog',
                    'name' => 'Internly Blog',
                    'url' => $canonical,
                    'description' => $description,
                ];
            } elseif (request()->routeIs('blog.show') && request()->route('slug')) {
                $post = collect(config('internly_blog'))->firstWhere('slug', request()->route('slug'));
                if ($post) {
                    $title = $post['title'] . ' | Internly Blog';
                    $description = $post['description'];
                    $keywords = $post['keywords'];
                    $type = 'article';
                    $structuredData = [
                        '@context' => 'https://schema.org',
                        '@type' => 'Article',
                        'headline' => $post['title'],
                        'description' => $description,
                        'datePublished' => $post['published_at'],
                        'dateModified' => $post['published_at'],
                        'author' => [
                            '@type' => 'Person',
                            'name' => 'Ala Baganne',
                            'url' => 'https://www.alabaganne.com',
                        ],
                        'publisher' => [
                            '@type' => 'Organization',
                            'name' => 'Internly',
                        ],
                        'mainEntityOfPage' => $canonical,
                    ];
                }
            }
        @endphp

        <title>{{ $title }}</title>
        <meta name="description" content="{{ $description }}">
        <meta name="keywords" content="{{ $keywords }}">
        <link rel="canonical" href="{{ $canonical }}">
        <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">

        <meta property="og:site_name" content="{{ $baseTitle }}">
        <meta property="og:title" content="{{ $title }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:type" content="{{ $type }}">
        <meta property="og:url" content="{{ $canonical }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $title }}">
        <meta name="twitter:description" content="{{ $description }}">

        <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/brand/internly/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/brand/internly/apple-touch-icon.png">
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
