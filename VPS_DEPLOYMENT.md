# Nextward VPS deployment

This is a configuration guide for a future deployment, not a report that a VPS has been configured. No remote server changes are needed to use this document. Replace `nextward.example.com`, certificate paths, database credentials and binary paths with the values on your server.

## Runtime and directory layout

Use Nginx, PHP-FPM 8.3 or later, Composer, Node.js 24, a production database, Supervisor and cron. The PHP CLI and FPM must use the same PHP version and extensions. Enable PDO with your database driver, cURL, DOM/XML, Mbstring, OpenSSL, Tokenizer, Fileinfo and PCNTL for CLI queue timeouts. Intl is optional: English and French country names come from `resources/data/countries.php`. Check the installed requirements with `composer check-platform-reqs --no-dev` after installation. SQLite is for isolated tests, not the deployment example below.

Keep database and SSR ports off the public network. Permit HTTPS and the SSH access you need; permit HTTP for HTTPS redirects and certificate validation. Configure automatic certificate renewal and monitor it. Size PHP workers, database memory and queue concurrency for the actual VPS; start with one queue worker.

The examples use an application user named `nextward` and Nginx's group `www-data`:

```text
/srv/nextward/current                     -> selected release directory
/srv/nextward/releases/<release-id>/       code, vendor, node_modules and built bundles
/srv/nextward/shared/.env                  private production configuration
/srv/nextward/shared/storage/              persistent Laravel storage
/srv/nextward/shared/secrets/              optional mail/API credential files
```

In each release, `.env` points to `shared/.env` and `storage` points to `shared/storage`. Create these links before Artisan commands. Keep `bootstrap/cache` release-specific. Preserve `storage/app/private`, `storage/app/public`, `storage/framework` and `storage/logs` across releases. Nginx serves only `current/public`.

Code and dependencies should be readable by the service users and writable only by your deployment account. PHP-FPM, cron and workers run as `nextward`; they need write access to `storage` and `bootstrap/cache`. For those two directories only, set ownership and permissions after placing a release:

```bash
sudo chown -R nextward:www-data /srv/nextward/shared/storage /srv/nextward/current/bootstrap/cache
sudo find /srv/nextward/shared/storage /srv/nextward/current/bootstrap/cache -type d -exec chmod 2770 {} \;
sudo find /srv/nextward/shared/storage /srv/nextward/current/bootstrap/cache -type f -exec chmod 660 {} \;
sudo chmod 600 /srv/nextward/shared/.env
```

The `.env` owner must be `nextward`. Its parent directories need appropriate traversal permissions. Public uploads under `storage/app/public` must be readable by Nginx. Never link `storage/app/private` or `shared/secrets` into `public`, and never use `chmod 777`.

### Chrome for PDFs

Resumes, tailored CVs and cover letters are printed to PDF by headless Chrome through Browsershot and Puppeteer (`app/Support/CvPdf.php`). Install Google Chrome, which also pulls in the libraries it needs:

```bash
wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
sudo apt install ./google-chrome-stable_current_amd64.deb
```

`/usr/bin/google-chrome`, `/usr/bin/chromium` and `/usr/bin/chromium-browser` are found on their own. Set `CV_CHROME_PATH` for another path. If PDFs fail with a sandbox error, which happens when Chrome runs as root or in a container, set `CV_CHROME_NO_SANDBOX=true`. `CV_PDF_TIMEOUT` (default 60 seconds) limits each print. After deploying, make a resume from a test profile and download it to check.

## Environment

Start from `.env.example`, then configure at least:

```dotenv
APP_NAME=Nextward
APP_ENV=production
APP_DEBUG=false
APP_URL=https://nextward.example.com
APP_KEY=<existing-key-or-new-install-key>

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<database-name>
DB_USERNAME=<dedicated-application-user>
DB_PASSWORD=<secret>

LOG_CHANNEL=daily
LOG_LEVEL=warning
CACHE_STORE=file
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=360
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true

INERTIA_SSR_ENABLED=true
INERTIA_SSR_RUNTIME=node
INERTIA_SSR_URL=http://127.0.0.1:13714
INERTIA_SSR_ENSURE_RUNTIME_EXISTS=false
INERTIA_SSR_ENSURE_BUNDLE_EXISTS=true
INERTIA_SSR_THROW_ON_ERROR=false

OPPORTUNITY_DIGESTS_ENABLED=false
GOOGLE_INDEXING_ENABLED=false
GOOGLE_INDEXING_DAILY_LIMIT=200

CV_CHROME_PATH=
CV_CHROME_NO_SANDBOX=false

BILLING_ENABLED=true
BILLING_SIGNUP_CREDITS=20
BILLING_MONTHLY_CREDITS=5
# Payments locked and free credits (100 at a time) on request, until payments open
BILLING_BETA=true
BILLING_BETA_CREDITS=100

MAIL_MAILER=<your-configured-provider>
MAIL_FROM_ADDRESS=<verified-sender-address>
MAIL_FROM_NAME=Nextward
```

Use the provider's required mail settings. `mailhog` from the local example is not a production mail service. Configure and test password resets and account notifications as well as digests. Leave digest sending and the optional Google Indexing API off until separately verified with controlled accounts and credentials. An indexing service-account file belongs outside the public tree, with mode `600`; `GOOGLE_INDEXING_CREDENTIALS` takes its absolute path.

Generate an `APP_KEY` only for a new installation. Preserve the existing key on upgrades and keep a protected backup. Do not put secrets in Git or `VITE_*` variables. Set `APP_URL` to the canonical HTTPS origin because public links, sitemaps and structured data use it.

### Existing credential exposure

The repository previously tracked `.env` with populated application, database, mail and Pusher secrets. This change removes the file from tracking and ignores local environment files, but it does not erase earlier commits. Before deployment, replace any real exposed database, mail and Pusher credentials with new provider-issued credentials, then revoke the old ones. Use a fresh application key for a new installation. For an existing installation, inventory encrypted data and sessions and plan their migration before rotating `APP_KEY`; blindly changing it can make encrypted data unreadable. Treat the old key as compromised. Removing historical copies requires a separate coordinated history cleanup and does not replace credential rotation.

For an existing installation, preserve its database name and data. The `2026_10_18_170000_rename_internships_and_students` migration renames `internships` to `opportunities` and `students` to `candidates` in place (rows, keys and indexes are kept); `kind` still tells jobs from internships. Back up the database before running it. Preserve the old effective `CACHE_PREFIX` and `SESSION_COOKIE` explicitly when changing `APP_NAME` if you need existing locks, quotas and sessions to continue. Internal indexing cache keys and legacy bot-name recognition remain compatible. The public/blog configuration now uses `nextward_public` and `nextward_blog`; rebuild the configuration cache after deploying.

The file cache and file sessions are suitable for this one-VPS layout because PHP, cron and workers share persistent storage and permissions. Multiple application hosts need a shared supported cache with atomic locks and shared sessions. The repository does not create the cache/lock or session tables required by Laravel's database cache/session drivers. Do not select those drivers without provisioning their tables. The database queue's `jobs` and `failed_jobs` tables are migrated by this application.

## PHP-FPM

Use a dedicated pool, for example `/etc/php/8.4/fpm/pool.d/nextward.conf`. Adjust the version path to the PHP installation:

```ini
[nextward]
user = nextward
group = www-data
listen = /run/php/nextward.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500
catch_workers_output = yes
php_admin_flag[display_errors] = off
php_admin_flag[log_errors] = on
php_admin_value[upload_max_filesize] = 10M
php_admin_value[post_max_size] = 32M
php_admin_value[max_execution_time] = 60
```

These worker counts are an initial example, not measured capacity. The 32 MB request limit applies to the entire request, including multi-file uploads. Keep the Nginx and PHP limits aligned with the product limits you intend to support. Imports run through CLI queue workers, not PHP-FPM requests.

## Nginx

Use the actual certificate paths and domain. Configure the machine's default virtual host to reject unknown hosts; do not serve this application as a catch-all host.

```nginx
server {
    listen 80;
    server_name nextward.example.com;
    return 301 https://nextward.example.com$request_uri;
}

server {
    listen 443 ssl;
    server_name nextward.example.com;
    root /srv/nextward/current/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/nextward.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/nextward.example.com/privkey.pem;
    client_max_body_size 32m;
    add_header X-Content-Type-Options nosniff always;
    add_header Referrer-Policy strict-origin-when-cross-origin always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options SAMEORIGIN always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /index.php {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/nextward.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root/index.php;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        # Laravel currently trusts proxies. Replace client-supplied forwarding headers.
        fastcgi_param HTTP_X_FORWARDED_PROTO $scheme;
        fastcgi_param HTTP_X_FORWARDED_HOST $host;
        fastcgi_param HTTP_X_FORWARDED_PORT $server_port;
        fastcgi_param HTTP_X_FORWARDED_FOR $remote_addr;
        fastcgi_param HTTP_X_FORWARDED_PREFIX "";
        fastcgi_param HTTP_FORWARDED "";
        fastcgi_hide_header X-Powered-By;
    }

    location ~ \.php(?:/|$) { return 404; }
    location ~ /\.(?!well-known/) { deny all; }
}
```

If using a CDN or load balancer, define the trusted proxy addresses and trusted header policy for that topology instead of copying arbitrary incoming forwarding headers. The example terminates TLS directly at Nginx. Adapt the HTTP block if your certificate renewal method needs an HTTP challenge location. Validate with `sudo nginx -t` and your version's PHP-FPM configuration test before reloading services.

## Queue worker and SSR

Install one Supervisor configuration, for example `/etc/supervisor/conf.d/nextward.conf`. Verify `/usr/bin/php` and `/usr/bin/node` point to the required versions as the `nextward` user. Do not rely on an interactive shell's Node version manager.

```ini
[program:nextward-queue]
directory=/srv/nextward/current
command=/usr/bin/php /srv/nextward/current/artisan queue:work database --queue=default --sleep=3 --timeout=300 --tries=3 --max-time=3600
user=nextward
numprocs=1
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stopwaitsecs=360
redirect_stderr=true
stdout_logfile=/srv/nextward/shared/storage/logs/queue-worker.log
stdout_logfile_maxbytes=20MB
stdout_logfile_backups=5

[program:nextward-ssr]
directory=/srv/nextward/current
command=/usr/bin/php /srv/nextward/current/artisan inertia:start-ssr --runtime=/usr/bin/node
user=nextward
numprocs=1
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stopwaitsecs=30
redirect_stderr=true
stdout_logfile=/srv/nextward/shared/storage/logs/ssr.log
stdout_logfile_maxbytes=20MB
stdout_logfile_backups=5
environment=PATH="/usr/local/bin:/usr/bin:/bin",NODE_ENV="production"
```

The importer job timeout is 300 seconds. Its database queue retry interval is 360 seconds and Supervisor allows 360 seconds for shutdown. Keep retry intervals greater than job timeouts. If switching to Redis or Beanstalkd, set their corresponding `*_QUEUE_RETRY_AFTER=360` as well; their framework defaults are shorter. Jobs have their own retry/backoff policies. A synchronous queue is not appropriate for production imports.

The SSR service listens on `127.0.0.1:13714`. Keep that port private and use one SSR launcher. `npm run ssr` is an alternative launcher, not an additional service. Both client and SSR bundles are built by `npm run build`. The current SSR bundle imports packages from `node_modules`, including packages listed under `devDependencies`, so use `npm ci --include=dev` and **do not prune development packages after building**. Retain the complete installed packages with the release.

After installing or changing the process configuration:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

Inertia can fall back to browser rendering after SSR errors. A healthy Node process does not prove that public content is readable by search engines. Check actual initial HTML as well as `php artisan inertia:check-ssr`.

## Cron and actual schedules

Create `/etc/cron.d/nextward`, owned by root with mode `644` and a final newline:

```cron
SHELL=/bin/sh
PATH=/usr/local/bin:/usr/bin:/bin
* * * * * nextward cd /srv/nextward/current && /usr/bin/php artisan schedule:run >> /srv/nextward/shared/storage/logs/scheduler.log 2>&1
```

This is a system cron file with a username column. If using `crontab -u nextward -e` instead, omit that column. Install only one of these approaches and do not also run `schedule:work`. Enable the host's cron service and configure rotation for `scheduler.log`.

`routes/console.php` defines:

| Task | Frequency | Gate or purpose |
| --- | --- | --- |
| Import dispatcher | Every five minutes | Only enabled, approved sources whose `next_run_at` is due |
| Availability reconciliation | Every fifteen minutes | Deadlines, stale sources, permission changes and removals |
| Digest dispatcher | Hourly | Feature enabled, explicit subscription and candidate-local due time |
| Indexing dispatcher | Hourly | Feature enabled for API work; outbox retained when disabled |
| Monthly credits | 1st of the month, 06:00 Africa/Tunis | Adds `BILLING_MONTHLY_CREDITS` to candidates with a verified email, once a month, while billing is on |
| Scheduler heartbeat | Every minute | Used by `opportunities:health` |

For a source with a 24-hour cadence, a completed full run sets its next full run to 05:00 Africa/Tunis. Other cadences use their configured interval. New sources can run immediately; bounded pending batches resume sooner, and failed requests use their retry time. This is not a rule that every source is scraped only once at 05:00. Each source also has a lock, request budget and permission checks. Three consecutive failed runs pause the source.

Cron dispatches jobs; it does not replace the queue worker. Verify the configured schedule as the same application user:

```bash
cd /srv/nextward/current
sudo -u nextward /usr/bin/php artisan schedule:list
```

Read `schedule:list` in its displayed timezone; source timing is handled separately in application code. Do not run `schedule:run` manually against live data unless you intend to dispatch the work that is due.

## Release procedure

1. Rehearse the exact release and migrations against a restored production-like database. Run the full tests in a separate testing environment. Do not point PHPUnit or demo seeders at production.
2. Back up the database, public/private storage, `.env` and private credential files. Store encrypted off-host backups with retention and restore checks. Preserve `APP_KEY`. Use a protected database client option file or your provider's backup facility; keep database passwords out of shell history. Record the backup identifier and current release.
3. Prepare a new release with its persistent `.env`/storage links. Install and build using that release's directory:

   ```bash
   composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
   composer check-platform-reqs --no-dev
   npm ci --include=dev
   npm run build
   php artisan storage:link
   ```

4. Pause deployment-time scheduler dispatch by temporarily disabling the single cron entry. Put the old application into maintenance with `php artisan down`. Gracefully stop `nextward-queue` and wait for active jobs before migrations. Stop `nextward-ssr` before switching releases. Take a final consistent backup if application writes continued after the earlier backup.
5. From the new release, run `php artisan migrate --force`. Never run `migrate:fresh` against existing data. For a clean installation, `php artisan db:seed --force` adds reference catalogues, six real opportunities and the daily import sources without deleting data or creating demo accounts. Read [the source and freshness notes](database/seeders/data/SOURCES.md) and recheck the listings before launch: snapshots older than 14 days are inserted as drafts. This seed does not remove demo data from an old database. For reference data only, use `php artisan db:seed --class=CitySeeder --force` and `php artisan db:seed --class=FieldSeeder --force`.
6. For a legacy installation, preview `php artisan applications:privatize-attachments`. After rehearsing the migration and verifying the database/storage backup, use its `--execute` option to copy and verify private attachments, update references and remove old public files. Resolve any failures before considering the old public attachments protected.
7. Build release caches with `php artisan config:cache` and `php artisan view:cache`. Switch `current` to the new release, reload PHP-FPM to refresh its OPcache, and start/restart `nextward-ssr` and `nextward-queue`. On an in-place deployment, use `php artisan queue:restart` and let Supervisor replace workers. Do not clear the shared application cache just to deploy: it also stores locks and indexing quotas.
8. Check migrations, processes and SSR, then run `php artisan up` and restore the one cron entry. Allow a minute for its heartbeat. Run the checks below, including an authenticated application flow. Keep the previous release until the checks pass.

Keep maintenance active if migrations or health checks fail. The above pause is intentional for these schema changes; it is not a claim of zero-downtime deployment.

## Checks, monitoring and recovery

```bash
cd /srv/nextward/current
php artisan migrate:status
php artisan inertia:check-ssr
php artisan queue:failed
php artisan opportunities:health --public
curl --fail --silent --show-error https://nextward.example.com/up
curl --fail --silent --show-error https://nextward.example.com/opportunities
curl --fail --silent --show-error https://nextward.example.com/sitemap.xml
```

Run Artisan as `nextward` using the production PHP binary. Inspect the initial HTML for real opportunity text, a canonical URL and valid structured data on an eligible detail page. Verify public and private attachment access, candidate/employer login, native applications, external tracking, saving and unsubscribe behavior. Verify the domain in Search Console and submit `/sitemap.xml`; API acknowledgement does not guarantee indexing.

`opportunities:health --public` returns exit code 1 when it finds an old/missing scheduler heartbeat, delayed queue work, failed jobs, overdue/erroring sources, stale imported content, uncertain digest deliveries, failed indexing notifications or broken SSR/public HTML. It logs warnings. Configure a monitor outside this cron process to alert on failures; a stopped cron daemon cannot report its own failure. Also monitor disk space, database backups, certificate expiry, PHP/Nginx errors and Supervisor restarts. `/up` alone does not verify queues, sources or SSR content.

Review source permissions and errors in `/app/admin/imports`. Do not enable fictional demo sources. Start each real approved source in review mode with a small request limit. `opportunities:import --dry-run` still makes real network requests. Digests require explicit consent; do not enable them merely to test a worker. Use fake providers in automated checks, then controlled accounts for production delivery verification.

Investigate failed jobs before targeted retries. If a digest outcome is uncertain, use `opportunities:review-digest BATCH_UUID` and compare the provider's delivery record, then use either `--mark-sent` or `--retry-confirmed-unsent` for the confirmed outcome. Do not blindly retry all mail jobs. For a crashed scheduler mutex, first establish that the original process is gone, then `php artisan schedule:clear-cache` clears scheduler locks; it does not resolve arbitrary source/delivery locks.

For rollback, pause cron and workers, enter maintenance, and assess database compatibility before switching back to an earlier release. An older release must understand the current schema and application data. Do not blindly roll back dependent migrations or discard delivery history. If a restore is required, restore the matching database and storage backup, account for any later user activity, and reconcile mail/indexing operations before resuming jobs to avoid duplicate sends or removals. Restore the matching key/configuration, rebuild configuration/view caches, restart SSR/workers and repeat the health checks before reopening traffic.

Framework references: [Laravel scheduling](https://laravel.com/docs/13.x/scheduling), [Laravel queues](https://laravel.com/docs/13.x/queues), [Supervisor configuration](https://supervisord.org/configuration.html).
