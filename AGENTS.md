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
- **Old names in the code:** the `internships` table and `Internship` model hold every listing (`kind` is `internship` or `job`). The `students` table and `Student` model hold every candidate (`career_stage` is `student`, `graduate` or `professional`). Keep these names for compatibility; use "opportunity" and "candidate" in new copy.
- **Honest content:** no made-up numbers, testimonials, live activity or company verification.
- **Privacy:** fonts are self-hosted in `resources/fonts/`. Don't add outside fonts, scripts or trackers without updating the privacy policy (`resources/js/Pages/Privacy.vue`).
- **Company logos:** pages only show logos stored on Nextward. Outside logo URLs are downloaded by `companies:logos` and never sent to the browser (`README.md#local-setup`).
- **Design:** flat surfaces, no shadows or glows, and no one-sided accent borders (top stripes, left bars); use tints or solid fills instead. This holds for emails too. Details in `design/DESIGN.md`.
- **Writing:** plain, everyday words in copy, docs, commits and code comments. Comments are short and rare.

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
| `LANDING_PAGE_IMPLEMENTATION_PROMPT.md`, `OPPORTUNITY_FINDER_IMPLEMENTATION_PLAN.md` | Task briefs (history) |
