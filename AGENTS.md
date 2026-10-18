# Nextward

Shared notes for anyone working on this repo, people or coding tools. `CLAUDE.md` and the Cursor rule point here.

## What Nextward is

- A job board for **students and professionals**: internships, PFE projects, graduate roles and jobs, in **Tunisia and worldwide**.
- Companies post internships and jobs, review applications and message candidates. Admin is an internal role, not an audience.
- The public site is in **English and French** (French pages live under `/fr`).
- Listings come from company posts and from approved sources imported every day (see `README.md`).

## Decisions

- **Name:** the product is **Nextward**. It was called Internly before (and Stagi.tn before that). Don't use the old names in code, copy or docs. Some asset packs say "Nextwand"; that is a typo, not a new name.
- **Scope:** Nextward is no longer students-and-internships only. Copy, features and docs must cover both students and professionals, and both internships and jobs.
- **One candidate role:** everyone looking for work is a **Candidate** ("Candidat"). There is no student, graduate or professional choice anywhere; experience and preferences say the rest. Accounts are candidates, companies or admins.
- **Names in the code:** listings are `Opportunity` (table `opportunities`, `kind` is `internship` or `job`) and people looking for work are `Candidate` (table `candidates`, `userable_type` is `candidate`). The old `Internship`/`Student` names and tables were renamed in October 2026 (`2026_10_18_170000_rename_internships_and_students`); old `/internships/...` and `/students/...` links redirect. Use "opportunity" and "candidate" in code and copy; "internship" stays only as a listing kind.
- **Honest content:** no made-up numbers, testimonials, live activity or company verification.
- **Listings in two languages:** every listing has an English and a French version. The original text and its language are never changed; AI translates into the other site language (both, when the original is in another language) without rewriting or "improving" the employer's text. Old listings keep working as they are and are translated over time, newest first, by `opportunities:translate`; closed listings are not translated. Details in `SEO.md`.
- **Privacy:** fonts are self-hosted in `resources/fonts/`. Don't add outside fonts, scripts or trackers without updating the privacy policy (`resources/js/Pages/Privacy.vue`).
- **Company logos:** pages only show logos stored on Nextward. Outside logo URLs are downloaded by `companies:logos` and never sent to the browser (`README.md#local-setup`).
- **Design:** flat surfaces, no shadows or glows, and no one-sided accent borders (top stripes, left bars); use tints or solid fills instead. This holds for emails too. Details in `design/DESIGN.md`.
- **One home page:** the home page speaks to students and professionals alike; there are no separate audience pages (old links redirect home). About explains the mission, what is free and the paid features to come.
- **Public pages:** one design for every public page: navy header, dark navy hero with the cartoon mascots and small sparkles, navy footer, and white, cool grey (`#F6F8FC`) or ice blue (`#EEF4FF`) sections in between. No cream, beige or ivory. Cobalt for actions, flat cobalt closing banners. Details in `LANDING_PAGE_DESIGN.md`.
- **Public images:** cartoon mascots and the desk scene from `public/nextward-assets/mascots/`; generated photos from `landing-light-v1/` only as article images. Never present the photos as real users, staff, offices or partners.
- **Writing:** plain, everyday words in copy, docs, commits and code comments. Comments are short and rare.
- **Email:** sent through Resend from `nextward@alabaganne.com` until a Nextward domain is verified. Every email uses the shared layout in `resources/views/vendor/mail` (new logo, white card with no borders, solid blue card for quoted text, navy button). Text people type is shown as plain text. Details in `README.md#email`.
- **Email verification:** required before applying, messaging, posting or editing roles, saving searches with alerts, claiming a company and asking for a public profile. Browsing and the dashboard work without it.
- **Public candidate profiles:** private by default. A candidate asks from Settings, an admin approves under Candidates, and only then the profile shows at `/candidates`. It shows name, headline, field, location, experience, bio, skills, languages, website and LinkedIn, never email, phone or resume. These pages are `noindex` and stay out of the sitemap. The candidate can go private at any time; editing an approved profile keeps it live.
- **Admin accounts** are candidates too: each admin has a candidate profile (made by `nextward:make-admin`, or the first time they open Settings), so they fill in the same profile and resume as anyone else.
- **Tunisian boards:** Tanitjobs and Keejob stay on automatic publishing by the owner's choice; checking their terms is the owner's call.
- **Error monitoring:** none for now (Laravel logs only), by the owner's choice.
- **What is free:** browsing, saving, applying, alerts, tracking and filling a profile from a resume are free for everyone. The tailored CV and cover letter ("kit") is the future paid feature: credit packs, one credit per role with a few rewrites, one free credit for every candidate. Everything is free for now; billing stays off (`BILLING_ENABLED=false`) until the owner sets prices and opens merchant accounts. Never block a free feature behind credits.
- **Payments:** Konnect and Flouci for Tunisian payers (TND), Lemon Squeezy for international payers (USD). Payments are always checked with the provider before credits are added. Details in `README.md#credits-paid-later-off-for-now`.
- **AI output:** the AI only uses facts from the candidate's own profile and resume, and people always review and edit before anything is saved or sent.

## Stack

Laravel 13 (PHP 8.3+), Inertia 3 with SSR, Vue 3, Tailwind CSS 4, Vite 8, Node 24. Run `php artisan test` and `npm run build` before you commit.

## Git workflow

- Commit straight to `main`. Do not create feature branches.
- Do not open pull requests or ask for PR reviews. This is a solo project and the app is still in development.
- This rule stays until the owner says to move to a `develop` branch.

## Keep the docs up to date

When the owner makes a decision of any kind (product, logic, naming, data, design, legal, workflow), update the docs it affects **in the same commit**, without being asked. Put lasting decisions and rules in this file, and fix the matching doc below. If a doc becomes wrong and is only kept as history, say so at its top.

| Doc | Covers |
|---|---|
| `AGENTS.md` | Product decisions and working rules (this file) |
| `README.md` | Setup, imports, AI, day-to-day commands |
| `design/DESIGN.md` | Workspace design language |
| `design/INTEGRATION.md` | How the front end is wired |
| `LANDING_PAGE_DESIGN.md` | Public landing and sign-in pages |
| `SEO.md` | Blog, SEO and links |
| `DEPLOY.md`, `VPS_DEPLOYMENT.md` | Deployment |
| `FUTURE_PLANS.md` | Open roadmap |
| `VERDICT.md`, `MONETIZATION_STRATEGY.md`, `MARKETING_PLAN.md` | Business notes |
| `database/seeders/data/SOURCES.md` | Seeded listings and import sources |
| `LANDING_PAGE_IMPLEMENTATION_PROMPT.md`, `OPPORTUNITY_FINDER_IMPLEMENTATION_PLAN.md`, `PUBLIC_PAGES_REDESIGN_PROMPT.md` | Task briefs (history) |
