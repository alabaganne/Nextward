# Nextward SEO and AI visibility

The goal is for Nextward to show up on Google (most searches from Tunisia are in French) and for AI assistants (ChatGPT, Claude, Perplexity, Gemini, Copilot) to know Nextward and recommend it.

## What the site already does

- **Server-side rendering (SSR).** Crawlers and AI bots get full HTML without running JavaScript, so the SSR process must stay running in production.
- **Head tags on every public page:** title, description, canonical, hreflang (EN/FR), Open Graph and Twitter tags with a share image (`public/brand/nextward/og-default.png`).
- **Structured data:**
  - `Organization` and `WebSite` on the home page
  - `AboutPage` on About
  - `Blog` on the blog index
  - `BlogPosting`, `BreadcrumbList` and `FAQPage` on posts
  - `JobPosting` on opportunities
  - `Organization` on company pages
- **Sitemaps** at `/sitemap.xml`: pages, blog posts (with last-updated dates), company pages and opportunities.
- **`/robots.txt`:** lets every bot, AI bots included, read public pages, and blocks the private areas.
- **`/llms.txt`:** a plain-text summary of Nextward for AI tools, with links to every guide. Each guide also has a Markdown copy at `/blog/{slug}.md`.
- **RSS feeds:** `/blog/feed.xml` and `/fr/blog/feed.xml`.
- **IndexNow:** `php artisan seo:indexnow` runs every hour and tells Bing about new or changed pages. ChatGPT search and Copilot use Bing's index.
- **Listings in English and French:** each listing keeps its original title and text, and AI writes the missing English or French version (`internship_translations`). The page in the original language always shows the original. The page in the other language shows the translation, with the original in an "Original listing" fold-out.
  - Until a translation exists, that page shows the original text and is `noindex`. It is also left out of hreflang, the sitemap and IndexNow, so Google never sees two copies of the same text.
  - The translated page has its own slug (`/fr/opportunities/12-developpeur-backend`). Old links redirect to it with a 301.
  - Title, meta description, `JobPosting` and breadcrumb follow the page language. The sitemap lists each language version with `xhtml:link` hreflang alternates.
  - The Google Indexing API gets the page in the listing's own language.
- **Public company pages:** `/companies/{id}-{slug}`. Pages with no open roles and a short "about" text are kept out of Google (`noindex`).
- **Companies directory:** `/companies` and `/fr/companies`, in the pages sitemap. Only the plain list is indexed; searches, field filters and "All companies" are `noindex`.
- **Public candidate profiles:** `/candidates` and `/candidates/{id}-{slug}` (also under `/fr`). They hold personal data, so they are always `noindex` and are not in the sitemap.

## Writing a blog post

Posts are Markdown files:
- English: `resources/content/blog/en/{slug}.md`
- French: `resources/content/blog/fr/{slug}.md`, with a French slug

Copy an existing file to start. Front matter fields:

| Field | What it is for |
|---|---|
| `title` | Page title and H1. Put the main keyword in it and keep it under 65 characters. |
| `description` | Google snippet, 120–155 characters. |
| `excerpt` | Short text shown on the blog list. |
| `category`, `audience` | Where the post is listed. `audience` is `students`, `professionals`, `companies` or `all`. |
| `published_at`, `updated_at` | Change `updated_at` every time you edit the content. |
| `translation` | Same value in the EN and FR files, so they link to each other (hreflang and the language switch). |
| `draft: true` | Shows the post only on your local machine. Remove it to publish. |
| `links` | Opportunity pages shown under the post. |
| `faq` | Questions and answers shown at the end, also sent as FAQ structured data. |
| `cover`, `cover_alt` | Optional 1200×630 image, also used as the share image. The smaller page photos in `Components/Blog/guideImages.js` are only shown on the site, not used as share images. |

Rules that help both Google and AI tools:
- Answer the question in the first 2–3 sentences.
- Phrase H2s the way people search ("Quand chercher son stage PFE ?").
- Use short paragraphs, lists, and a table when it helps.
- Link to 2–3 other guides and to the matching `/opportunities/...` page.
- Give real numbers only with a source, and list sources at the end.
- Use simple words, and write French for French readers rather than translating word for word.

After deploying new or edited posts, run `php artisan seo:indexnow` (the hourly job also picks them up).

## The 10 new draft posts (review before publishing)

Each post exists in EN and FR with `draft: true`:

| Topic | FR file | EN file |
|---|---|---|
| Stage PFE (main guide) | `stage-pfe-tunisie` | `pfe-internship-tunisia` |
| SIVP | `contrat-sivp-tunisie` | `sivp-contract-tunisia` |
| Stage d'été | `stage-ete-tunisie` | `summer-internship-tunisia` |
| CV | `cv-pour-stage` | `internship-cv` |
| Lettre de motivation | `lettre-de-motivation-stage-pfe` | `internship-cover-letter` |
| Entretien | `entretien-de-stage-questions` | `internship-interview-questions` |
| Rapport / soutenance | `rapport-de-stage-soutenance-pfe` | `internship-report-pfe-defense` |
| Stage informatique | `stage-informatique-tunisie` | `it-internship-tunisia` |
| Premier emploi | `premier-emploi-jeune-diplome-tunisie` | `first-job-after-graduation-tunisia` |
| Télétravail | `teletravail-depuis-la-tunisie` | `remote-jobs-from-tunisia` |

To review a post:
1. Read it locally at `/fr/blog/{slug}`.
2. Check every fact and source, especially in the SIVP post.
3. Remove `draft: true`, then deploy.
4. Once a post is live, link to it from older guides. For example, `conseils-candidature-stage` should link to `lettre-de-motivation-stage-pfe` once that post is published.

## Launch checklist (one time)

- [ ] Set the real domain with HTTPS and set `APP_URL`. All canonical tags, sitemaps and structured data use it.
- [ ] Keep the SSR process (`nextward-ssr` in Supervisor) running, and check that `curl https://DOMAIN/fr/blog` shows a `<title>` and `og:image` in the HTML.
- [ ] **Google Search Console:** add a Domain property and submit `https://DOMAIN/sitemap.xml`.
- [ ] **Bing Webmaster Tools:** import the site from Search Console and check IndexNow in its dashboard.
- [ ] Set `INDEXNOW_KEY` in `.env` (any random 32-character string), then run `php artisan seo:indexnow --all` once.
- [ ] If Cloudflare is in front of the site, turn off "Block AI bots" / AI Crawl Control blocking. It is on by default for new zones.
- [ ] Set `NEXTWARD_SOCIAL_LINKS` in `.env` (comma-separated profile URLs). They go into the `Organization` `sameAs` field, which links the brand across the web.
- [ ] Test a post, an opportunity and a company page with the Rich Results Test (https://search.google.com/test/rich-results) and validator.schema.org.

## Brand presence (what makes AI tools know and recommend Nextward)

AI assistants mostly repeat what many trusted pages say about a brand.

- [ ] **Use the same name, logo and one-line description everywhere:** LinkedIn company page, Facebook, Instagram, Crunchbase, Google Business Profile (if you have an address). Use the same description as `/about`.
- [ ] **Wikidata:** add an item once Nextward has a few independent press mentions.
- [ ] **University career offices and student clubs** (ESPRIT, INSAT, ENIT, IHEC, FST, ISG, ENSI, IEEE and Enactus clubs): ask for a link on their "useful links" or career pages.
- [ ] **Tunisian tech and business press:** pitch a launch story, then a monthly "internship and job market" data story taken from Nextward listings.
- [ ] **"Best job / internship sites in Tunisia" roundup articles:** ask the authors to add Nextward.
- [ ] **Reddit (r/Tunisia), Facebook student groups, LinkedIn:** give honest, helpful answers that link to a guide when it truly answers the question. No spam.
- [ ] **Each new guide:** share it on LinkedIn and Facebook in French.

## Measuring

**Every week:**
- Search Console: queries, clicks, pages indexed.
- Bing Webmaster Tools: the same.

**Every month:**
- **AI referrals:** visits from chatgpt.com, perplexity.ai, claude.ai, gemini.google.com and copilot.microsoft.com in analytics.
- **AI bot visits:** count them in the nginx logs:
  ```bash
  grep -Ei "GPTBot|OAI-SearchBot|ChatGPT-User|ClaudeBot|Claude-SearchBot|Claude-User|PerplexityBot|Google-Extended|Bingbot" /var/log/nginx/access.log | wc -l
  ```
- **AI answer check:** ask these 5 questions in ChatGPT, Claude, Perplexity, Gemini and Copilot, and write down whether Nextward is named:
  1. Où trouver un stage PFE en Tunisie ?
  2. Meilleurs sites pour trouver un stage en Tunisie
  3. Best job sites in Tunisia
  4. Remote jobs open to candidates in Tunisia
  5. C'est quoi Nextward ?

## Next steps (later)

- **City and field pages** (e.g. "Stage informatique Tunis"): indexable only when there are 5 or more open roles, each with its own intro text.
- **A monthly "Tunisia internship and job market" page** built from Nextward listings: counts by field and city, remote share, how many listings show pay. Original numbers are what journalists and AI tools quote.
- **Share images per post and per opportunity.**
