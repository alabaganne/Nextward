# Curated opportunity seed

Checked on **15 October 2026 at 14:20 UTC**. The six employer pages were open and showed an application action. This records what was available at the check time, not a guarantee that the employer is still recruiting later.

The catalogue has **four internships and two jobs**, with **three Tunisia listings, two country-specific international internships and one worldwide remote job**. Every listing was posted in the last 30 days. Descriptions are original summaries. No employer accounts, applicants, applications, messages, reviews or salary estimates are created.

Four listings from the first snapshot were removed on 15 October 2026 because they were posted long ago (Devoteam in 2025, Value in 2025, Canonical Sustaining Engineering in 2021) or had no posting date (Automattic).

| Employer | Opportunity and original source | Location | Type |
| --- | --- | --- | --- |
| Amaris Consulting | [Stage de Fin d'études - IT](https://careers.amaris.com/jobs/45472) | Tunis | PFE internship |
| Amaris Consulting | [Stage de Fin d'études - Télécom](https://careers.mantu.com/brands/amaris-consulting/jobs/45473) | Tunis | PFE internship |
| Sopra HR Software | [Business Analyst IT (Senior)](https://jobs.smartrecruiters.com/SopraSteria1/744000154692800) | Tunis | Job |
| Affirm | [IT Engineer Intern (Early Careers Summer 2027)](https://job-boards.greenhouse.io/affirm/jobs/8011375003) | San Francisco, US | Internship |
| Ubisoft | [Talent Acquisition & Campus Assistant](https://jobs.smartrecruiters.com/Ubisoft2/744000154109855) | Paris, France | Internship |
| Canonical | [Graduate Software Engineer](https://canonical.com/careers/8249005/graduate-software-engineer-open-source-and-linux-canonical-ubuntu-remote) | Worldwide remote | Graduate job |

## How to seed

After migrations, use `php artisan db:seed` locally or `php artisan db:seed --force` in production. The default seeder loads reference cities and fields, then this catalogue. `php artisan db:seed --class=OpportunitySeeder --force` loads only the opportunities and their required references. The legacy `InternshipSeeder` delegates to the same real catalogue.

Seeding makes no network requests and does not delete data. Repeated runs skip existing application URLs and source identities. They preserve edited listings, closed/suppressed records, source permissions and verification dates. Model events are disabled during the seed so it does not enqueue mail or indexing notifications.

Use a clean production database. Seeding does **not** remove demo records already present in an old database. Do not deploy a copy of a demo database or run `migrate:fresh` against valuable data. The previous complete demo dataset is now an explicit fixture at `tests/Fixtures/DemoSeeder.php`; it is used by workflow tests, not by the default seed. The older standalone company/student/application/message seeders are also demo utilities, guarded against production, and are not called by the default seed.

## Freshness and the daily import job

This is a manually checked snapshot, not an enabled recurring scraper. Each source records editorial approval for the original summary and factual fields only, with automated imports disabled and publication in review mode. No employer affiliation, licence for wholesale copying or automatic adapter support is implied. Some pages use microdata or malformed JSON-LD, so the existing JSON-LD reader cannot necessarily refresh them without further source integration.

Nothing re-checks these pages automatically, so the curated sources use a **14-day freshness window** (`settings.freshness_hours`) in place of the 48-hour rule used by automatic imports. If the snapshot is older than that when first seeded, its new opportunities are saved as drafts and Artisan prints a warning. Reconciliation moves them to draft once the window passes, and closes any imported listing posted more than 60 days ago. Rerunning the seed never advances `last_verified_at`, republishes an edited record or reopens a closed one.

The daily import itself comes from the sources in `ImportSourceSeeder`: employer job boards on Greenhouse, Lever, Ashby, Workable and Recruitee (added on 25 October 2026: Automattic, Elastic, Vercel, GetYourGuide, Contentful, Adyen, N26, Wolt, Celonis, n8n, Resend, Sanity, Hugging Face and bunq, picked because they hire remotely or help with visas), and the remote boards Himalayas, Remote OK, Remotive, We Work Remotely (full-stack, front-end and back-end feeds) and the Hacker News "Who is hiring" thread, which only feed private job leads and are never published. Tunisian job sites (Tanitjobs, Keejob) are no longer read.

Before a later launch, revisit each original page and its application action. Update or replace unavailable roles, record the actual check time, and review the existing records through the import workflow. Enable recurring imports only after checking source reuse requirements and confirming the provider can read the page. For repeatable deployment checks, do not replace the fixed timestamp with `now()`.

## Data decisions

- Unknown deadlines, relocation support and visa sponsorship remain unknown. A foreign location is not evidence of visa support.
- Amaris IT is classified as an internship because its title and description explicitly describe a PFE, despite a conflicting generic employment label. Neither Amaris listing explicitly specifies remote/hybrid arrangements. January is stored as a term, without inventing a January 1 start date.
- Source publication dates come from page metadata or, for Affirm, its [public job record](https://boards-api.greenhouse.io/v1/boards/affirm/jobs/8011375003). Amaris Telecom exposes a date without a timezone, so only its calendar date is retained. Unknown dates remain null; discovery is not presented as original publication.
- Affirm's USD 50 hourly rate is the only structured numeric salary. Its company-wide remote policy is not treated as confirmation that this internship is remote or available from Tunisia. Its 12-to-16-week duration stays in the description rather than becoming a false exact duration.
- Ubisoft describes a paid six-month internship but provides no amount. Months are not converted into an invented exact number of weeks.
- Canonical explicitly lists Tunisia in the role's applicant-location metadata. Its graduate application track is limited to first degrees completed or expected in 2025 or 2026.

Public pages remain indexable while published and open. Listings without a reliable original publication date remain readable and indexable, but omit `JobPosting` rich-result markup under the existing SEO rules.
