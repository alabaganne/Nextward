# Nextward opportunity finder: final implementation plan

Prepared on 13 October 2026. Status: implemented and locally verified in the working tree on `main`. No branch, commit, push, pull request, or production deployment was created. The sections below retain the agreed implementation scope; operational setup is documented in [README.md](/Users/alabaganne/Desktop/Projects/nextward/README.md).

**Implementation verification:** client and SSR production builds pass, all 123 application/configuration/route/migration PHP files pass syntax checks, and Composer validation passes. Disposable SQLite checks cover fresh migration, rollback and reapplication. Temporary backend fixtures pass 24 tests with 199 assertions; separate account, private attachment, indexing, recommendation and operational-health checks pass. Raw HTTP checks cover public HTML, metadata, schema, filters, pagination, redirects, sitemaps and account isolation. Browser checks cover desktop/mobile, French pages, professional sign-in, application controls and employer job drafts. A 10,005-role fixture confirms sitemap chunking beyond the previous cap.

**Inherited test results:** 43 passed and 14 failed. Remaining failures expect the old internship routes, static homepage, flat sitemap, required Tunisian city, or use outdated migration/unmigrated-database fixtures. Repository tests were not edited. Targeted replacement behavior was checked outside the repository test files.

**Production work remains:** rehearse and apply migrations against the real database, migrate legacy public CVs after backup, supervise SSR/queues/scheduler, onboard approved sources, and configure optional mail and Google indexing services. No live source was enabled, real email sent, production database changed, or Google notification submitted. Real source coverage, indexing and field performance still need production verification.

**Product decision:** Nextward becomes one place to discover internships and jobs in Tunisia and worldwide, with tailored experiences for students and professionals. One catalogue, one candidate account, and one import pipeline support both audiences.

**Public launch requirement:** approved, active opportunities must have useful, readable pages in the initial HTML response, accessible without an account. Both internships and jobs are included when the new public experience launches.

1. **What the current app gives us, and what must change**

   Keep the current brand, visual style, Laravel backend, Vue/Inertia frontend, relational database, native employer posting, saved roles, applications, and messaging. The work extends those features rather than replacing the stack.

   Findings verified during the review:

   | Area | Current state | Required change |
   | --- | --- | --- |
   | Positioning | Student and internship language throughout the app | Clearly serve students, graduates, and professionals |
   | Inventory | The live public board had no open roles during the review | Add regular imports alongside employer posts |
   | Public rendering | Homepage and browse responses contain metadata and Inertia data, but no visible headings, cards, or links in the initial HTML | Enable real server rendering for public content |
   | Discovery | `/internships`; keyword search currently searches the title | Introduce `/opportunities`, type filters, and title/company/description search |
   | Data | Internship assumptions, Tunisian city relations, required closing dates, and salary display using `$` | Support jobs, international locations, unknown deadlines, and real currencies |
   | Companies | Several reads assume every company has an account | Support imported companies without creating fake users |
   | Applications | Native employer workflow | Add external apply and honest, candidate-managed tracking |
   | SEO | Blanket indexing metadata, a query-stripping canonical, and a sitemap capped at 500 roles | Route-specific indexing, correct canonicals, and complete sitemaps |
   | Local setup | Locked Laravel 13 dependencies differ from the installed Laravel 8 vendor directory | Restore the locked dependencies before implementation verification |
   | CV files | Application attachments use public storage | Move CV access behind authorization as part of the application work |

   Relevant starting points: [routes](/Users/alabaganne/Desktop/Projects/nextward/routes/web.php), [HTML template](/Users/alabaganne/Desktop/Projects/nextward/resources/views/app.blade.php), [frontend entry](/Users/alabaganne/Desktop/Projects/nextward/resources/js/app.js), [listing controller](/Users/alabaganne/Desktop/Projects/nextward/app/Http/Controllers/InternshipController.php), and [application controller](/Users/alabaganne/Desktop/Projects/nextward/app/Http/Controllers/ApplicationController.php).

2. **Product scope and audience rules**

   - **Nextward for Students:** PFE and summer internships, other internships, apprenticeships where supported, graduate programmes, and first jobs.
   - **Nextward for Professionals:** jobs across experience levels, including full-time, part-time, contract, and freelance employment listings.
   - **Opportunity type:** `internship` or `job`. “Opportunity” is the umbrella term, not a third selectable type. Employment type, seniority, and internship programme are separate attributes.
   - **Geography:** Tunisia and worldwide. Prominent shortcuts are “In Tunisia”, “Remote from Tunisia”, and “Abroad with relocation”. Users can also browse any supported country without selecting one of these shortcuts.
   - **One candidate identity:** a student can explore jobs; a professional can explore internships. Audience preferences change the default content, not access rights.
   - **Credible availability:** show employer, source, original posting date when known, last successful verification, application destination, and stated eligibility.

   Start with employment opportunities. Scholarships, grants, events, courses, automatic applications, and a freelance services marketplace are outside this implementation.

3. **Landing page and all public-facing copy**

   Update the existing homepage rather than undertaking a separate redesign. The first screen must make both audiences and both geographies clear.

   Proposed hero copy:

   > **FOR STUDENTS AND PROFESSIONALS**
   >
   > **Find your next opportunity.**
   >
   > Discover internships and jobs in Tunisia and around the world. Explore roles that match your skills, experience, and where you want to work.
   >
   > Primary action: **Explore opportunities**
   > Secondary action: **See how it works**

   Homepage content, in order:

   - Search with keyword, internship/job selection, and location. Submit to a real browse URL with a normal GET form.
   - Two equally visible audience cards: **For students** and **For professionals**, with specific examples and links to their sections.
   - Location shortcuts for Tunisia, remote work from Tunisia, and relocation opportunities abroad.
   - Recent internships and jobs rendered from the database. Use real inventory and sensible empty states. Do not invent opportunity counts, employer partnerships, or testimonials.
   - A short explanation of how Nextward works: discover, save, apply, and track. Explain that some applications happen on an employer or source website.
   - A trust section explaining sources, verification dates, and what “eligibility unknown” means.
   - An employer section: **Hire students and professionals**, with **Post an opportunity** as the action.
   - Relevant career guides for students and experienced candidates, then the existing footer.

   Navigation becomes **Opportunities**, **For students**, **For professionals**, and **Career guides**, plus employer, sign-in, and account actions. Keep mobile navigation equally clear.

   Create tailored `/for-students` and `/for-professionals` pages. Each has its own introduction, relevant filters, live listings, and guides. Student content includes first jobs, so it must not be implemented as an internship-only filter.

   Update the public layout, footer, About page, Contact explanations, signup and login text, onboarding, empty states, dashboard labels, saved-list labels, employer forms, application messages, notifications, page titles, social previews, and internal links. Retain internship wording where it describes an actual internship. General product wording should say “opportunities”, “candidates”, or “students and professionals”.

   Initial homepage title: **Internships and jobs in Tunisia and worldwide | Nextward**. Write useful page-specific descriptions rather than repeating this title everywhere. Update the website search metadata to use `/opportunities`.

4. **Public routes, redirects, and shared navigation**

   | URL | Purpose | Indexing policy |
   | --- | --- | --- |
   | `/` | Main landing page | Index |
   | `/for-students` | Student section and first-job guidance | Index |
   | `/for-professionals` | Professional section | Index |
   | `/opportunities` | Unified browse page | Index |
   | `/opportunities/internships` | Internship category | Index when useful and populated |
   | `/opportunities/jobs` | Job category | Index when useful and populated |
   | `/opportunities/tunisia` | Tunisia category | Index when useful and populated |
   | `/opportunities/remote-from-tunisia` | Roles explicitly permitting work from Tunisia | Index when useful and populated |
   | `/opportunities/relocation` | Roles with stated relocation assistance | Index when useful and populated |
   | `/opportunities/{id}-{slug}` | One canonical detail page for either type | Index while eligible for publication |
   | Arbitrary keyword/filter combinations | User search results | Noindex; exclude from sitemaps |
   | Dashboard, profiles, applications, saved lists, messages, settings, admin, auth | Account workflows | Noindex; authenticated where appropriate |

   Use a small, curated set of search landing pages. Additional field/city combinations require real demand, useful content, and sufficient inventory, rather than generating every possible combination.

   Redirect the old public `/internships` browse URL permanently to `/opportunities/internships`. Translate supported old query parameters and preserve meaningful filters and pagination. Redirect old numeric detail URLs to the corresponding canonical opportunity URL.

   Resolve details by stable ID, store a readable slug, and redirect an outdated or missing slug to the current canonical URL. Unknown IDs return 404. Register named categories before the detail route and constrain the detail parameter to avoid route collisions.

   Keep existing internal model names and authenticated create/edit/apply route parameters initially. Add an explicit public URL representation rather than changing `Internship::getRouteKey()` globally. Public links throughout the app use the new canonical URL. Private form submissions keep their established endpoints until there is a concrete need to change them.

   The existing authenticated `/students` resource already uses that path. The public `/for-students` page avoids a collision and preserves the private candidate-directory routes.

   `/jobs` does not become a second detail namespace. Students and professionals share the same opportunity URLs, saves, and application history.

5. **Server rendering with the existing Laravel, Vue, and Inertia stack**

   Use native Inertia 3 SSR with the current Laravel Vite integration and Vue server renderer. A separate frontend framework or prerendering service is unnecessary. The documented manual configuration supports this approach; the repository's Node 24 target satisfies the documented Node requirement. [Inertia SSR documentation](https://inertiajs.com/docs/v3/advanced/server-side-rendering)

   Implementation work:

   - Add a server entry at `/Users/alabaganne/Desktop/Projects/nextward/resources/js/ssr.js`; configure client and server builds in Vite and the package scripts.
   - Make the browser entry hydrate server HTML and keep shared component registration consistent between entries.
   - Keep browser-only code, including `window`, Axios bootstrap, video media queries, and observers, out of server execution. Use a stable initial video/poster state to avoid hydration differences.
   - Supply Ziggy route configuration and request location to the server renderer. Keep authentication and other request state scoped to each render. [Ziggy documentation](https://github.com/tighten/ziggy)
   - Server-render the homepage, audience pages, browse pages, details, curated categories, blog, About, and Contact. Exclude private app routes from SSR using the supported Inertia middleware controls.
   - Read public page data from Nextward's database. Public requests must never wait for an external scraper or ATS API.
   - Ensure headings, descriptions, cards, detail links, breadcrumbs, and pagination exist in the first HTML response. Avoid scroll-triggered reveal styles that leave essential server-rendered text hidden until JavaScript runs.
   - Keep one metadata owner using the same route-specific SEO data for full page requests and client navigation. Prevent duplicate or stale canonical, robots, title, and structured-data tags.
   - Run the SSR process under the production process supervisor alongside the queue worker and scheduler. Keep its endpoint private, restart it with matching deployment assets, and monitor render failures.
   - Check actual HTML regularly. A running Node process does not prove that pages are rendering correctly; Inertia can fall back to client rendering after an SSR failure.

   Visitors and crawlers receive the same substantive content. Server rendering improves accessibility to crawlers but does not guarantee indexing or rankings. [Google's JavaScript SEO guidance](https://developers.google.com/search/docs/crawling-indexing/javascript/javascript-seo-basics)

6. **Browse and opportunity-detail experience**

   The browse page uses server-side filtering, stable pagination, and URL-backed state. Every filter must survive refresh, sharing, pagination, and browser back navigation.

   Core filters: keyword; internship/job; field; country and city; onsite/hybrid/remote; experience level; and employment type. Add programme, duration, and start date for internships when known. Include posted date, language, compensation status, and salary/currency filters as their data becomes reliable. An absent salary means “Not specified”, not unpaid.

   Salary comparisons use a selected currency and pay period. Do not compare monthly TND figures directly with annual EUR or USD figures, or infer take-home pay from an advertised amount.

   Search title, employer name, and readable description from the first public release. Start with bounded database queries and indexes appropriate to the actual database. Add a separate search service only after measured limitations justify it. Limit long queries and excessive filter values.

   “Remote from Tunisia” includes explicitly eligible roles. Offer a separate, clearly labelled option to include roles whose eligibility is unknown. “Abroad with relocation” uses positive relocation evidence; visa sponsorship gets its own indicator. Worldwide browsing can include roles restricted to other countries, with those restrictions visible.

   Cards show title, employer, type, location, work arrangement, compensation with currency when known, source, and an honest date label. Sort by original posting date when available with a stable ID tie-breaker. Unknown posting dates can be ordered using discovery time, but label them “Added to Nextward”, never “Posted today”.

   Detail pages include:

   - Full, readable permitted description, responsibilities, requirements, and benefits as supplied.
   - Employer, location or multiple locations, work arrangement, experience, employment type, and internship-specific details.
   - Salary range, currency, and period only when supported by the source.
   - Original posting date, deadline or “No deadline listed”, last successful verification, source link, and application destination.
   - Separate remote eligibility, relocation assistance, and visa sponsorship information, with an explicit unknown state.
   - Save, share/copy link, apply, and report-issue actions. Reading and following an external apply link require no Nextward account.
   - A limited set of related active opportunities and useful category links.

   Use a normal link for external applications. Record a click as an outbound click, not a submitted application. “I applied” is a separate candidate action. A closed listing displays its status and disables apply.

   Retain keyboard access, labelled inputs, visible focus, mobile filter usability, and meaningful empty states. Browse, pagination, and reading an opportunity must remain usable with JavaScript disabled.

7. **Data changes and compatibility**

   Use additive migrations. Keep the `internships`, `students`, `likes`, and existing application identities and relationships. Do not rename the whole codebase to achieve new public labels.

   | Data area | Planned additions or changes |
   | --- | --- |
   | Opportunity classification | `kind`, seniority, employment types, internship programme; preserve existing field, duration, term, and work-type data |
   | Publication and application | Stable slug; native/imported origin; explicit internal/external application mode; application URL; draft/published/suppressed publication state separate from open/closed availability |
   | Dates | Nullable original `posted_at`; first discovery, last successful verification, and meaningful content-update timestamps; nullable `closing_at`; a precise UTC expiry timestamp for sources supplying one |
   | Location | Raw source location; normalized country codes and multiple locations; optional existing city relation; work arrangement and source-stated remote scope |
   | Eligibility | Allowed and excluded applicant locations; Tunisia eligibility; separate relocation and visa-sponsorship states; supporting source excerpts and extraction version |
   | Compensation | Decimal minimum/maximum, currency, period, and paid/unpaid/unspecified status; preserve and safely migrate current pay values |
   | Company | Name independent of an account, website/domain identity, source references, optional logo, country/location, nullable city |
   | Candidate profile | Career stage, headline, relevant experience, optional study details, professional skills, country/location, target countries and role types, work preferences, languages, and alert settings |

   Keep an imported UTC expiry timestamp precise. Existing native date-only deadlines continue to mean the end of that date in the defined application timezone. Use one availability rule across browse, details, apply, alerts, metadata, and sitemaps.

   Backfill existing genuine native opportunities as internships with internal application mode. Use their creation date as the original posting date only where that accurately represents publication. Leave uncertain legacy compensation currency and imported posting dates unknown. Do not guess USD or TND.

   Make the candidate's existing `field_id` and `city_id` optional, preserving valid existing values. Store international country/location information without forcing a Tunisian city or an invented field of study. Update company registration to accept the same geographical reality. For optional taxonomy relations changed by these migrations, use null-on-delete behavior so removing a city or field does not delete a profile. Add any needed field values, such as Other, idempotently without running the demo seeder.

   Supporting tables:

   - `import_sources`: one configured source or employer board per record, with provider, board identity, allowed domains, permission/reuse status, cadence, limits, enabled state, and recent health.
   - `import_runs`: per-run progress, completion status, request and item counts, created/updated/rejected/closed counts, timestamps, and actionable errors.
   - `opportunity_sources`: durable discovered items, source identity, source/apply URLs, normalized evidence, content hash, verification history, and nullable `internship_id` while staged. Enforce a unique source plus external ID, or a stable URL hash when no ID exists. Multiple source items may point to one opportunity.
   - Queue storage only if the required jobs table is missing. Use Laravel's existing database queue support initially.
   - Saved searches and alert delivery records when that phase is implemented.

   Store bounded extraction evidence only where source terms permit it. Avoid retaining unnecessary raw pages. Use existing relational columns for common filters and modest JSON fields for multi-location/source detail, rather than building a general-purpose ingestion framework.

   Company display access must work without `company->user`. Link employer identities using trusted domains, source company IDs, and explicit review where needed. Matching only a normalized company name is insufficient. Claiming a company later must not automatically convert imported listings to in-app application mode.

8. **Candidate accounts, employer posting, and application workflows**

   Registration offers **Find opportunities** or **Hire candidates**. Candidate onboarding asks for career stage, interests, skills, and location preferences; selecting Students or Professionals can prefill those interests. Make nonessential onboarding skippable. Do not silently assign Tunis or Software Engineering to a new professional.

   Retain the existing `Student` model and `student` morph value as internal compatibility details. Add a clear candidate authorization predicate and update the relevant policies, controllers, shared props, and navigation. Do not make a second professional profile table or give professionals a parallel application system. A candidate can update their career stage without losing saved roles or applications.

   The initial dashboard shows relevant opportunities using the candidate's selected filters, saved roles, and application history. Personalized ranking with match explanations, saved searches, and digest settings follows in phase 6. Show internships and jobs according to the candidate's interests. Professional profile fields must not require school-specific information.

   Employer create/edit forms add the opportunity type, job contract and seniority fields, country/location, work arrangement, currency, and relevant eligibility information. Internship duration and programme inputs appear when appropriate. Update employer dashboard counts, listing labels, applicant views, and notifications to support both types.

   Preserve native applications and messaging for jobs actually managed inside Nextward. Use an explicit application mode, not “company has a user”, to decide which workflow applies.

   For external application tracking, reuse `applications` with an explicit `channel` of internal or external, an applied date, and private candidate notes. Existing rows backfill to internal. Make the database cover-letter field nullable while retaining the existing required validation for native submissions.

   A dedicated external tracking action records the candidate's confirmation. It does not submit their CV, create an employer notification, or mark the application as employer-viewed. External statuses are visibly candidate-reported. Employer application queries, messages, status updates, and authorization must exclude external tracking records, even when the employer later claims the company. Audit both company relationship queries and the direct opportunity-applicant query. Candidate views can show both channels with clear labels, and candidate notes stay private in either channel.

   Move CV uploads to private storage, return authorized download links, and migrate existing files and attachment references without losing them. Access remains limited to the candidate and the authorized recipient of a native application. External apply never uploads or forwards a CV automatically. Verify old public files are removed after a successful, backed-up migration.

   Saved-role operations must accept all candidates, avoid duplicates, and remain available when a saved opportunity closes. Preserve existing account and application history through all migrations.

9. **Sources, scraping, and daily import operations**

   Use source-specific adapters and a shared normalization/publishing step inside Laravel. Prefer published feeds and employer ATS endpoints. Use permitted public-page scraping when structured feeds are unavailable.

   | Source | Verified finding | Planned treatment |
   | --- | --- | --- |
   | Tanitjobs | Sample public job pages contain JobPosting data; robots advertises a sitemap index. Its terms describe downloads for personal, noncommercial use. | Keep public reuse disabled pending an agreement or confirmed permission. Do not treat crawl access as a reuse licence. |
   | Keejob | Sample public job pages contain JobPosting data and a jobs sitemap exists. Site/database and posting rights are described in its terms. | Confirm permission for the intended reuse before enabling public import. No Nextward republication licence has been established in this review. |
   | Greenhouse | Public job-board API for individual employer boards | Configure and verify approved employer boards and their reuse conditions |
   | Lever | Published-job API with employer-specific endpoints | Configure and verify approved employer boards and their reuse conditions |
   | Ashby | Public job-posting API for individual boards | Configure and verify approved employer boards and their reuse conditions |
   | Direct Tunisian employer sites and agreed feeds | Coverage depends on each employer | Build a local source list and obtain the needed reuse terms; use these independently of aggregator agreements |

   Source references: [Tanitjobs terms](https://www.tanitjobs.com/terms-of-use/), [Tanitjobs robots](https://www.tanitjobs.com/robots.txt), [Keejob terms](https://www.keejob.com/terms-of-use/), [Keejob robots](https://www.keejob.com/robots.txt), [Greenhouse documentation](https://docs.greenhouse.io/job-board.html), [Lever documentation](https://github.com/lever/postings-api), and [Ashby documentation](https://developers.ashbyhq.com/docs/public-job-posting-api).

   These ATS endpoints are per-company boards, not a worldwide search API. Build a checked registry of Tunisian employers and worldwide companies. A proposed starting target is 30 to 50 international boards, adjusted to relevance, permissions, and operating cost. Give Tunisian and international source development comparable attention; do not enforce an artificial 50/50 listing quota.

   LinkedIn scraping is not a dependency. Its published policy prohibits the relevant scraping software. [LinkedIn policy](https://www.linkedin.com/help/linkedin/answer/a1341387/prohibition-of-scraping-software)

   Import flow:

   ```mermaid
   flowchart LR
       A[Approved source registry] --> B[Discover URLs and board records]
       B --> C[Durable pending items]
       C --> D[Fetch and extract]
       D --> E[Normalize and validate]
       E --> F[Resolve employer and duplicates]
       F --> G{Publishable?}
       G -->|Yes| H[Published opportunities]
       G -->|Needs review| I[Staging and moderation]
       H --> J[SSR pages and sitemaps]
       H --> K[Saved searches and alerts]
       H --> L[Scheduled rechecks]
       L --> E
   ```

   Implementation rules:

   - Read sitemap indexes recursively with limits, and follow the source's declared sitemap locations. Parse XML with external entities disabled. Parse HTML structurally, then decode JobPosting objects from JSON-LD arrays and `@graph` where present. Add source-specific handling when evidence requires it.
   - Follow each API's documented pagination and completeness rules. An incomplete response cannot serve as a complete inventory snapshot.
   - Persist all discovered pending items before applying fetch limits. Do not assume IDs are chronological or advance a high-water mark past unprocessed items.
   - Separate new discovery from refresh of existing records. Older but genuinely open roles remain eligible; a blanket 30-day posting cutoff is not a reliable availability rule.
   - Keep source identity unique per employer board. Provider-level IDs alone can collide between boards.
   - Fetch only allowed public domains, revalidate redirect destinations, reject private-network destinations, and bound response size, time, redirects, and XML nesting. Treat page content as data.
   - Use an identifiable user agent, source-specific request budgets, low per-host concurrency, conditional requests where supported, and backoff respecting `Retry-After`. Pause sources with repeated blocks or parser failures. Do not bypass access controls or CAPTCHAs.
   - Normalize descriptions into readable plain text with paragraphs and lists for the existing safe text rendering approach. Build safe structured-data HTML from that text. Never render arbitrary imported HTML or trust imported script content.
   - Preserve URLs and source facts, reject unusable apply destinations, and quarantine incomplete or suspicious records. Avoid publishing spam, obvious duplicates, fabricated employers, or thin excerpt pages.
   - Run a configurable dispatcher daily at **05:00 Africa/Tunis**. Dispatch bounded source work to the existing Laravel queue system. Lock the actual source run, since locking only the dispatcher does not prevent overlapping queued imports.
   - Allow independent source runs to fail independently. Record completeness, counters, and errors for every run. Use retries and idempotent writes so repeating a job does not duplicate a role.
   - Provide source selection, bounded batch sizes, a dry run, and a recheck command. A dry run must not write listings, cursors, delivery records, or run-state changes.

   A daily import is the default cadence, not a guarantee that the original source publishes fresh roles every day. Set source-specific refresh frequency after measuring volume and allowed request rates. Queue workers and the scheduler require supervised production processes. [Laravel scheduling](https://laravel.com/framework/docs/13.x/scheduling), [Laravel queues](https://laravel.com/framework/docs/13.x/queues)

10. **Classification, duplicate handling, and availability**

   Prefer structured facts, then context-aware extraction. Keep uncertainty visible.

   - Internship classification uses structured employment types and corroborated wording such as stage, PFE, internship, or apprenticeship. Use language-aware word boundaries. A title containing a casual use of “stage” is not enough. Full-time and internship may both apply, so employment type and opportunity kind remain separate.
   - Experience level, field, and skills use source evidence. Do not automatically treat every manager as the same seniority or every unknown role as software engineering. Ambiguous classification goes to review or an appropriate unknown/Other value.
   - Remote eligibility comes from an explicit hiring region or allowed applicant locations. A company describing itself as “global”, or a role mentioning an EMEA timezone, does not establish permission to work from Tunisia. Apply exclusions and negations before positive labels.
   - Relocation assistance and visa sponsorship are separate facts. “No sponsorship” must not become “Sponsorship available”. Keep the supporting excerpt and source so labels can be audited and corrected.
   - Worldwide roles restricted to other countries may remain in the global catalogue, but should not appear as eligible recommendations for someone in Tunisia.

   Deduplicate within a source by its stable item ID first. For cross-source duplicates, use a common employer identity plus a strong signal such as the same requisition ID or canonical application destination. Employer/title similarity only suggests a review candidate. Different offices and requisitions must not be merged automatically.

   Prefer the verified original employer source when appropriate, retaining all legitimate source references. If two published records are merged, preserve saves and application history, then redirect the retired public URL to the surviving one.

   Imports must preserve native employer-managed content and application mode. Stage conflicts rather than overwriting those records. For imported records corrected by moderation, track the overridden fields so the next refresh preserves the correction; suppression must also survive reimports. An explicit closure from the original employer takes precedence over stale aggregator copies.

   Availability rules:

   | Evidence | Action |
   | --- | --- |
   | A known expiry has passed, or a verified source explicitly says closed | Close that source listing; update the canonical role according to its remaining sources |
   | Verified source detail returns 404/410 | Recheck according to source policy and retire that source reference; do not mistake access blocks for deletion |
   | URL disappears from a sitemap | Queue a detail recheck; do not close from absence alone |
   | Item disappears from an authoritative, complete ATS snapshot | Confirm with subsequent healthy snapshots and a detail check when available; proposed default is two complete runs at least 24 hours apart |
   | Source times out, returns 403/429, changes layout, or returns a suspiciously empty feed | Mark the run unhealthy; pause bulk expiry and retry or investigate |
   | One of several sources disappears | Keep the opportunity if another authoritative source still confirms it open |
   | Verification becomes too old | Mark freshness uncertain and suppress from new alerts; after the source-specific grace period, suspend active publication pending verification |

   Use a source-specific freshness service target, initially two scheduled refresh cycles for normal daily sources. This is an operational threshold, not a claim that a role has closed. Detect unexpected mass removals and require review before applying them.

   Closed roles leave open browse results, recommendation results, active sitemaps, and JobPosting markup. Keep a useful 200-status history page with a clear closed label and noindex where users still need saved/application history. Retired pages with no ongoing use can return 410. Do not delete everything after 60 days or cascade-delete application history. A validated reopening reuses its identity and retains honest dates.

11. **SEO implementation and content quality**

   Make every approved, unique, active, publicly published opportunity eligible for indexing. Drafts, suppressed records, duplicates, expired roles, private data, and arbitrary filter permutations have separate rules. Search engines ultimately decide what they index.

   **Metadata and links.** Generate distinct titles, descriptions, canonical URLs, Open Graph data, and breadcrumbs from real page content. A job title can follow “Backend Engineer at Company, Tunis | Nextward”. Detail and category links must be ordinary crawlable anchors. Use one verified HTTPS production hostname and consistent internal URLs. Avoid putting the same role under both audience namespaces. [Canonical guidance](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls)

   **Pagination.** Each real pagination page has a crawlable link and its own canonical URL, including `page=2` where applicable. Preserve filters. Do not canonicalize every page to page one, and do not rely on infinite scrolling for discovery. Out-of-range pagination returns a real 404. [Pagination guidance](https://developers.google.com/search/docs/specialty/ecommerce/pagination-and-incremental-page-loading)

   **Filters.** Index only the curated landing pages. Keyword results and arbitrary filter combinations return noindex and stay out of sitemaps. Normalize redundant parameters and use canonical redirects only for genuinely equivalent URLs. Do not canonicalize different result sets to the unfiltered root. Bound parameter combinations and avoid making every filter permutation a crawlable link. A bot must be able to fetch a page to see its noindex rule. Invalid combinations return 404; valid user searches with no matches may return a helpful 200 noindex page. [Faceted navigation guidance](https://developers.google.com/crawling/docs/faceted-navigation)

   **JobPosting.** Emit this structured data only on an individual, active employment opportunity with the required supported facts. Its description and application path must be visible without login. Use the original posting date, real employer, and actual location rules. Include a known deadline and truthful salary. Fully remote roles can use TELECOMMUTE; hybrid roles do not qualify for that label. Supply known applicant-location restrictions. Remove the markup when a role expires or is suppressed. Omit `directApply` unless the actual flow meets its definition. Listings missing required facts can still have useful ordinary pages, but must not receive fabricated markup. [Google JobPosting requirements](https://developers.google.com/search/docs/appearance/structured-data/job-posting)

   Build a typed mapping for the fields Nextward actually has: title, description, employer, posting/expiry dates, identifier, employment types, location, applicant requirements, salary, and canonical URL. A job may have multiple locations or employment types. Escape JSON safely so imported strings cannot break out of the script element. The visible page and structured fields use the same normalized record. [JobPosting vocabulary](https://schema.org/JobPosting)

   **Sitemaps and robots.** Replace the 500-row cap with a sitemap index and chunked opportunity sitemaps. Include every eligible canonical URL, within protocol limits of 50,000 URLs and 50 MB uncompressed per sitemap. Separate static content and opportunity inventories where useful. `lastmod` reflects a meaningful public-content change, not every verification request. Publish consistent snapshots and remove closed records promptly. Resolve the conflict between the Laravel robots route and the [static robots file](/Users/alabaganne/Desktop/Projects/nextward/public/robots.txt), whose sitemap URL is currently hardcoded. [Sitemap guidance](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap)

   **Search engine notifications.** Set up Search Console, sitemap submission, and coverage monitoring for the real domain. Add Google's Indexing API for eligible JobPosting URL updates and removals, using an asynchronous delivery log, retries, and quota limits. Send events only after public state and caches are updated. Follow the API's removal rules and do not submit ordinary browse pages to this specialized API. This accelerates notification, not guaranteed indexing. [Indexing API setup](https://developers.google.com/search/apis/indexing-api/v3/quickstart), [API operations](https://developers.google.com/search/apis/indexing-api/v3/using-api)

   **Useful content.** Earn indexable pages through accurate structured facts, freshness, clear eligibility, usable search, and permitted descriptions. Add substantive guides around PFE applications, graduate hiring, remote eligibility, and professional job searching. Do not generate thousands of near-identical city pages or use bulk rewrites merely to make scraped text appear unique. [Google spam policies](https://developers.google.com/search/docs/essentials/spam-policies)

   **Languages.** Preserve original French, English, and Arabic descriptions with appropriate language information and Unicode handling. Deliver French versions of the main landing, audience pages, and selected curated categories as a dedicated localization increment, using `/fr/...` and the existing unprefixed English routes. Only genuinely localized equivalent pages get reciprocal hreflang and self-canonicals. Initially, each opportunity retains its single canonical detail URL and original description; do not create nominal translations with an unchanged main body. Additional translated detail URLs require a later, explicit translation workflow. [Localized-page guidance](https://developers.google.com/search/docs/crawling-indexing/localized-versions)

   **Performance.** Eager-load relationships, bound result and filter payloads, and index measured queries. Keep imported fetching outside requests. Optimize existing landing videos and images. Cache public query results where helpful, invalidating on changes and expiry; do not cache personalized HTML or Inertia responses across users. Preserve the correct variation between HTML and Inertia JSON. Target Core Web Vitals at the 75th percentile: LCP at most 2.5 seconds, INP at most 200 ms, and CLS at most 0.1. These are launch targets, not current measurements. [Web Vitals guidance](https://web.dev/articles/vitals)

12. **Matching, saved searches, and daily digests**

   Once the public inventory and both account experiences are reliable, add personalization using the same catalogue and eligibility rules.

   - Let candidates save a search with its validated filters, give it a name, and choose an alert frequency and timezone.
   - Build recommendations from declared skills, field, experience, interests, countries, and work arrangement. Explain useful matches in plain language, such as “Remote role open to Tunisia, with Laravel experience requested”. Show material gaps and unknown eligibility.
   - Apply hard restrictions before preference ranking. A high skill match does not override a stated country restriction.
   - Start with simple, explainable rules. Keep the initial implementation within Laravel and the existing database.
   - Send email digests only after opt-in, with unsubscribe, pause, and frequency controls. Include only still-active, publishable opportunities at delivery time.
   - Deduplicate by candidate, canonical opportunity, and alert purpose so cross-source copies and overlapping saved searches do not create repeated messages. Track deliveries and retries; use provider idempotency where available.
   - Label opportunities by original posting date versus discovery date honestly. A reimport or minor formatting edit does not make a role new again.
   - Keep preference changes, saved searches, and subscriptions private. No application or CV forwarding happens as a side effect of a match or alert.

   Add the digest section to the dashboard and settings only when it works. The landing page should advertise alerts only after this phase is available.

13. **Administration, reliability, and ongoing maintenance**

   Extend the existing admin access with a small source-and-import view: enabled sources, permission status, last complete run, queue age, extraction success, staged records, freshness, and errors. Include controls to pause a source, retry a failed batch, recheck an item, approve or suppress a listing, and correct or merge records with an audit trail.

   Treat zero new vacancies as a normal possible result. Alert on failed scheduled work, abnormal inventory changes, parser failures, persistent stale inventory, and queue delays. Track request volume and source limits so daily operation remains affordable and respectful of source capacity.

   Use the same audit and public-state rules for moderation changes as importer changes. A suppression or closure invalidates relevant cached results, removes active structured data and sitemap entries, and queues a search-engine update when applicable. Run a small expiry reconciliation schedule independently of the daily import; enforce expiry on reads as well, so a deadline does not wait for tomorrow's import. Cache lifetimes must not extend an opportunity past its known deadline.

   Track SSR availability, first-response HTML content, scheduler execution, worker health, failed jobs, outgoing email failures, and source freshness. Back up the database before migrations and retain a clear procedure for disabling imports without disabling the existing app.

   Add a source/reporting explanation and update the existing privacy and terms content or linked pages to accurately describe imports, external applications, profile information, and optional emails. Provide a practical route for employers to report incorrect or removed roles. Do not expose candidate data through public resources, imported employer identities, or public caching.

   Company claiming is a later increment: verify authority through a domain or another reviewed proof, preserve provenance, and explicitly decide which listings the employer can manage. A claim never silently changes application destinations or reveals candidates' external tracking notes.

14. **Implementation order and release gates**

   | Phase | Work included | Completion gate |
   | --- | --- | --- |
   | 0. Establish the baseline | Confirm `main`, preserve unrelated changes, use the repository's Node target, restore dependencies from committed lockfiles, establish a disposable local database, record current checks and production hosting capabilities | Existing app runs locally; SSR hosting, queue/scheduler operation, source access, and production hostname are understood |
   | 1. Extend the data safely | Add opportunity fields, import/source records, company fallback identity, candidate compatibility, shared availability rules, backfills, and targeted indexes | Existing IDs, saves, native applications, and company accounts continue to work; unknown source facts remain unknown |
   | 2. Establish public rendering and URLs | Native Inertia SSR, safe browser/server bootstrap, canonical public routes, old public redirects, route-specific SEO metadata, robots ownership | Raw HTML contains visible public content and links; client navigation and private routes remain correct |
   | 3. Build and pilot imports | Approved source registry, adapters, durable discovery, queue jobs, normalization, classification, deduplication, rechecks, staging, and admin health | A small permitted Tunisian and worldwide sample completes repeated runs without duplicate creation or false closures |
   | 4. Complete both audience experiences | Landing page, Students and Professionals pages, browse/detail UI, candidate onboarding/profile/dashboard, employer job posting, external tracking, and private CV handling | Students and professionals can complete their relevant journeys; internal and external applications behave accurately |
   | 5. Launch the public finder | Publish approved inventory of both types, complete SEO metadata/schema/sitemaps, production SSR/worker supervision, Search Console and eligible indexing notifications, mobile/performance checks | Both opportunity types are readable without JavaScript, discoverable through links, correctly classified for indexing, and operationally monitored |
   | 6. Add personalized discovery | Saved searches, matching reasons, opt-in digests, delivery deduplication, and settings | Recommendations obey eligibility; subscriptions and unsubscribe work; repeated import runs do not cause repeated alerts |
   | 7. Expand useful coverage | French public landing/category content, additional approved Tunisian and international sources, targeted career guides, verified company claiming, measured search improvements | Expansion maintains freshness, useful page quality, source permissions, and manageable operating cost |

   Staging can exercise each phase before public release. The first release branded as the new opportunity finder includes both students and professionals. Employer-account functionality and existing saved/application history remain usable throughout the transition.

   Phase 0 uses `composer install` and `npm ci` against committed lockfiles when implementation starts. It does not require changing branches, upgrading frameworks, deleting lockfiles, or assuming a particular passing-test count. Review migrations and use a disposable database for development. The existing demo seeder clears data and must not be run against an existing user database.

   Keep migrations additive and separate data backfills from destructive changes. Validate company identity and duplicate cleanup before adding unique constraints. Use transactions for canonical-record merges. A rollout can disable a new source or suspend new publication while retaining existing native functionality and data.

15. **Expected code and configuration touchpoints**

   These are the implementation areas identified by the original plan.

   | Area | Existing starting points and expected additions |
   | --- | --- |
   | Homepage and public positioning | [Welcome](/Users/alabaganne/Desktop/Projects/nextward/resources/js/Pages/Welcome.vue), [Public layout](/Users/alabaganne/Desktop/Projects/nextward/resources/js/Layouts/Public.vue), [About](/Users/alabaganne/Desktop/Projects/nextward/resources/js/Pages/About.vue), [Contact](/Users/alabaganne/Desktop/Projects/nextward/resources/js/Pages/Contact.vue); add audience pages using existing page conventions |
   | Public browse and details | [Internships pages](/Users/alabaganne/Desktop/Projects/nextward/resources/js/Pages/Internships), [RoleCard](/Users/alabaganne/Desktop/Projects/nextward/resources/js/Components/Internship/RoleCard.vue), [Pagination](/Users/alabaganne/Desktop/Projects/nextward/resources/js/Components/Ui/Pagination.vue), [InternshipController](/Users/alabaganne/Desktop/Projects/nextward/app/Http/Controllers/InternshipController.php), [InternshipResource](/Users/alabaganne/Desktop/Projects/nextward/app/Http/Resources/InternshipResource.php) |
   | Routes and SEO | [web.php](/Users/alabaganne/Desktop/Projects/nextward/routes/web.php), [app.blade.php](/Users/alabaganne/Desktop/Projects/nextward/resources/views/app.blade.php), [robots.txt](/Users/alabaganne/Desktop/Projects/nextward/public/robots.txt); add focused sitemap and SEO handling as the routes grow |
   | SSR and build | [app.js](/Users/alabaganne/Desktop/Projects/nextward/resources/js/app.js), [bootstrap.js](/Users/alabaganne/Desktop/Projects/nextward/resources/js/bootstrap.js), [LoopVideo](/Users/alabaganne/Desktop/Projects/nextward/resources/js/Shared/LoopVideo.vue), [Vite config](/Users/alabaganne/Desktop/Projects/nextward/vite.config.js), [package scripts](/Users/alabaganne/Desktop/Projects/nextward/package.json), [Inertia middleware](/Users/alabaganne/Desktop/Projects/nextward/app/Http/Middleware/HandleInertiaRequests.php); add the SSR entry and deployment process definition |
   | Models and migrations | [Internship](/Users/alabaganne/Desktop/Projects/nextward/app/Models/Internship.php), [Company](/Users/alabaganne/Desktop/Projects/nextward/app/Models/Company.php), [Student](/Users/alabaganne/Desktop/Projects/nextward/app/Models/Student.php), [User](/Users/alabaganne/Desktop/Projects/nextward/app/Models/User.php), [Application](/Users/alabaganne/Desktop/Projects/nextward/app/Models/Application.php), [migrations](/Users/alabaganne/Desktop/Projects/nextward/database/migrations); add source/run/item and later saved-search/delivery models |
   | Candidate and employer workflows | [registration controller](/Users/alabaganne/Desktop/Projects/nextward/app/Http/Controllers/Auth/RegisteredUserController.php), [registration page](/Users/alabaganne/Desktop/Projects/nextward/resources/js/Pages/Auth/Register.vue), [StudentRequest](/Users/alabaganne/Desktop/Projects/nextward/app/Http/Requests/StudentRequest.php), [InternshipRequest](/Users/alabaganne/Desktop/Projects/nextward/app/Http/Requests/InternshipRequest.php), [CompanyController](/Users/alabaganne/Desktop/Projects/nextward/app/Http/Controllers/CompanyController.php), [DashboardController](/Users/alabaganne/Desktop/Projects/nextward/app/Http/Controllers/DashboardController.php), profile pages, and relevant authorization policies |
   | Saves, applications, and attachments | [LikeController](/Users/alabaganne/Desktop/Projects/nextward/app/Http/Controllers/LikeController.php), [ApplicationController](/Users/alabaganne/Desktop/Projects/nextward/app/Http/Controllers/ApplicationController.php), [ApplicationPolicy](/Users/alabaganne/Desktop/Projects/nextward/app/Policies/ApplicationPolicy.php), [applications pages](/Users/alabaganne/Desktop/Projects/nextward/resources/js/Pages/Applications), company applicant queries, notification recipients, and private download handling |
   | Navigation and account wording | [AppSidebar](/Users/alabaganne/Desktop/Projects/nextward/resources/js/Layouts/AppSidebar.vue), [AppTopbar](/Users/alabaganne/Desktop/Projects/nextward/resources/js/Layouts/AppTopbar.vue), [Dashboard](/Users/alabaganne/Desktop/Projects/nextward/resources/js/Pages/Dashboard.vue), [Settings](/Users/alabaganne/Desktop/Projects/nextward/resources/js/Pages/Settings.vue), and related contextual labels |
   | Imports and operations | [console routes](/Users/alabaganne/Desktop/Projects/nextward/routes/console.php), queue configuration, new focused import classes, commands/jobs, source configuration, admin pages, and supervised production processes |
   | Documentation and career content | [README](/Users/alabaganne/Desktop/Projects/nextward/README.md), [FUTURE_PLANS](/Users/alabaganne/Desktop/Projects/nextward/FUTURE_PLANS.md), [blog configuration](/Users/alabaganne/Desktop/Projects/nextward/config/nextward_blog.php); document source onboarding, SSR/worker operations, moderation, and deployment |

   Keep edits limited to these feature requirements. There is no planned framework rewrite, blanket model rename, general UI redesign, new search cluster, browser-scraping service, or unrelated refactor.

16. **Acceptance checklist for implementation**

   These checks define the intended result. Local implementation verification is recorded above. Adding or editing repository tests requires the user's explicit instruction; production release checks remain separate from isolated local verification.

   | Area | Required evidence |
   | --- | --- |
   | Positioning | Homepage visibly says students and professionals; both paths work on mobile and desktop; general navigation says opportunities |
   | Public rendering | Raw HTTP responses contain the homepage heading, listing titles and links, full detail description, metadata, and appropriate structured data; JavaScript-disabled browsing works |
   | SSR isolation | Consecutive requests from different users do not share account state; private routes remain private; SSR failure monitoring catches a client-rendering fallback |
   | Search and filters | Employer and description searches work; filters combine correctly; refresh, pagination, and back navigation preserve state; remote-from-Tunisia excludes known restrictions |
   | URLs and SEO | Old public URLs redirect once to their intended destination; wrong slugs redirect; missing IDs and invalid pages return 404; page-two canonical remains page two; arbitrary search URLs are noindex |
   | Structured data | Representative native, imported, local, multi-location, remote, and internship detail pages pass relevant validators; list pages have no JobPosting; unknown facts are omitted; visible text agrees with markup |
   | Sitemaps and removal | All eligible roles appear across chunked sitemaps, including beyond the old 500 limit; closed/suppressed roles disappear; robots points to the correct domain; expiry updates caches and notifications |
   | Import correctness | Repeat imports produce no duplicates; a limited batch leaves pending discoveries intact; JSON-LD arrays and multiple employment types work; missing deadlines and currencies remain unknown |
   | Source failures | A timeout, 403/429, malformed response, partial feed, suspiciously empty feed, or missing sitemap URL does not close valid inventory |
   | Eligibility and duplicates | Negative sponsorship text remains negative; timezone language does not imply eligibility; separate requisitions remain separate; a merged role preserves saves and application history |
   | Candidate compatibility | Existing students retain accounts and history; professionals register without fake study/location defaults; both can save and use supported application flows |
   | External applications | Apply opens the verified external destination; click and submitted status remain distinct; manual tracking cannot notify an employer or expose private notes |
   | Attachments and ownership | CV URLs require authorization; unrelated candidates and unclaimed companies cannot access them; migrated attachment references still work |
   | Alerts | Opt-in, frequency, eligibility, unsubscribe, retries, and duplicate suppression behave correctly; expired roles are excluded at send time |
   | Operations and performance | Production SSR, workers, scheduler, source health, and failure handling are observable; key public journeys meet the agreed performance targets or have measured, actionable gaps |

   Keep a representative set of permitted source examples for review, covering French/English text, negations, multiple countries, unknown dates, and closure scenarios. Verify both full HTML requests and Inertia navigation; an HTTP 200 or a passing server-side feature check alone does not demonstrate readable public HTML.

17. **What changes from Claude's proposal**

   Keep the useful core: daily sourcing, Tunisia plus worldwide coverage, two audience sections, an additive `kind` field, native employer posts, outbound apply for imported roles, and phased personalization.

   The final decisions are:

   - Stay on `main`, and use the committed dependencies without a branch switch or broad upgrade.
   - Launch the new public finder for both audiences with one `/opportunities` catalogue and one canonical detail namespace.
   - Include the homepage, navigation, account, profile, dashboard, employer, and application changes in the implementation scope.
   - Make real SSR a public launch requirement, alongside metadata and structured data.
   - Enable sources only with a basis for the intended reuse; aggregator access is a dependency to resolve, not assumed permission.
   - Use durable pending work and complete-run evidence instead of relying on increasing IDs or a fixed “newest 400” cutoff.
   - Verify closures and eligibility using evidence; preserve unknown values and distinguish relocation from visa sponsorship.
   - Preserve provenance across duplicates and keep external application mode explicit after company claiming.
   - Replace arbitrary age-based deletion and capped sitemaps with a documented availability lifecycle and complete eligible URL coverage.
   - Include search quality, private CV access, operational monitoring, and measurable release checks in the relevant phases.

   Success is a growing, fresh, useful catalogue that people can find in search and successfully use. Track active verified inventory by geography/type, stale and duplicate rates, search-to-detail and detail-to-apply activity, saved-role use, opt-in digest engagement, and organic search impressions/clicks. Evaluate indexing and content usefulness rather than treating raw imported URL counts as success.
