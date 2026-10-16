# Deploy Nextward to a VPS

Follow this guide for a single Linux VPS. It describes deployment steps; it does not mean the VPS has already been configured. Application code comes from the `main` branch of [alabaganne/Nextward](https://github.com/alabaganne/Nextward).

Use [VPS_DEPLOYMENT.md](VPS_DEPLOYMENT.md) for the complete environment, PHP-FPM, Nginx, Supervisor, cron, permission and monitoring configurations. Replace example domains, service names and binary paths with your server's values. Run each phase separately and stop if a command fails.

## 1. Prepare the server

Provision the following before running application commands:

- Nginx with a valid HTTPS certificate and DNS pointing to the VPS.
- PHP CLI and PHP-FPM 8.3 or later, with matching versions and the extensions listed in [the runtime requirements](VPS_DEPLOYMENT.md#runtime-and-directory-layout). CLI workers also need PCNTL.
- Composer 2, Git, Node.js 24 and npm. Use a stable system Node path for Supervisor.
- MySQL or PostgreSQL, with an application database and a dedicated database user. The examples use MySQL.
- Supervisor and cron, enabled on boot.
- A service account named `nextward`, with access to the repository, and the `www-data` web server group.
- A Resend API key and a sender on a domain verified in Resend (today `nextward@alabaganne.com`), for email verification, password resets and notifications.

Keep database and SSR ports private. Serve only `/srv/nextward/current/public`. Configure the dedicated PHP-FPM pool to run as `nextward`, using the example in [the VPS guide](VPS_DEPLOYMENT.md#php-fpm).

Create the base directories as a server administrator:

```bash
sudo install -d -o nextward -g www-data -m 0750 \
  /srv/nextward /srv/nextward/releases /srv/nextward/shared
sudo install -d -o nextward -g www-data -m 0700 /srv/nextward/shared/secrets
```

Run the remaining Git, Composer, npm and Artisan commands as `nextward`, not as root. Commands prefixed with `sudo` require an administrator. Keep the same shell for blocks using `release_dir`.

## 2. Prepare a release

Use a new directory for each deployment, including updates:

```bash
umask 022
release_dir="/srv/nextward/releases/$(date -u +%Y%m%dT%H%M%SZ)"
git clone --branch main --single-branch https://github.com/alabaganne/Nextward.git "$release_dir"
cd "$release_dir"
git rev-parse HEAD

mkdir -p /srv/nextward/shared/storage/app/private
mkdir -p /srv/nextward/shared/storage/app/public
mkdir -p /srv/nextward/shared/storage/framework/cache/data
mkdir -p /srv/nextward/shared/storage/framework/sessions
mkdir -p /srv/nextward/shared/storage/framework/views
mkdir -p /srv/nextward/shared/storage/logs

mv storage storage.release-template
ln -s /srv/nextward/shared/storage storage
ln -s /srv/nextward/shared/.env .env
```

Record the commit ID for the release. The `mv storage` command is for this fresh checkout only. Never move or replace an existing installation's live storage with an empty directory. If migrating an existing installation into this layout, transfer its complete storage and private `.env` into `shared` first, preserving the database and `APP_KEY`.

On the **first installation only**, create the environment file if it does not exist:

```bash
if [ ! -e /srv/nextward/shared/.env ]; then
  install -m 0600 .env.example /srv/nextward/shared/.env
fi
```

Edit `/srv/nextward/shared/.env` privately. Use [the production environment example](VPS_DEPLOYMENT.md#environment), including:

```dotenv
APP_NAME=Nextward
APP_ENV=production
APP_DEBUG=false
APP_URL=https://nextward.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nextward
DB_USERNAME=nextward
DB_PASSWORD="replace-with-a-private-password"

CACHE_STORE=file
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=360

INERTIA_SSR_ENABLED=true
INERTIA_SSR_RUNTIME=node
INERTIA_SSR_URL=http://127.0.0.1:13714
INERTIA_SSR_ENSURE_RUNTIME_EXISTS=false
INERTIA_SSR_ENSURE_BUNDLE_EXISTS=true
INERTIA_SSR_THROW_ON_ERROR=false

LOG_CHANNEL=daily
LOG_LEVEL=warning
OPPORTUNITY_DIGESTS_ENABLED=false
GOOGLE_INDEXING_ENABLED=false
```

Set `RESEND_API_KEY`, `MAIL_FROM_ADDRESS` and `MAIL_CONTACT_ADDRESS`, and leave `MAIL_MAILER` empty so Resend is used (see [Email](README.md#email)). Application and message emails are queued, so the queue worker must run. `APP_URL` must be the canonical HTTPS origin used in search results, sitemaps and email links. Leave optional digest sending and Google indexing disabled until their delivery and credentials are verified. File cache and sessions use shared storage on this one VPS; the repository does not provision database cache/session tables.

Do not copy local testing settings or a demo database onto the VPS. Follow [the existing credential exposure instructions](VPS_DEPLOYMENT.md#existing-credential-exposure) before using any credentials from an older installation.

## 3. Install dependencies and build

From the new release directory:

```bash
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
composer check-platform-reqs --no-dev
npm ci --include=dev
npm run build
php artisan storage:link
```

`npm run build` produces both `public/build` and `bootstrap/ssr/ssr.js`. Keep `node_modules`, including development dependencies, with the release because the SSR bundle imports them at runtime. Do not run `npm prune --omit=dev`. Production uses the compiled bundles and supervised SSR, not `npm run dev` or `php artisan serve`.

For a **new installation with an empty `APP_KEY` only**:

```bash
php artisan key:generate --force
```

Preserve the existing key on updates. Keep a protected backup of it alongside the database and storage backups.

Apply [storage and cache permissions](VPS_DEPLOYMENT.md#runtime-and-directory-layout) before running the services. For a prepared release, use its path instead of `current`:

```bash
sudo chown -R nextward:www-data /srv/nextward/shared/storage "$release_dir/bootstrap/cache"
sudo find /srv/nextward/shared/storage "$release_dir/bootstrap/cache" -type d -exec chmod 2770 {} \;
sudo find /srv/nextward/shared/storage "$release_dir/bootstrap/cache" -type f -exec chmod 660 {} \;
```

## 4. Migrate and seed

For an existing site, perform the maintenance and backup steps in section 8 **before** migrating. For a new installation, keep public traffic and scheduled work off until setup is complete.

```bash
php artisan migrate --force
```

On a clean installation, load the reference data and real opportunity snapshot:

```bash
php artisan db:seed --force
```

The default seed adds six real opportunities, their references and the daily import sources (Tanitjobs, Keejob and employer job boards). It creates no login accounts and does not delete existing records. Read [the source and freshness notes](database/seeders/data/SOURCES.md) before launch. The snapshot was verified on 15 October 2026. If it is more than 14 days old, its opportunities are inserted as drafts. Recheck the original vacancies and record genuine verification before publishing; rerunning the seed does not refresh them. The daily import starts on its own once the scheduler runs.

For reference data without the opportunity snapshot, use these commands instead:

```bash
php artisan db:seed --class=CitySeeder --force
php artisan db:seed --class=FieldSeeder --force
```

Do not use `migrate:fresh`, the test fixture seeder, or demo account/activity seeders on production. Existing internal table names, including `internships`, are intentional and must be preserved.

If upgrading an installation with public application attachments, follow [the private attachment migration](README.md#private-application-attachments) during maintenance. Preview `php artisan applications:privatize-attachments` before using `--execute`, and verify the backup and resulting authorized downloads.

## 5. Activate the release and services

Build the release caches:

```bash
php artisan config:cache
php artisan view:cache
```

Do not clear the shared application cache as a routine deployment step. It contains import locks and indexing quotas as well as cached data.

Point `current` at the prepared release. These commands assume GNU/Linux and a `current` path that is absent or already a symlink:

```bash
ln -s "$release_dir" /srv/nextward/current.next
mv -Tf /srv/nextward/current.next /srv/nextward/current
```

Configure [Nginx](VPS_DEPLOYMENT.md#nginx) and [Supervisor](VPS_DEPLOYMENT.md#queue-worker-and-ssr) using the provided templates. Validate their configurations, reload the correct PHP-FPM service to refresh OPcache, then reload Nginx. Install the Supervisor programs:

```bash
sudo nginx -t
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

Both `nextward-queue` and `nextward-ssr` must show `RUNNING`. Use the Supervisor template's 300-second worker timeout, 360-second queue retry interval and 360-second shutdown allowance. SSR listens on `127.0.0.1:13714`; only Nginx exposes the app publicly.

Install exactly one [scheduler cron entry](VPS_DEPLOYMENT.md#cron-and-actual-schedules). For `/etc/cron.d/nextward`, include the service username:

```cron
SHELL=/bin/sh
PATH=/usr/local/bin:/usr/bin:/bin
* * * * * nextward cd /srv/nextward/current && /usr/bin/php artisan schedule:run >> /srv/nextward/shared/storage/logs/scheduler.log 2>&1
```

The file must be owned by root, mode `644`, and end with a newline. Do not also install a user crontab entry or run `schedule:work`. Enable cron and rotate its log. Laravel checks due imports every five minutes, reconciles availability every fifteen minutes, and dispatches digests and indexing hourly. The queue worker executes queued work. No approved and enabled sources means no automatic imports.

## 6. Verify before launch

Run these from `/srv/nextward/current` as `nextward`:

```bash
php artisan migrate:status
php artisan inertia:check-ssr
php artisan schedule:list
php artisan queue:failed
```

After leaving maintenance with `php artisan up` and enabling the single cron entry, allow a minute for its heartbeat, then run:

```bash
php artisan opportunities:health --public
curl --fail --silent --show-error https://nextward.example.com/up
curl --fail --silent --show-error https://nextward.example.com/robots.txt
curl --fail --silent --show-error https://nextward.example.com/sitemap.xml
curl --fail --silent --show-error https://nextward.example.com/opportunities
```

Check the following manually before announcing the site:

- `/`, `/for-students`, `/for-professionals`, `/opportunities`, the French pages and a published opportunity detail render correctly on desktop and mobile.
- With JavaScript disabled, public page headings, opportunity text and links remain visible. Inspect canonical/hreflang metadata and eligible detail-page `JobPosting` data. Text present only in serialized page data is not proof of rendered HTML.
- CSS, logos and illustrations load over HTTPS, with no localhost or Vite development URLs.
- Candidate and employer registration/login, password resets, saving, native applications and external application tracking work. Private attachments reject unauthorized access.
- No fictional opportunities are published, source/application links work, and sitemap URLs use the production domain.

Submit `/sitemap.xml` in Search Console. Configure an external monitor for `opportunities:health --public`, process failures, disk space, backups and certificate expiry. `/up` alone does not check imports, queues or SSR content. Review `storage/logs` and `php artisan queue:failed` when a check fails.

## 7. Create the first administrator and configure imports

Production seeding creates no users. Create a dedicated admin with an email address you control that is not already registered:

```bash
sudo -u nextward php artisan nextward:make-admin you@example.com
```

It asks for a name and a password (typed hidden) and marks the email as confirmed. An admin must have no student/company profile under the current `User::isAdmin()` rules, so the command refuses accounts that have one. On updates, keep the existing admin account.

Open `/admin/imports` to configure sources. Start with approved permission evidence, correct allowed domains, a supported provider, review mode and small request limits. Follow [the import instructions](README.md#approved-sources-and-import-operation). A real seed listing does not by itself establish that its source is ready for recurring scraping. Dry-run imports still make network requests.

Enable email digests only after consent and unsubscribe delivery are checked with controlled accounts. Google indexing is optional; configure it separately using [the indexing instructions](README.md#optional-google-indexing-api). Sitemaps and normal crawling work without the API.

## 8. Deploy later updates

1. Run release checks in a separate local/staging environment. Rehearse migrations against a restored production-like database. Do not run tests against production.
2. Back up the database, public/private storage, `.env` and private credentials. Record the current release path, commit and backup identifier. Keep encrypted backups off the VPS and verify restores.
3. Prepare a new release using sections 2 and 3. Reuse the shared `.env`, storage, database and key. Do not copy the example environment over production settings or generate a new key.
4. Disable the single scheduler cron entry, put the current release into maintenance with `php /srv/nextward/current/artisan down`, and gracefully stop the workers and SSR:

   ```bash
   sudo supervisorctl stop nextward-queue nextward-ssr
   ```

   Wait for active jobs, requests and scheduler invocations to finish. Take a final consistent backup before changing the database.

5. In the new release, run `php artisan migrate --force`, then `php artisan config:cache` and `php artisan view:cache`. Do not seed automatically on every update. Complete any reviewed attachment migration before reopening traffic.
6. Switch `current` using section 5, reload PHP-FPM, then start the stopped services:

   ```bash
   sudo supervisorctl start nextward-ssr nextward-queue
   ```

   Updating Supervisor configuration requires `reread` and `update` as well. Rebuilding bundles does not restart running SSR or queue processes.

7. Check migrations, process status and SSR while maintenance remains active. Run `php /srv/nextward/current/artisan up`, restore the cron entry and repeat section 6. Keep the previous release until these checks pass.

For configuration changes, rebuild the configuration cache and restart SSR/workers. Rebuild frontend bundles too if changing a `VITE_*` setting. Never put private credentials in frontend variables.

## 9. Recover from a failed deployment

Keep maintenance active and cron/workers paused while investigating. Read the application, PHP-FPM, Nginx and Supervisor logs. If migrations have not changed schema compatibility, switch `current` back to the recorded previous release, rebuild its configuration/view caches, reload PHP-FPM and restart SSR/workers.

If the old code is incompatible with the migrated database or attachment layout, a symlink rollback is insufficient. Restore a tested matching database/storage backup and its configuration/key, accounting for later user writes and mail/indexing work. Do not blindly roll back migrations or retry all mail jobs. Follow [the recovery instructions](VPS_DEPLOYMENT.md#checks-monitoring-and-recovery), including uncertain digest delivery reconciliation, before restoring cron and traffic.

Framework references: [Laravel deployment](https://laravel.com/docs/13.x/deployment), [Laravel scheduler](https://laravel.com/docs/13.x/scheduling), [Inertia SSR deployment](https://inertiajs.com/docs/v3/advanced/server-side-rendering#deployment).
