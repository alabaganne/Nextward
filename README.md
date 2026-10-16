# Nextward

Nextward helps students and professionals find internships and jobs in Tunisia and worldwide. The public catalogue combines employer posts with opportunities imported from approved sources. Students can look for internships, PFE projects and graduate roles; professionals can explore jobs by experience, location and work arrangement.

Candidates can save opportunities, set interests, receive optional search digests, apply to employers on Nextward, and keep their own record of applications made elsewhere. An external application opens the original application page. Clicking it does not claim that an application was submitted; candidates confirm and update their tracking themselves. Employers manage their own posts and internal applications. Imported company profiles do not imply a verified employer account.

## Stack

- Laravel 13, PHP 8.3+, Inertia.js 3 and Vue 3
- Tailwind CSS 4 and Vite 8
- Node.js 24, as specified in `.nvmrc`, for asset builds and the SSR service
- MySQL or PostgreSQL for application data; SQLite for isolated checks
- Database queues for imports, digests and indexing notifications

PHP needs the appropriate PDO driver, cURL, DOM/XML, Mbstring, OpenSSL and Tokenizer. SQLite support is needed for the existing test configuration. Public pages are server rendered through Inertia; private account pages are excluded from SSR and search indexing. Existing database names such as `internships`, `students` and `likes` remain for compatibility.

## Local setup

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Set `APP_URL` and `DB_*` for a local database, then run:

```bash
php artisan migrate
php artisan db:seed --class=CitySeeder
php artisan db:seed --class=FieldSeeder
php artisan storage:link
```

The city and field catalogues support optional profile choices and filters. Worldwide profiles can use their country and location without a Tunisian city. `storage:link` exposes public company/profile media, not new application attachments.

Pages only load company logos from Nextward. A logo from another site (`companies.logo_url`) is downloaded once by `php artisan companies:logos`, which the scheduler runs every hour: PNG, JPEG, GIF or WebP up to 1 MB, from public hosts only, saved under `company-logos/imported` on the public disk. Until the copy exists, or when the download fails, pages show the initials badge. Failed downloads are tried again after a week. An uploaded logo always wins.

Run the web server and Vite in separate terminals:

```bash
php artisan serve
npm run dev
```

The usual local URL is `http://127.0.0.1:8000`. To inspect complete server-rendered HTML, build both bundles and run the SSR service as described below. The example configuration uses database queues, so queued work also needs a worker. Digests and Google indexing remain disabled by default.

## Real opportunity seed

`php artisan db:seed` now adds six real, sourced opportunities plus their employer and reference records, and the daily import sources. It does not truncate tables or create accounts, applications, messages or other demo activity. Repeat runs preserve existing listings and moderation decisions. `InternshipSeeder` delegates to this same catalogue.

The snapshot was checked on 15 October 2026 and contains four internships and two jobs, all posted in the last 30 days: three listings in Tunisia, two international internships and one worldwide remote job. Each listing links to the original employer page. See [the source list and refresh notes](database/seeders/data/SOURCES.md).

These hand-checked listings stay public for 14 days after the check. An older snapshot is seeded as drafts, and rerunning the seed does not pretend to reverify it. Recheck the listings before a later deployment.

## Daily import

`ImportSourceSeeder` adds the sources the scheduler reads every day at 05:00 Tunis time: Tanitjobs and Keejob for Tunisia, and employer job boards on Greenhouse, Lever and Ashby for worldwide roles. Only listings posted in the last 30 days are added (`OPPORTUNITY_MAX_AGE_DAYS`). Imported listings posted more than 60 days ago are closed (`OPPORTUNITY_RETENTION_DAYS`). Worldwide boards keep internships, plus jobs that are in Tunisia, remote without a country limit, or offer relocation or visa help. The same role found on both Tanitjobs and Keejob is shown once. Check each site's terms of use before a public launch; sources can be paused in Imports.

Use a clean production database. The seed preserves existing data, including any demo data already present. The old complete demo dataset lives in `tests/Fixtures/DemoSeeder.php` for explicit workflow tests. Never deploy a demo database or run the standalone demo account/activity seeders in production.

## Public pages and search visibility

`/opportunities` is the main catalogue, with job/internship filters and Tunisia/worldwide coverage. `/for-students` and `/for-professionals` introduce the two audiences. Opportunity details have stable IDs and readable slugs. Legacy internship URLs redirect to their canonical opportunity URLs.

Public HTML includes the listing text, metadata and canonical links. Eligible detail pages include `JobPosting` structured data based on visible, supported facts. Missing application or location evidence can prevent rich-result markup without making an otherwise useful detail page private. Closed, suppressed, stale or duplicate listings have separate availability and indexing behavior. Search and arbitrary filter combinations are kept out of the index; curated landing pages provide crawlable entry points.

Verify the production domain in Search Console, submit `/sitemap.xml`, inspect representative detail URLs and monitor indexing, structured-data errors and crawl coverage. Check initial HTML with JavaScript disabled or a plain HTTP client after every SSR deployment. A running SSR process alone does not prove that listing text was rendered.

## Production deployment and SSR

Start with [DEPLOY.md](DEPLOY.md) for installation, updates, launch checks and rollback. See [VPS_DEPLOYMENT.md](VPS_DEPLOYMENT.md) for the complete Nginx, PHP-FPM, Supervisor and cron configuration. These guides describe a future deployment; they do not confirm an existing VPS configuration.

1. Back up the database and both public and private storage. Rehearse the upgrade on a restored copy, including nullable profile fields and existing application attachments.
2. Configure the real HTTPS `APP_URL`, production database, `APP_ENV=production`, `APP_DEBUG=false`, mail settings and writable storage. Keep the existing `APP_KEY` when upgrading. Do not run demo seeders or `migrate:fresh`.
3. Install locked dependencies and build the client and SSR bundles with Node.js 24:

   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci
   npm run build
   php artisan migrate --force
   php artisan storage:link
   php artisan config:cache
   php artisan view:cache
   ```

4. Supervise the SSR process using Node.js 24 on the service user's `PATH`:

   ```bash
   php artisan inertia:start-ssr
   ```

   `npm run ssr` directly runs the same built entry at `bootstrap/ssr/ssr.js`; use one launcher, not both. Restart the supervised SSR process after each new bundle, then check it:

   ```bash
   php artisan inertia:check-ssr
   ```

5. Run the queue worker and scheduler below. Restart workers after deployments with `php artisan queue:restart`, then check public HTML, authenticated flows, source health and failed jobs.

The installed Inertia configuration reads `INERTIA_SSR_ENABLED` (default `true`), `INERTIA_SSR_RUNTIME` (`node`), `INERTIA_SSR_URL` (`http://127.0.0.1:13714`), `INERTIA_SSR_ENSURE_RUNTIME_EXISTS` (`false`), `INERTIA_SSR_ENSURE_BUNDLE_EXISTS` (`true`) and `INERTIA_SSR_THROW_ON_ERROR` (`false`). It also supports optional `INERTIA_SSR_TIMEOUT` and `INERTIA_SSR_HOT_URL`. These variables do not start the service. The application binds SSR to loopback; keep its port private. Inertia normally falls back to browser rendering after an SSR failure, so monitor rendered content and SSR errors. Throwing on SSR errors is useful in isolated end-to-end verification.

The new migrations extend existing records. A code rollback alone is insufficient after a schema or attachment migration. Restore a tested database/storage backup when necessary, or roll migrations back in their normal reverse order after reviewing data loss. Do not manually roll back an older migration while newer dependent migrations remain applied.

## Private application attachments

New CVs and supporting documents use private storage and authorized, uncached download routes. Existing public attachment files need a separate migration. After backing up database and storage, first inspect the dry run:

```bash
php artisan applications:privatize-attachments
```

During a maintenance window that prevents concurrent attachment changes, execute the reviewed migration:

```bash
php artisan applications:privatize-attachments --execute
```

It copies each recognized legacy file into private application storage and a private backup, verifies content hashes, preserves original filenames, updates database references, and removes the public file only after all references have migrated. Review failures and verify authorized downloads before reopening traffic. Keep `storage/app/private` and its backups outside public web access. Old public URLs can remain reachable until migration succeeds, and previously cached copies may require a separate CDN purge. This command does not delete historical third-party caches.

## Queue worker, scheduler and health

Use `QUEUE_CONNECTION=database` and `DB_QUEUE_RETRY_AFTER=360`. The migration creates the `jobs` table when absent. Supervise a worker with:

```bash
php artisan queue:work database --queue=default --sleep=3 --timeout=300 --tries=3
```

Allow at least 360 seconds for supervisor shutdown. The 360-second retry interval must remain longer than the 300-second import job timeout to avoid duplicate processing. Jobs also define their own retry behavior. Inspect failures with `php artisan queue:failed`; investigate delivery state before retrying mail jobs.

Install one scheduler entry, adjusting paths to the deployment:

```cron
* * * * * /usr/bin/php /srv/nextward/current/artisan schedule:run >> /srv/nextward/shared/storage/logs/scheduler.log 2>&1
```

The scheduler checks due and resumable import work every five minutes. Sources with a 24-hour cadence use 05:00 Africa/Tunis for their next full run; other source cadences, retries and saved progress determine when work is due. It reconciles deadlines and stale records every fifteen minutes, and dispatches digests and indexing hourly. Scheduled work still needs a queue worker.

Queue uniqueness, source locks, scheduler locks and indexing quotas depend on a consistent cache. The example file cache works for processes sharing one host and storage. Multiple hosts must use a shared supported cache with atomic locks and the same namespace. If choosing the database cache driver, provision its cache and lock tables first; this repository does not create them. Do not enable several independent schedulers with isolated caches.

Run `php artisan opportunities:health` from an external monitor. Add `--public` to inspect actual homepage and browse HTML as well as the SSR process. The command checks the scheduler heartbeat, queue age and failures, source freshness, digest/indexing issues and SSR health. Exit code 1 means attention is needed. Monitor Laravel logs for SSR rendering failures, since a browser can still appear functional after an SSR fallback.

## Approved sources and import operation

Admins manage sources, run history, staged records, reports, duplicate merges and company claims at `/admin/imports`. Supported adapters are sitemap/JSON-LD, Greenhouse, Lever and Ashby. There are no automatically approved third-party boards or guaranteed live-source coverage.

Record the source URL, exact allowed domains, provider, permission evidence, cadence and request limits. A source must have approved permission and be enabled before fetching. Public access or robots permission alone does not establish republication rights. Start with review mode and inspect attribution, employer identity, application links, dates, location, remote eligibility and description quality. Enable automatic publication only after the source is approved and its output is consistently correct. Invalid or incomplete items can still require review.

The importer respects robots rules, domain boundaries, request limits and bounded runs. It stores progress, detects duplicates, rechecks known records and retains source provenance. Freshness uses the greater of 48 hours and two source refresh cycles. Three consecutive failed runs pause a source for review, and rate-limit retries respect the source's Retry-After value. Removed permission or stale/closed evidence can suppress publication. Admin corrections are preserved separately from later source updates. Company claims require reviewed evidence and do not convert external applications into Nextward submissions.

```bash
php artisan opportunities:import --source=approved-source-key --limit=10 --dry-run
php artisan opportunities:import --source=approved-source-key --limit=40 --sync
php artisan opportunities:import --due
php artisan opportunities:import --source=approved-source-key --recheck
php artisan opportunities:reconcile
```

| Import option | Behavior |
| --- | --- |
| `--source=KEY` | Limit to a source key; repeat for several sources. |
| `--limit=40` | Bound a run to 1 through 100 items; default 40. |
| `--dry-run` | Fetch and inspect without persisting import changes; still makes network requests. |
| `--sync` | Process a bounded batch in the current command instead of dispatching a job. |
| `--recheck` | Reverify existing source items. |
| `--due` | Select sources whose next run is due. |

Without `--sync` or `--dry-run`, eligible sources are queued. No approved, enabled sources means no import work. Review source errors and staged records before increasing coverage or request volume.

## Saved searches and email digests

Candidates can save searches without receiving email. Daily or weekly digests require explicit subscription, a timezone and a configured mail provider. `OPPORTUNITY_DIGESTS_ENABLED=false` is the default. Enable it only after testing delivery with controlled accounts. Queued jobs recheck consent and current opportunity availability, and delivery records prevent repeatedly sending the same opportunity to a candidate. Emails include unsubscribe links.

```bash
php artisan opportunities:digests --dry-run
```

This counts due candidate subscriptions without queueing or sending, including while digests are disabled. The scheduler calls `opportunities:digests` hourly; sending remains disabled until the feature switch is enabled.

Interrupted or ambiguous mail sends are marked uncertain rather than automatically resent. Check the mail provider's delivery records, then inspect and resolve the batch using exactly one confirmed outcome:

```bash
php artisan opportunities:review-digest BATCH_UUID
php artisan opportunities:review-digest BATCH_UUID --mark-sent
php artisan opportunities:review-digest BATCH_UUID --retry-confirmed-unsent
```

`--mark-sent` records confirmed delivery without sending. `--retry-confirmed-unsent` makes uncertain items eligible for a later job, which checks consent again. Do not use the retry option merely because a response was lost.

## Optional Google Indexing API

Ordinary crawling and sitemaps work without this integration. To enable it, configure a Google Cloud project and service account, enable the Indexing API, verify the site in Search Console, and add the service account as a site owner according to [Google's prerequisites](https://developers.google.com/search/apis/indexing-api/v3/prereqs).

Store the JSON credentials in a protected server file outside `public` and `storage/app/public`. Set `GOOGLE_INDEXING_CREDENTIALS` to that absolute path and enable `GOOGLE_INDEXING_ENABLED` only after setup. Never place credentials in frontend variables or commit them. The application sends eligible `JobPosting` detail updates and subsequent removals; it does not submit catalogue or filter pages.

```bash
php artisan opportunities:indexing --all --limit=100
php artisan opportunities:indexing --limit=100
```

`--all` enqueues the initial eligible detail inventory. The normal hourly command handles pending changes and removals. There is no indexing dry-run flag. While disabled, the outbox is retained and no API calls are made. `GOOGLE_INDEXING_DAILY_LIMIT=200` is the application's default daily cap; keep it within the project's actual [Google quota and approval](https://developers.google.com/search/apis/indexing-api/v3/quota-pricing). `--limit` bounds dispatches for one invocation, not the daily quota. Monitor failed/pending `indexing_notifications` records and queue failures. API acknowledgement does not guarantee crawling, rich results or indexing.

## Messages and verification

Internal applications retain employer messaging and application stages. External tracking is private to the candidate and does not notify an imported employer. Messages and notifications persist, but live browser updates remain disabled in `resources/js/bootstrap.js` and page listeners. Pusher variables alone do not enable them.

```bash
composer validate --strict
npm run build
php artisan test
```

The PHPUnit configuration uses in-memory SQLite. Tests cover canonical redirects, sitemap routes, worldwide profiles and the database-backed homepage. Historical migration checks roll back later dependent migrations before testing the older application-status upgrade. Run the full suite against the exact release before deployment.

Before release, verify both compiled bundles, the real SSR initial HTML, native applications, external tracking, attachment permissions, approved-source fixtures, source failure handling, consent/unsubscribe and indexing requests with fake providers. Rehearse database upgrades on a restored production-like copy. Unit or fixture checks do not establish rights to a source or confirm production mail, crawling or Google indexing.

## Project notes

- `OPPORTUNITY_FINDER_IMPLEMENTATION_PLAN.md`: product scope and implementation plan
- `FUTURE_PLANS.md`: rollout work and later improvements
- `LANDING_PAGE_DESIGN.md`: public visual direction
- `public/nextward-assets/README.md`: brand media assets
- `design/DESIGN.md` and `design/INTEGRATION.md`: workspace design reference; current shared tokens are in `resources/css/app.css`

Licensed under the [MIT license](https://opensource.org/licenses/MIT).
