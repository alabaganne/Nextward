<?php

namespace App\Support;

use App\Models\Internship;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicSeo
{
    public static function isPublic(Request $request): bool
    {
        return $request->routeIs('home', 'about', 'contact', 'blog.*', 'audience.*', 'opportunities.index', 'opportunities.category', 'opportunities.show', 'fr.*');
    }

    public static function forRequest(Request $request): array
    {
        $fr = $request->is('fr', 'fr/*');
        $path = '/'.ltrim(preg_replace('#^fr/?#', '', $request->path()), '/');
        $texts = self::copy($fr ? 'fr' : 'en');
        $key = match ($path) {
            '/' => 'home', '/for-students' => 'students', '/for-professionals' => 'professionals',
            '/about' => 'about', '/contact' => 'contact', '/blog' => 'blog', default => null,
        };
        $copy = $texts[$key] ?? ['title' => 'Nextward', 'description' => ''];
        $seo = self::base($request, $copy['title'], $copy['description'], self::isPublic($request));
        if (in_array($key, ['home', 'students', 'professionals'], true)) {
            $seo['alternates'] = self::alternates($path);
        }
        if ($key === 'home') {
            $seo['structured_data'] = [[
                '@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'Nextward', 'url' => self::url('/'),
                'potentialAction' => ['@type' => 'SearchAction', 'target' => self::url('/opportunities').'?search={search_term_string}', 'query-input' => 'required name=search_term_string'],
            ]];
        }
        if ($request->routeIs('blog.show')) {
            $post = collect(config('nextward_blog'))->firstWhere('slug', $request->route('slug'));
            if ($post) {
                $seo['title'] = $post['title'].' | Nextward';
                $seo['description'] = $post['description'];
                $seo['type'] = 'article';
                $seo['structured_data'] = [[
                    '@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $post['title'],
                    'description' => $post['description'], 'datePublished' => $post['published_at'],
                    'dateModified' => $post['published_at'], 'mainEntityOfPage' => $seo['canonical'],
                    'author' => ['@type' => 'Person', 'name' => 'Ala Baganne', 'url' => 'https://alabaganne.com/'],
                    'publisher' => ['@type' => 'Organization', 'name' => 'Nextward'],
                ]];
            }
        }
        return $seo;
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
        $company = $opportunity->company?->user?->name ?: $opportunity->company?->name;
        $description = Str::limit(trim(html_entity_decode(strip_tags($opportunity->description))), 155);
        $seo = self::base(request(), $opportunity->title.($company ? ' at '.$company : '').' | Nextward', $description, $opportunity->isOpen());
        $seo['canonical'] = $opportunity->public_url;
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
            'datePosted' => $opportunity->posted_at->toIso8601String(), 'url' => $opportunity->public_url,
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
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Opportunities', 'item' => self::url('/opportunities')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $opportunity->title, 'item' => $opportunity->public_url],
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

    private static function url(string $path): string
    {
        return rtrim(config('app.url'), '/').'/'.ltrim($path, '/');
    }

    public static function copy(string $locale = 'en'): array
    {
        return config('nextward_public.'.$locale);
    }
}
