# Internly

Internly connects students with companies hiring interns. Students can browse public internship listings, apply with a cover letter and attachments, save roles, track applications, and message companies. Companies publish internships and manage their application pipeline in a shared workspace.

The public site and authentication pages use the flat Internly visual style. The workspace includes role-aware dashboards, application stages, saved internships, messaging, company profiles, account settings, and administration screens.

## Stack

- Laravel 13 with PHP 8.3+, Inertia.js 3, and Vue 3
- Tailwind CSS 4 with shared brand and workspace tokens in `resources/css/app.css`
- Vite 8 using `vite.config.js`
- Heroicons and Lucide Vue components for interface icons
- MySQL or PostgreSQL for application data; SQLite for automated tests
- Pusher and Laravel Echo dependencies retained; live browser updates are currently disabled

Use Node.js 24 (the version in `.nvmrc`), Composer, and PHP with PDO, cURL, DOM/XML, Mbstring, OpenSSL, Tokenizer, and SQLite support for tests. Install the appropriate PDO driver for your application database.

## Local setup

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Set `APP_URL` and your `DB_*` values in `.env`, then prepare the database and public file storage:

```bash
php artisan migrate
php artisan db:seed --class=CitySeeder
php artisan db:seed --class=FieldSeeder
php artisan storage:link
```

The city and field catalogs populate registration and internship filters. Run the application and Vite in separate terminals:

```bash
php artisan serve
npm run dev
```

The default local URL is `http://127.0.0.1:8000`. Alternatively, `composer dev` starts the web server, logs, and Vite together. For production assets, run `npm run build`.

## Demo data

On a disposable local database, `php artisan db:seed` populates the demo workspace. **The demo seeder clears existing application data.** Do not run it against a database you need to preserve.

Demo account passwords are `password`:

| Role | Email |
| --- | --- |
| Student | `student@example.com` |
| Company | `company@example.com` |
| Company (Atlas Cloud) | `atlascloud@example.com` |
| Admin | `admin@example.com` |

## Messages and notifications

Messages and notifications are saved and available when the page refreshes.
Live browser updates are currently disabled: Echo initialization in
`resources/js/bootstrap.js` and the message subscription listeners are inactive.
Configuring Pusher environment variables alone does not enable realtime delivery.

To add live updates later, restore Echo initialization and the relevant page
listeners, configure `BROADCAST_CONNECTION=pusher` plus the `PUSHER_*` /
`VITE_PUSHER_*` values, and rebuild frontend assets. The default queue connection
is synchronous; another queue driver also requires `php artisan queue:work`.

## Verification

```bash
composer validate --strict
npm run build
php artisan test
```

The PHPUnit configuration uses an isolated in-memory SQLite database. Tests cover authentication, public listings, applications, saved roles, messaging, notifications, and workspace page responses.

## Project notes

- `LANDING_PAGE_DESIGN.md`: public visual direction and landing page structure
- `FUTURE_PLANS.md`: planned founder introduction and product walkthrough video
- `public/internly-assets/README.md`: brand illustrations and media assets
- `design/DESIGN.md` and `design/INTEGRATION.md`: workspace design reference and component mapping; the working Tailwind 4 tokens live in `resources/css/app.css`

Internship fields include work type, duration, term, compensation, and publication status. Applications use the pipeline stages `submitted`, `viewed`, `review`, `interview`, `offer`, and `closed`. The existing `likes` relationship represents saved internships, and attachments remain JSON-backed application data.

[Open the demo](https://internly.alabaganne.com).

Licensed under the [MIT license](https://opensource.org/licenses/MIT).
