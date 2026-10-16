<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Internship;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicSeo
{
    public static function isPublic(Request $request): bool
    {
        return $request->routeIs('home', 'about', 'contact', 'privacy', 'terms', 'blog.*', 'audience.*', 'opportunities.index', 'opportunities.category', 'opportunities.show', 'companies.public', 'fr.*')
            && ! $request->routeIs('fr.login*', 'fr.register*', 'fr.password.*');
    }

    public static function forRequest(Request $request): array
    {
        $fr = $request->is('fr', 'fr/*');
        $path = '/'.ltrim(preg_replace('#^fr/?#', '', $request->path()), '/');
        $texts = self::copy($fr ? 'fr' : 'en');
        $key = match ($path) {
            '/' => 'home', '/for-students' => 'students', '/for-professionals' => 'professionals',
            '/about' => 'about', '/contact' => 'contact', '/privacy' => 'privacy', '/terms' => 'terms', '/blog' => 'blog', default => null,
        };
        $copy = $texts[$key] ?? ['title' => 'Nextward', 'description' => ''];
        $seo = self::base($request, $copy['title'], $copy['description'], self::isPublic($request));
        if ($key) {
            $seo['alternates'] = self::alternates($path);
        }
        if ($key === 'home') {
            $seo['structured_data'] = [self::organization(), [
                '@context' => 'https://schema.org', '@type' => 'WebSite', '@id' => self::url('/').'#website', 'name' => 'Nextward', 'url' => self::url('/'),
                'inLanguage' => $fr ? 'fr' : 'en', 'publisher' => ['@id' => self::url('/').'#organization'],
                'potentialAction' => ['@type' => 'SearchAction', 'target' => self::url('/opportunities').'?search={search_term_string}', 'query-input' => 'required name=search_term_string'],
            ]];
        }
        if ($key === 'about') {
            $seo['structured_data'] = [self::organization(), [
                '@context' => 'https://schema.org', '@type' => 'AboutPage', 'name' => $copy['title'], 'url' => $seo['canonical'],
                'inLanguage' => $fr ? 'fr' : 'en', 'about' => ['@id' => self::url('/').'#organization'],
            ]];
        }
        if ($key === 'blog') {
            $seo['structured_data'] = [[
                '@context' => 'https://schema.org', '@type' => 'Blog', 'name' => $copy['title'], 'description' => $copy['description'],
                'url' => $seo['canonical'], 'inLanguage' => $fr ? 'fr' : 'en', 'publisher' => ['@id' => self::url('/').'#organization'],
                'blogPost' => collect(Blog::all($fr ? 'fr' : 'en'))->reject(fn ($post) => $post['draft'])->take(20)->map(fn ($post) => [
                    '@type' => 'BlogPosting', 'headline' => $post['title'], 'url' => Blog::url($post), 'datePublished' => $post['published_at'],
                ])->values()->all(),
            ]];
        }
        return $seo;
    }

    public static function post(array $post, ?array $translation = null): array
    {
        $fr = $post['locale'] === 'fr';
        $seo = self::base(request(), $post['title'].' | Nextward', $post['description'], !$post['draft']);
        $seo['canonical'] = Blog::url($post);
        $seo['type'] = 'article';
        $seo['published_time'] = $post['published_at'];
        $seo['modified_time'] = $post['updated_at'];
        if ($post['cover']) {
            $seo['image'] = self::url($post['cover']);
            $seo['image_alt'] = $post['cover_alt'] ?: $post['title'];
        }
        if ($translation) {
            $en = $fr ? $translation : $post;
            $seo['alternates'] = [
                ['lang' => 'en', 'url' => Blog::url($en)],
                ['lang' => 'fr', 'url' => Blog::url($fr ? $post : $translation)],
                ['lang' => 'x-default', 'url' => Blog::url($en)],
            ];
        }
        $blog = self::url($fr ? '/fr/blog' : '/blog');
        $seo['structured_data'] = [[
            '@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $post['title'],
            'description' => $post['description'], 'image' => [$seo['image']], 'inLanguage' => $post['locale'],
            'datePublished' => $post['published_at'], 'dateModified' => $post['updated_at'],
            'mainEntityOfPage' => $seo['canonical'], 'url' => $seo['canonical'], 'articleSection' => $post['category'],
            'author' => self::founder(), 'publisher' => self::organization(false), 'isPartOf' => ['@type' => 'Blog', 'url' => $blog],
        ], [
            '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Nextward', 'item' => self::url($fr ? '/fr' : '/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $fr ? 'Guides carrière' : 'Career guides', 'item' => $blog],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $post['title'], 'item' => $seo['canonical']],
            ],
        ]];
        if ($post['faq']) {
            $seo['structured_data'][] = [
                '@context' => 'https://schema.org', '@type' => 'FAQPage', 'inLanguage' => $post['locale'],
                'mainEntity' => array_map(fn ($item) => ['@type' => 'Question', 'name' => $item['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']]], $post['faq']),
            ];
        }
        return $seo;
    }

    public static function company(Company $company, int $openRoles): array
    {
        $fr = request()->is('fr/*');
        $name = $company->display_name;
        $location = $company->city?->name ?: $company->location;
        $about = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $company->about))));
        $description = $fr
            ? ($openRoles
                ? Str::limit("{$openRoles} ".($openRoles === 1 ? 'offre ouverte' : 'stages et emplois ouverts')." chez {$name}".($location ? " ({$location})" : '').'. '.($about ?: 'Consultez les exigences, le lieu et la façon de postuler sur Nextward.'), 155)
                : Str::limit($about ?: "{$name} sur Nextward : profil de l’entreprise et anciens stages et emplois.", 155))
            : ($openRoles
                ? Str::limit("{$openRoles} open ".($openRoles === 1 ? 'opportunity' : 'internships and jobs')." at {$name}".($location ? " ({$location})" : '').'. '.($about ?: 'See the requirements, location and how to apply on Nextward.'), 155)
                : Str::limit($about ?: "{$name} on Nextward: company profile and past internships and jobs.", 155));
        $seo = self::base(request(), ($fr ? "Stages et emplois chez {$name}" : $name.' internships and jobs').' | Nextward', $description, $company->isIndexable($openRoles));
        $seo['canonical'] = $company->publicUrl($fr ? 'fr' : 'en');
        $seo['alternates'] = self::pairs($company->publicUrl('en'), $company->publicUrl('fr'));
        if ($company->logo && filter_var($company->logo, FILTER_VALIDATE_URL)) {
            $seo['image'] = $company->logo;
            $seo['image_alt'] = $name;
        }
        $seo['structured_data'] = [array_filter([
            '@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $name, 'url' => $seo['canonical'],
            'description' => $about ? Str::limit($about, 500) : null,
            'sameAs' => $company->website && filter_var($company->website, FILTER_VALIDATE_URL) ? [$company->website] : null,
            'logo' => isset($seo['image_alt']) ? $seo['image'] : null,
            'address' => $location || $company->country_code ? array_filter(['@type' => 'PostalAddress', 'addressLocality' => $location, 'addressCountry' => $company->country_code]) : null,
        ]), [
            '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => $fr ? 'Opportunités' : 'Opportunities', 'item' => self::url($fr ? '/fr/opportunities' : '/opportunities')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $name, 'item' => $seo['canonical']],
            ],
        ]];
        return $seo;
    }

    public static function organization(bool $full = true): array
    {
        $brand = config('nextward_public.brand');
        $organization = [
            '@type' => 'Organization', '@id' => self::url('/').'#organization', 'name' => 'Nextward', 'url' => self::url('/'),
            'logo' => ['@type' => 'ImageObject', 'url' => self::url($brand['logo']), 'width' => 1254, 'height' => 1254],
        ];
        if (!$full) return $organization;
        return ['@context' => 'https://schema.org'] + $organization + array_filter([
            'description' => $brand['description'], 'email' => $brand['email'],
            'areaServed' => ['@type' => 'Country', 'name' => 'Tunisia'], 'knowsLanguage' => ['fr', 'en', 'ar'],
            'founder' => self::founder(), 'sameAs' => $brand['same_as'] ?: null,
        ]);
    }

    private static function founder(): array
    {
        $founder = config('nextward_public.brand.founder');
        return array_filter(['@type' => 'Person', 'name' => $founder['name'], 'url' => $founder['url'], 'sameAs' => $founder['same_as'] ?: null]);
    }

    public static function browse(Request $request, ?string $category = null, int $total = 0): array
    {
        $fr = $request->is('fr', 'fr/*');
        $copy = self::copy($fr ? 'fr' : 'en')[$category ?: 'opportunities'];
        $filters = collect($request->query())->except('page')->reject(fn ($value) => $value === '' || $value === null || $value === []);
        $seo = self::base($request, $copy['title'], $copy['description'], $filters->isEmpty() && (!$category || $total > 0));
        if ($request->integer('page', 1) > 1) {
            $seo['title'] .= ' | Page '.$request->integer('page');
        }
        if ($filters->isEmpty()) {
            $seo['alternates'] = self::alternates('/opportunities'.($category ? '/'.$category : ''), $request->integer('page', 1));
        }
        return $seo;
    }

    public static function opportunity(Internship $opportunity): array
    {
        $opportunity->loadMissing('company.user', 'city', 'field');
        $fr = request()->is('fr/*');
        $company = $opportunity->company?->user?->name ?: $opportunity->company?->name;
        $description = Str::limit(trim(html_entity_decode(strip_tags($opportunity->description))), 155);
        $seo = self::base(request(), $opportunity->title.($company ? ($fr ? ' chez ' : ' at ').$company : '').' | Nextward', $description, $opportunity->isOpen());
        $seo['canonical'] = $opportunity->publicUrl($fr ? 'fr' : 'en');
        $seo['alternates'] = self::pairs($opportunity->publicUrl('en'), $opportunity->publicUrl('fr'));
        $seo['type'] = 'article';
        $eligibleCountries = array_values(array_diff(array_filter((array) $opportunity->eligible_countries,
            fn ($country) => is_string($country) && preg_match('/^[A-Z]{2}$/', $country)), (array) $opportunity->excluded_countries));
        $worldwide = $opportunity->remote_eligibility === 'eligible'
            && preg_match('/^(?:remote[ :,-]*)?(?:worldwide|anywhere in the world|global)$/i', trim((string) $opportunity->remote_scope))
            && empty($opportunity->excluded_countries);
        $hasLocation = $opportunity->work_type === 'remote'
            ? ($eligibleCountries || $worldwide)
            : ($opportunity->country_code || collect($opportunity->locations ?: [])->contains(fn ($location) => !empty($location['country_code'])));
        $hasApplication = $opportunity->application_mode === 'external'
            ? filter_var($opportunity->application_url, FILTER_VALIDATE_URL) && in_array(parse_url($opportunity->application_url, PHP_URL_SCHEME), ['http', 'https'], true)
            : $opportunity->company?->user !== null;
        if (!$opportunity->isOpen() || !$opportunity->posted_at || !$company || !$hasLocation || !$hasApplication || !$opportunity->description) {
            return $seo;
        }
        $job = [
            '@context' => 'https://schema.org', '@type' => 'JobPosting', 'title' => $opportunity->title,
            'description' => '<p>'.nl2br(e(trim(strip_tags($opportunity->description)))).'</p>',
            'datePosted' => $opportunity->posted_at->toIso8601String(), 'url' => $seo['canonical'],
            'hiringOrganization' => array_filter(['@type' => 'Organization', 'name' => $company, 'sameAs' => $opportunity->company?->website]),
            'identifier' => ['@type' => 'PropertyValue', 'name' => 'Nextward', 'value' => (string) $opportunity->id],
        ];
        $employmentTypes = array_values(array_intersect((array) $opportunity->employment_types, ['FULL_TIME', 'PART_TIME', 'CONTRACTOR', 'TEMPORARY', 'INTERN', 'VOLUNTEER', 'PER_DIEM', 'OTHER']));
        if ($opportunity->kind === 'internship' && !in_array('INTERN', $employmentTypes, true)) $employmentTypes[] = 'INTERN';
        if ($employmentTypes) $job['employmentType'] = $employmentTypes;
        if ($opportunity->expires_at) $job['validThrough'] = $opportunity->expires_at->toIso8601String();
        elseif ($opportunity->closing_at) $job['validThrough'] = \Carbon\Carbon::parse($opportunity->closing_at->toDateString(), config('importer.timezone', 'Africa/Tunis'))->endOfDay()->toIso8601String();
        if ($opportunity->work_type !== 'remote') {
            $locations = collect($opportunity->locations ?: [])->filter(fn ($location) => !empty($location['country_code']))->map(fn ($location) => [
                '@type' => 'Place', 'address' => array_filter([
                    '@type' => 'PostalAddress',
                    'addressCountry' => $location['country_code'],
                    'addressLocality' => $location['address']['addressLocality'] ?? $location['city'] ?? null,
                    'addressRegion' => $location['address']['addressRegion'] ?? null,
                    'streetAddress' => $location['address']['streetAddress'] ?? null,
                    'postalCode' => $location['address']['postalCode'] ?? null,
                ]),
            ])->values()->all();
            $job['jobLocation'] = $locations ?: ['@type' => 'Place', 'address' => array_filter([
                '@type' => 'PostalAddress', 'addressCountry' => $opportunity->country_code,
                'addressLocality' => $opportunity->city?->name ?: $opportunity->location,
            ])];
        }
        if ($opportunity->work_type === 'remote') {
            $job['jobLocationType'] = 'TELECOMMUTE';
            $countries = $eligibleCountries;
            if ($countries) $job['applicantLocationRequirements'] = array_values(array_map(fn ($country) => ['@type' => 'Country', 'name' => $country], $countries));
        }
        $periods = ['hour' => 'HOUR', 'day' => 'DAY', 'week' => 'WEEK', 'month' => 'MONTH', 'year' => 'YEAR'];
        if ($opportunity->pay_currency && isset($periods[$opportunity->pay_unit]) && ($opportunity->pay_min !== null || $opportunity->pay_max !== null)) {
            $job['baseSalary'] = ['@type' => 'MonetaryAmount', 'currency' => $opportunity->pay_currency, 'value' => array_filter([
                '@type' => 'QuantitativeValue', 'minValue' => $opportunity->pay_min === null ? null : (float) $opportunity->pay_min,
                'maxValue' => $opportunity->pay_max === null ? null : (float) $opportunity->pay_max,
                'unitText' => $periods[$opportunity->pay_unit],
            ], fn ($value) => $value !== null)];
        }
        $seo['structured_data'] = [$job, [
            '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => $fr ? 'Opportunités' : 'Opportunities', 'item' => self::url($fr ? '/fr/opportunities' : '/opportunities')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $opportunity->title, 'item' => $seo['canonical']],
            ],
        ]];
        return $seo;
    }

    private static function base(Request $request, string $title, string $description, bool $index): array
    {
        $query = collect($request->query())->reject(fn ($value, $key) => str_starts_with($key, 'utm_') || in_array($key, ['gclid', 'fbclid'], true) || $value === '' || $value === null || $value === [] || ($key === 'page' && (int) $value === 1))->sortKeys()->all();
        return [
            'title' => $title, 'description' => $description,
            'canonical' => rtrim(config('app.url'), '/').'/'.ltrim($request->path(), '/').($query ? '?'.http_build_query($query) : ''),
            'robots' => $index ? 'index, follow, max-image-preview:large' : 'noindex, follow',
            'image' => self::url(config('nextward_public.brand.og_image')), 'image_alt' => $request->is('fr', 'fr/*') ? 'Nextward : stages et emplois en Tunisie et à l’international' : 'Nextward: internships and jobs in Tunisia and worldwide',
            'locale' => $request->is('fr', 'fr/*') ? 'fr_FR' : 'en_US',
            'structured_data' => [], 'alternates' => [],
        ];
    }

    private static function alternates(string $path, int $page = 1): array
    {
        $suffix = $page > 1 ? '?page='.$page : '';
        return [
            ['lang' => 'en', 'url' => self::url($path).$suffix],
            ['lang' => 'fr', 'url' => self::url('/fr'.($path === '/' ? '' : $path)).$suffix],
            ['lang' => 'x-default', 'url' => self::url($path).$suffix],
        ];
    }

    private static function pairs(string $en, string $fr): array
    {
        return [['lang' => 'en', 'url' => $en], ['lang' => 'fr', 'url' => $fr], ['lang' => 'x-default', 'url' => $en]];
    }

    private static function url(string $path): string
    {
        return rtrim(config('app.url'), '/').'/'.ltrim($path, '/');
    }

    public static function copy(string $locale = 'en'): array
    {
        return config('nextward_public.'.$locale);
    }
}
