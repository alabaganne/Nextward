# Implementation prompt for Claude

> Historical implementation brief, written before Nextward added professionals and jobs. Its notes on audiences (students only) and the old Internly site are out of date; see `AGENTS.md`. The current visual direction is documented in `LANDING_PAGE_DESIGN.md`: open layouts without shadows, a flat hero illustration, a dark employer section, and interface icons from `@heroicons/vue`. Follow that document where this earlier brief differs.

Improve Nextward's landing page so it feels polished, human, and engaging. Integrate the supplied logo and selected illustrations, add restrained animation and motion graphics, and remove fabricated data and unsupported claims from the public website. Implement and verify the changes; do not stop at a design proposal.

## Context and working rules

- This is an existing Laravel, Inertia, Vue 3, Tailwind, and Vite project. Inspect the actual code and installed versions before changing anything.
- Students and companies are the two primary audiences. Admin is an internal role, not a third marketing audience. Do not promote career-team features unless they are implemented and deliberately supported.
- The hosted site at https://internly.alabaganne.com/ is a visual reference. The local landing page currently differs from it; build on the current checkout without discarding existing work to recreate an older deployment.
- Start with `resources/js/Pages/Welcome.vue`, `resources/js/Shared/BrandLogo.vue`, `resources/js/Layouts/Public.vue`, `resources/views/app.blade.php`, the home route, and existing public-page components. Read repository instructions and preserve unrelated uncommitted changes.
- Keep the code simple. Do not mention Codex or ChatGPT in commit messages or PRs. Do not deploy, reset the database, run the seeder, or delete production records as part of this task.

## Visual direction and new logo

Keep Nextward's identity: royal blue `#2563EB`, navy `#0A1738`, pale blue-white `#F6F8FC`, rounded cards, generous whitespace, and soft shadows. The live site uses Space Grotesk headings and Inter body text. Reuse the existing font-loading approach, avoiding duplicate downloads. Improve hierarchy, spacing, and section rhythm rather than introducing a different brand.

Use the new logo from `public/brand/nextward/`:

- `/brand/nextward/logo.svg`: blue symbol and navy outlined wordmark for light surfaces.
- `/brand/nextward/logo-dark.svg`: light blue symbol and white wordmark for dark surfaces.
- `/brand/nextward/mark.svg` and `/brand/nextward/mark-light.svg`: symbol only for compact layouts.
- `/brand/nextward/wordmark.svg` and `/brand/nextward/wordmark-white.svg`: wordmark only for stacked logo layouts.
- `/brand/nextward/logo-mono.svg` and `/brand/nextward/logo-white.svg`: monochrome alternatives.
- `/brand/nextward/favicon.svg` and `/brand/nextward/apple-touch-icon.png`: browser and device icons.

Update the shared `BrandLogo` component so existing header, footer, authentication, and sidebar usages receive the logo consistently. Preserve its `showText`, `dark`, `size`, `stacked`, and `tagline` interface. Use the symbol for `showText=false`; implement stacked layouts with the supplied separate symbol and wordmark. Keep optional taglines as real HTML, outside the SVG. Provide a single accessible brand name per linked logo and preserve its aspect ratio. Replace any old favicon references without leaving competing icons. The logo artwork is already outlined and does not require a font to render.

## Ready-to-use illustration assets

Read `public/nextward-assets/README.md` and `public/nextward-assets/manifest.json`. All URLs start at `/nextward-assets/`; do not include `/public` in browser URLs.

| Asset URL | Good fit |
|---|---|
| `/nextward-assets/stills/student-avatar.png` | Student role heading or role selection |
| `/nextward-assets/stills/recruiter-avatar.png` | Company role heading or hiring introduction |
| `/nextward-assets/stills/conversation.png` | Hero or messaging/connection section |
| `/nextward-assets/stills/student-workspace.png` | Student benefits or internship discovery |
| `/nextward-assets/stills/company-workspace.png` | Company benefits or application review |
| `/nextward-assets/stills/shared-success.png` | Closing call to action or completion state |

The portraits are transparent 1254 × 1254 PNGs; scenes are transparent 1536 × 1024 PNGs. They depict the same student and recruiter throughout. Use the assets selectively, maintain their proportions, and give them room to breathe. Avoid repeating the same large illustration in adjacent sections or making every section a card grid. Never present these illustrative characters as actual users, testimonials, or proof of adoption.

Two silent six-second, 30 fps loops are available at 1200 × 800:

- Conversation: `/nextward-assets/loops/conversation.webm`, `conversation.mp4`, and `conversation-poster.png`.
- Success: `/nextward-assets/loops/success.webm`, `success.mp4`, and `success-poster.png`.

The WebM files are transparent. The MP4 files use a pale `#F6F8FC` background, so do not put their opaque rectangular backgrounds on navy surfaces. Use the corresponding still illustration or poster when animation is unsuitable. Editable motion sources remain in `output/nextward-assets/motion/`; no media generation is required.

## Layout, copy, and motion

- Lead with a concise, benefit-focused hero, a prominent “Browse internships” action, and a clear company action. A conversation illustration or loop is a strong starting point for the hero.
- Give students and companies distinct benefit sections, supported by their workspace images or avatars. Show the real explore → apply → connect workflow. End with a clear action; the shared-success artwork can support it.
- Keep useful career resources and FAQs when they reflect actual content and behavior. Remove developer-facing marketing language such as “SEO-visible opportunities” and “SEO-friendly guides.” Write for students and hiring teams.
- Add tasteful section entrances, hover/focus feedback, and small graphic accents that explain discovery, applications, or connection. Prefer CSS and a small IntersectionObserver helper; use existing animation dependencies only where they add value. Avoid a new heavyweight animation framework.
- Keep entrances around 300–600 ms with modest movement and short stagger. Make content visible if JavaScript or animation setup fails. Avoid scroll hijacking, large parallax shifts, flashing effects, and decorative motion that distracts from reading.
- Keep at most one prominent loop playing in the viewport at a time. Pause offscreen media, avoid preloading every video, and offer an accessible pause/play control for ongoing animation. Use `muted`, `playsinline`, explicit dimensions, and a poster. Respect `prefers-reduced-motion` by showing stills and removing nonessential movement.
- Preserve readable copy and usable navigation on mobile. Keep artwork below or beside the hero copy without displacing the primary action. Lazy-load below-the-fold images and reserve their layout space.

## Remove fake data and unsupported claims

Audit the public website, not just the hero. Inspect landing, About, Contact, internship/company listings and detail pages, FAQs, shared layout content, and relevant data sources. Remove fabricated statistics, testimonials, star ratings, partner/placement logos, success stories, invented contacts, fake activity notifications, match percentages, and claims the application does not support.

Known issues to investigate:

- `Welcome.vue` hard-codes “1k+” students, “100+” companies, “250+” roles, and “5k+” applications. Remove them; do not replace them with other invented numbers.
- The previously reviewed hosted page displayed “92% match,” “Application sent · 2m ago,” stars, “Trusted by” copy, and “Students placed at” logos. If equivalents exist in this checkout, remove them unless backed by genuine evidence and actual functionality.
- `tests/Fixtures/DemoSeeder.php` creates demo companies including Atlas Cloud, Novabyte, Realinflo, Greenfield, Lumen, and Stride Labs, along with example-domain accounts, generated students, internships, and applications. A database count or database-driven listing is not automatically genuine. Do not show seeded fixtures as real employers, live opportunities, adoption, or placements. Do not identify fixtures solely by a person's or company's display name; inspect provenance and the actual data model.
- Inspect claims about personalized recommendations, verification, one-click applications, saved drafts, placement outcomes, speed, and international availability. Keep only features demonstrated by the implementation, and describe their actual limitations accurately.

Use live metrics only when their source and definition are trustworthy and exclude demo/test records. “Open internships” must use the actual public visibility rules and a closing date that has not passed. Reuse that definition consistently between counts and lists. If authenticity cannot be established, omit the metric, employer strip, or featured-listings section instead of guessing. A truthful empty state is better than a fabricated success story. Do not infer missing provenance flags or expose private user/application data to create public social proof.

Keep legitimate records and test fixtures intact. Fix public queries, eligibility rules, and copy as needed; do not run destructive cleanup or repopulate the database. Remove placeholder `#` links, or replace them with real existing destinations. Do not invent legal pages, contact details, pricing, or product features to fill space. Report any unresolved data-provenance issue explicitly.

## Verification and handoff

- Run the existing build and checks appropriate to the changes. Add meaningful tests only for changed data logic, especially genuine-versus-demo eligibility, expired listings, and zero-results behavior.
- Inspect desktop and mobile layouts in a browser. Verify the new logo, all used asset paths, light/dark rendering, navigation, both role actions, and real signup flows without submitting accounts.
- Verify reduced-motion behavior, keyboard access, focus states, pause controls, no missing media, no horizontal overflow, and no layout shifts from images or video. Verify that empty or unavailable authentic data does not restore fabricated fallback content.
- Search for remaining fake claims and known placeholder values. Explain any verified statistics you keep and their source. Take screenshots of the finished landing page.
- Finish with a concise summary of changes, assets chosen, claims removed, verification results, and any remaining limitations. The result should be ready for review locally; do not deploy it.
