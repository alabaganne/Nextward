# Nextward

Nextward helps students and professionals everywhere find internships and jobs around the world. The public catalogue combines employer posts with opportunities imported from approved sources. Students can look for internships, PFE projects and graduate roles; professionals can explore jobs by experience, location and work arrangement.

Candidates can save opportunities, set interests, receive optional search digests, apply to employers on Nextward, and keep their own record of applications made elsewhere. An external application opens the original application page. Clicking it does not claim that an application was submitted; candidates confirm and update their tracking themselves. Employers manage their own posts and internal applications. Imported company profiles do not imply a verified employer account.

## Stack

- Laravel 13, PHP 8.3+, Inertia.js 3 and Vue 3
- Tailwind CSS 4 and Vite 8
- Node.js 24, as specified in `.nvmrc`, for asset builds and the SSR service
- MySQL or PostgreSQL for application data; SQLite for isolated checks
- Database queues for imports, digests and indexing notifications
- Headless Chrome, through `spatie/browsershot` and Puppeteer, to print resumes and cover letters as PDF

PHP needs the appropriate PDO driver, cURL, DOM/XML, Mbstring, OpenSSL and Tokenizer. SQLite support is needed for the existing test configuration. Public pages are server rendered through Inertia; private account pages are excluded from SSR and search indexing. Listings live in the `opportunities` table and candidates in `candidates` (renamed from `internships` and `students` in October 2026).

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
php artisan db:seed --class=SkillSeeder
php artisan storage:link
```

The city and field catalogues support optional profile choices and filters. `SkillSeeder` fills the skills catalogue from the skills listings are tagged with (`skills` in `config/importer.php`); `php artisan db:seed` runs it too. Worldwide profiles can use their country and location without a Tunisian city. `storage:link` exposes public company/profile media, not new application attachments.

Pages only load company logos from Nextward. A logo from another site (`companies.logo_url`) is downloaded once by `php artisan companies:logos`, which the scheduler runs every hour: PNG, JPEG, GIF or WebP up to 1 MB, from public hosts only, saved under `company-logos/imported` on the public disk. Until the copy exists, or when the download fails, pages show the initials badge. Failed downloads are tried again after a week. An uploaded logo always wins.

Run the web server and Vite in separate terminals:

```bash
php artisan serve
npm run dev
```

Resume and letter PDFs are printed by headless Chrome. `/usr/bin/google-chrome`, `/usr/bin/chromium` and `/usr/bin/chromium-browser` are found on their own; otherwise set `CV_CHROME_PATH`, or run `npx puppeteer browsers install chrome` once so Puppeteer uses its own copy. On a server that runs PHP as root or in a container, set `CV_CHROME_NO_SANDBOX=true`. Tests never start Chrome (`CV_PDF_FAKE=true` in `phpunit.xml`).

The usual local URL is `http://127.0.0.1:8000`. To inspect complete server-rendered HTML, build both bundles and run the SSR service as described below. The example configuration uses database queues, so queued work also needs a worker. Digests and Google indexing remain disabled by default.

## Real opportunity seed

`php artisan db:seed` now adds six real, sourced opportunities plus their employer and reference records, and the daily import sources. It does not truncate tables or create accounts, applications, messages or other demo activity. Repeat runs preserve existing listings and moderation decisions. `InternshipSeeder` delegates to this same catalogue.

The snapshot was checked on 15 October 2026 and contains four internships and two jobs, all posted in the last 30 days: three listings in Tunisia, two international internships and one worldwide remote job. Each listing links to the original employer page. See [the source list and refresh notes](database/seeders/data/SOURCES.md).

These hand-checked listings stay public for 14 days after the check. An older snapshot is seeded as drafts, and rerunning the seed does not pretend to reverify it. Recheck the listings before a later deployment.

## Daily import

`ImportSourceSeeder` adds the sources the scheduler reads every day at 05:00 Tunis time: Tanitjobs and Keejob for Tunisia, and employer job boards on Greenhouse, Lever and Ashby for worldwide roles. Only listings posted in the last 30 days are added (`OPPORTUNITY_MAX_AGE_DAYS`). Imported listings posted more than 60 days ago are closed (`OPPORTUNITY_RETENTION_DAYS`). Worldwide boards keep internships, plus jobs that are in Tunisia, remote without a country limit, or offer relocation or visa help. The same role found on both Tanitjobs and Keejob is shown once. Check each site's terms of use before a public launch; sources can be paused in Imports.

Nextward is for IT roles, so every import (scheduled runs, Run import in admin, `opportunities:import` and `opportunities:fetch`) keeps only IT listings: software, web and mobile development, data and AI, DevOps and cloud, security, networks and systems, IT support, QA, UI/UX, embedded and BI. A board title with no IT word is rejected before the page is read; after reading, a listing the AI (or the keyword rules) does not place in an IT field is rejected too. Both show as "Outside IT scope." in the review queue, and an admin can still approve one by hand. The title words and fields live under `focus.tech` in `config/importer.php`; `OPPORTUNITY_SCOPE=` (empty) turns the rule off.

To add new listings right away, without waiting for the schedule, run either of these on your machine or on the server:

```bash
composer scrape
npm run scrape
```

It reads the sources now and stops after adding 10 IT opportunities that were never added before: about half from Tunisian boards and half from worldwide boards. The title keywords live under `focus.tech` in `config/importer.php`; add words for one run with `--keywords=flutter,react`. Listings outside IT are never added, with or without `--focus`. Listings already in the database, listings found earlier on another board, listings removed by an admin, and listings that are too old or not open to Tunisia are skipped. Pass a different number with `composer scrape -- --count=20` or `npm run scrape -- --count=20`, or call `php artisan opportunities:fetch --focus=tech --count=20` directly. IT listings that don't match the focus are left for the daily import. On a server, run it as the user that owns the app (for example `sudo -u www-data composer scrape`) so cache and log files keep the right owner.

### AI

AI reads new listings before they are published, 10 per call (`OPPORTUNITY_AI_BATCH`), so a run costs a few calls instead of one per listing. It gets our fields, the skills catalogue and the filter values, and sets the field, internship or job, seniority, work mode, contract types, programme (PFE, summer, apprenticeship, graduate), up to 15 skills from the catalogue, who can apply (`applicant_scope`: anywhere, listed countries, local work rights only, or unknown), eligible and excluded countries, relocation and visa. It also judges quality: scams, pay-to-apply, commission-only, empty and spam listings are rejected as "Low quality: …" and wait in Imports, where an admin can still publish them. It runs on OpenAI or Claude through the `laravel/ai` package; pick one with `OPPORTUNITY_AI_PROVIDER=openai` (default) or `anthropic` and set its key (`OPENAI_API_KEY` or `ANTHROPIC_API_KEY`). Models are set per provider under `ai.models` in `config/importer.php` (`OPPORTUNITY_OPENAI_MODEL`, default `gpt-5.4-mini`, and `OPPORTUNITY_OPENAI_DISCOVERY_MODEL`, default `gpt-5.5`; for Claude, `OPPORTUNITY_AI_REVIEW_MODEL`, default `claude-sonnet-5-5`, reads and translates listings and `OPPORTUNITY_AI_MODEL`, default `claude-opus-5-5`, finds sources and writes letters). The answer is kept with the listing, so daily refreshes do not call it again. Only listings the AI places in an IT field, as an IT role, are added; the others are rejected as outside the IT scope. Listings that were online before this review can be read again with `php artisan opportunities:review --limit=200` (open imported listings with no "who can apply" yet, newest first; `--all` reads every open one). It is never scheduled, so you choose when to spend on it; it lists the listings it judges low quality but leaves them online. If the key is missing, the account has no credit, or a call fails, the keyword rules are used instead and the run carries on. Set `OPPORTUNITY_AI_ENABLED=false` to turn it off.

Every published listing also gets an English and a French version; the original stays untouched and shows in a fold-out on the translated page. Employer posts are translated by a queued job as soon as they are published or edited. Imported listings are translated by `php artisan opportunities:translate` (every 10 minutes, 200 listings a run, newest first), which packs several listings into one AI call (`review` model) up to about 12,000 characters or 5 listings (`OPPORTUNITY_AI_TRANSLATE_BATCH_CHARS`), and retries failures. Use `--sync --limit=20` to run it now, and `OPPORTUNITY_AI_TRANSLATE=false` to turn it off. After a first deploy with many listings, run it a few times with a bigger `--limit` to catch up faster.

Candidates (admins included) keep up to 10 resumes on their profile page (`/profile#resumes`): PDFs they upload, resumes made from their profile, and AI translations of either. One of them is the **main** resume: it is shared on the public profile, picked first when applying, read to fill the profile and given to the AI for kits. Deleting the main one makes the newest other ready resume the main one. A resume made from the profile is laid out with one of six themes (Ravenna, Halden, Marlow, Sören, Atlas, Verona; `app/Support/CvTemplates.php`), with an accent colour, sans or serif type and A4 or US Letter paper, edited with a live preview at `/resumes/{id}` and printed as PDF by headless Chrome (`app/Support/CvPdf.php`, `resources/views/cv`, `resources/css/cv.css`, fonts in `public/fonts/cv`). Making one, changing its theme, refreshing it from the profile and downloading cost nothing and call no AI.

CVs, resumes and letters in Arabic are laid out right to left in every theme (the page gets `dir="rtl"` and the theme rules use logical CSS properties), in the preview, the PDF and the Word file. Arabic text uses IBM Plex Sans Arabic (sans) and Noto Naskh Arabic (serif), self-hosted in `public/fonts/cv` under the SIL Open Font License (`OFL.txt`) and loaded only for Arabic CVs. Emails, phone numbers and links stay left to right inside Arabic text. Word files use Arial for Arabic, since few people have Plex installed. In Sören, the side column's tint is a fixed block in print, so it runs down every page.

A ready resume can be translated into English or French (`review` model, queued `TranslateResume` job). An uploaded PDF is sent as a file; a resume made on Nextward is sent as its text. The translation comes back as a draft the candidate checks and saves. Asking again for the same language opens the same draft.

An uploaded PDF can't be edited as it is, so **Make editable** asks the same model to copy it into the CV fields word for word, in its own language (source `copy`). It comes back as a draft too, then works like a resume made on Nextward: themes, live preview, PDF and Word. Translations and copies share one limit: each candidate can start 10 an hour.

When the main resume is an uploaded PDF, Settings > Resume shows **Fill my profile from this resume**. The AI (`review` model) reads the PDF and suggests a headline, bio, location, links, languages, skills, experience, education and projects. Settings shows each suggestion next to the current value; the candidate ticks what to use, checks the form and presses Save. Nothing is saved before that. Each candidate can read their resume 5 times an hour.

When applying on Nextward, the candidate picks the resume to send: one of their ready resumes, a tailored CV made for this listing (printed when they apply), or none. The main resume is picked by default, or the tailored CV when they come from its kit page. A copy is stored with the application as its first file, so deleting the resume later does not take it from the company. The resume counts toward the 10 files.

On any open opportunity page, a candidate can press **Write my cover letter** and pick a language: the listing's own language first (when it is one a CV can be written in: English, French, Arabic, German, Spanish, Italian, Portuguese or Dutch), then English and French. A queued job (`letter` model, `OPPORTUNITY_OPENAI_LETTER_MODEL`, default `gpt-5.5`) writes a cover letter and a CV from their profile, experience, education, projects and resume PDF, using only facts found there. The letter shows on the listing page itself: the candidate edits it there (edits save on their own), copies it, downloads it as a Word file (built in the browser with the `docx` package), uses it to apply on Nextward, or asks for a new draft. The full result is a "kit" under **CV & letters** (`/kits`), where the CV can be edited with the same themes and live preview as resumes, and both download as PDF (headless Chrome, on the server) or Word. The original listing text goes into the prompt, so a kit for a German listing is written from the German text. A closed listing can't get a new draft. Each candidate can start 10 kits or rewrites an hour.

### Credits

The AI tools use credits; everything else is free. Credits are on by default (`BILLING_ENABLED=true`); set it to `false` to make the AI tools free again.
- One credit covers one AI task: a kit's first draft plus `BILLING_REWRITES_PER_KIT` rewrites (default 3), a resume translation or editable copy with the same number of rewrites, or one resume read. A failed draft or read gives its credit back. Admins never use credits.
- Every candidate gets `BILLING_SIGNUP_CREDITS` (default 20) when the account is made; existing candidates were topped up to 20 by a migration. `php artisan credits:monthly` adds `BILLING_MONTHLY_CREDITS` (default 5) to every candidate with a verified email, once a month; the scheduler runs it on the 1st at 06:00 Africa/Tunis. The AI tools need a verified email.
- Packs (20, 60 and 150 credits) are in `config/billing.php`. Set their prices in millimes (TND) and cents (USD) with the `BILLING_*_TND` / `BILLING_*_USD` variables; packs with no price are hidden, so until prices are set candidates only have their free credits.
- Payment providers: **Konnect** and **Flouci** for Tunisian cards and wallets (TND), and **Lemon Squeezy** for international cards and PayPal (USD, one product variant per pack). Each only shows once its keys are set. Every payment is checked again with the provider (Konnect and Flouci through their API, Lemon Squeezy through the signed webhook) and credits are added once per payment. Webhooks go to `/webhooks/billing/{konnect|flouci|lemonsqueezy}`.
- With billing on, candidates see a **Credits** page (`/billing`) with their balance, packs and history. Add or remove credits by hand with `php artisan credits:grant email@example.com 5 --note="Bank transfer"`.

`composer discover` (or `php artisan opportunities:discover`) asks the AI to search the web for new places to read developer jobs: employer boards on Greenhouse, Lever or Ashby, and Tunisian sites with job sitemaps. Each suggestion is checked against the board itself and saved switched off, waiting for approval in Imports. The scheduler also runs it every Monday at 06:00 Tunis time.

### Moving opportunities to another server with a CSV file

`composer scrape` also adds every new listing to `storage/app/opportunities/found.csv` (`OPPORTUNITY_CSV_PATH`; skip with `--no-csv`). `php artisan opportunities:export-csv` adds the open imported listings already in the database. Copy the file to the server and run:

```bash
php artisan opportunities:import-csv storage/app/opportunities/found.csv
```

It needs the same sources there (`php artisan db:seed --class=ImportSourceSeeder`). Listings already on the server, listings removed there earlier, and listings checked more than 48 hours before the import are skipped, so run the scraper again for a fresh file when needed. Running it twice adds nothing new. Once imported, the server's scheduler re-checks them like any other listing; without it they move to draft 48 hours after their last check.

Use a clean production database. The seed preserves existing data, including any demo data already present. The old complete demo dataset lives in `tests/Fixtures/DemoSeeder.php` for explicit workflow tests. Never deploy a demo database or run the standalone demo account/activity seeders in production.

## Public pages and search visibility

`/opportunities` is the main catalogue: All / Internships / Jobs tabs and one row of filters (field, type, country, work mode, experience, date, Open worldwide). The old `/opportunities/tunisia`, `/remote-from-tunisia` and `/relocation` pages redirect to the matching filters with a 301. The home page speaks to everyone; the old `/for-students` and `/for-professionals` pages redirect to it. Opportunity details have stable IDs and readable slugs. Legacy internship URLs redirect to their canonical opportunity URLs.

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

Admins work in the Imports group of the sidebar: Sources (`/admin/imports/sources`, with add and edit pages), Review queue (`/admin/imports/review`), Reports, Employer claims, and Runs & health. Correcting, hiding, closing or merging a listing happens on its own page (`/admin/imports/opportunities/{id}`), opened from the review queue or a report. Supported adapters are sitemap/JSON-LD, Greenhouse, Lever and Ashby. There are no automatically approved third-party boards or guaranteed live-source coverage.

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

### Deleting listings

`php artisan opportunities:purge` deletes imported listings for good, with everything tied to them: applications and their files, saves, views, alert deliveries, reports, translations, kits, skills, source records and the notifications about those applications. Imported companies left with no listings and no account go too (`--keep-companies` keeps them). Kits' credit history is kept. Pages already sent to Google get a "deleted" notice through the indexing reconcile.

```bash
php artisan opportunities:purge --dry-run          # count what would go
php artisan opportunities:purge                    # asks before deleting
php artisan opportunities:purge --source=tanitjobs # only one source
php artisan opportunities:purge --origin=all       # company posts too
```

Without `--remember` the source records are deleted, so the scraper may add the same roles again later. With it, they stay and those roles are never imported again. Back up the database before running it on production.

## Email

Mail goes through [Resend](https://resend.com). In `.env`:

```bash
RESEND_API_KEY=re_...
MAIL_FROM_ADDRESS=nextward@alabaganne.com
MAIL_CONTACT_ADDRESS=you@example.com
```

Leave `MAIL_MAILER` empty: it uses Resend when `RESEND_API_KEY` is set and writes mail to `storage/logs/laravel.log` otherwise, which is handy locally. The sender domain must be verified in Resend (`alabaganne.com` is today; switch `MAIL_FROM_ADDRESS` when a Nextward domain is added).

Every email uses the same Nextward layout: `resources/views/vendor/mail` (theme in `html/themes/nextward.css`) and the templates in `resources/views/mail` and `resources/views/emails`. Text people type (messages, contact form, names) is shown as plain text, so it can't add links or formatting.

| Email | Sent when |
| --- | --- |
| Confirm your email | Someone signs up, or their email changes |
| Confirm your new email | Someone with a verified email asks to change it; goes to the old address |
| Reset your password | Someone asks on the sign-in page |
| Your account is ready | An admin creates an account; the link lets the person choose a password |
| New application | A candidate applies to a company's role |
| Application update | A company changes the status of an application |
| New message | Someone gets a message (at most one email per conversation every 30 minutes) |
| New roles for a saved search | Daily or weekly digests, when turned on (below) |
| Contact form | Someone sends the form at `/contact` (help, bug, feature idea, listing problem, hiring or partnership, other); bug reports add the browser, logged-in senders add their account, and an optional screenshot comes attached. Replying answers them directly |

Email verification is required before applying, messaging, posting or editing roles, saving searches with alerts, claiming a company and asking for a public profile. Signed-in people can still browse and use their dashboard. Choosing a password from an emailed link also confirms the address.

People change their email in Settings > Security with their password. A verified email only changes after the link sent to the old address is opened (it works for 60 minutes); the new address then gets the usual verification link. An unverified email changes right away and the new address gets the verification link.

Application and message emails wait in the queue, so production needs the queue worker running (see below). The in-app notice still shows right away.

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

## Sign-up sources

Add `?ref=<slug>` to links you share, for example `/opportunities?ref=reddit-tunisia` (letters, digits and dashes, up to 60). The first ref someone opens is kept in their session and saved on their account (`users.signup_ref`) when they sign up. Admin > Sign-up sources (`/admin/signups`) counts sign-ups per ref and how many of those candidates turned on an alert. Nothing else is stored and no outside tool is used.

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
