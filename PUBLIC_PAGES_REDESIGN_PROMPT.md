# Implement Nextward's light public website redesign

> Task brief, kept as history. Only parts of it were used: the owner kept the navy hero, mascots and navy footer. The current design is in `LANDING_PAGE_DESIGN.md`. Mockups are in `output/imagegen/nextward-light-concepts-2026-10-17/`.

Work in /Users/alabaganne/Desktop/Projects/nextward. Read AGENTS.md before making changes. First inspect the relevant pages and propose a short plan, as required by the repository. Then implement the redesign in the existing Laravel, Inertia and Vue application.

## Goal and scope

Replace the cream public website with a bright, coherent design based on the four saved mockups. The main pages are /, /for-students, /for-professionals and /blog. Give the rest of the non-auth public website the same visual language: article pages, the opportunity catalogue and its category pages, public opportunity details, public company profiles, About, Contact, Privacy and Terms. Include the corresponding French routes and real translated article routes.

Keep authentication forms and signed-in dashboards outside this redesign. Keep functional changes limited to what the new public design needs. No framework migration, new dependency, database migration, unrelated refactor, deployment, commit, push or pull request. Do not edit tests.

## Read these local references first

Full-page visual references:
- output/imagegen/nextward-light-concepts-2026-10-17/home.png
- output/imagegen/nextward-light-concepts-2026-10-17/for-students.png
- output/imagegen/nextward-light-concepts-2026-10-17/for-professionals.png
- output/imagegen/nextward-light-concepts-2026-10-17/career-guides.png

Extracted image assets:
- public/nextward-assets/landing-light-v1/README.md
- public/nextward-assets/landing-light-v1/manifest.json
- output/imagegen/nextward-light-concepts-2026-10-17/asset-contact-sheet.jpg

Likely implementation files, inspect their current contents and dependencies:
- resources/js/Layouts/Public.vue
- resources/css/public.css
- resources/js/Pages/Welcome.vue
- resources/js/Pages/Audience/Students.vue
- resources/js/Pages/Audience/Professionals.vue
- resources/js/Pages/Blog/Index.vue
- resources/js/Pages/Blog/Show.vue
- resources/js/Pages/Internships/Index.vue and the component actually used by the public opportunity detail route
- resources/js/Pages/Companies/Public.vue
- resources/js/Pages/About.vue, Contact.vue, Privacy.vue and Terms.vue
- resources/js/Components/Internship/RoleCard.vue
- routes/web.php, app/Http/Controllers/PublicPageController.php, app/Http/Controllers/BlogController.php and app/Support/Blog.php
- resources/content/blog/en/ and resources/content/blog/fr/

## Visual direction

The owner dislikes cream backgrounds. Use white #FFFFFF, cool grey #F6F8FC and ice blue #EEF4FF for public surfaces. Use cobalt for actions and selected heading words, navy for headings and slate for supporting text. Retain the existing brand token values when they achieve the reference look. Do not change workspace-wide colours just to restyle the public site.

Build a white header and footer, spacious layouts, strong readable headings and editorial photo sections. Keep the self-hosted Space Grotesk heading font and Inter body font. Use roughly 1120-1200px content width, generous desktop gaps and sensible mobile padding. Cards can have full thin neutral borders and modest rounded corners.

No cream, beige or ivory surfaces. No shadows, glows, decorative gradients, floating badges, mascot illustrations or sparkle decorations on the redesigned public pages. No one-sided accent stripes. Use flat cobalt closing banners. Preserve the actual existing Nextward logo and brand mark: the approximate logos inside the generated mockups are not replacement logo assets.

The mockups guide composition and hierarchy. Rebuild headings, controls, cards and example panels as real accessible HTML/Vue. Never display a whole mockup as the website, and never extract its UI controls as raster assets. Correct generated typography errors and unsupported claims rather than reproducing them.

## Image files and placement

Use the provided WebP files from /nextward-assets/landing-light-v1/. PNG copies are lossless crop masters. They are exact crops of generated mockups, not separate high-resolution photography originals. Their native dimensions are in manifest.json. Do not claim they are high-resolution or upscale them to create fake detail. Avoid stretching or severe cropping, keep small portraits in small cards and use width/height attributes to prevent layout shifts.

Use these files:
- home-hero.webp: homepage hero.
- home-student-portrait.webp and home-professional-portrait.webp: homepage audience panels.
- home-guide-portrait.webp: homepage guide teaser.
- professionals-hero.webp: professional hero.
- professionals-guide-shortlist.webp and professionals-guide-remote.webp: small professional guide teasers.
- students-hero.webp: student hero.
- students-guide-application.webp and students-guide-library.webp: student guide teasers.
- students-closing-portrait.webp: optional right-hand student closing image. This crop includes blue background.
- guide-remote-work.webp: remote eligibility article feature/cover.
- guide-cv-application.webp: internship application tips cover.
- guide-job-shortlist.webp: professional job comparison cover.
- guide-find-internship.webp: finding an internship cover.
- guide-hiring-interns.webp: employer guide cover.
- guide-platform.webp: platform guide cover.

Article mapping:
- check-remote-job-eligibility-from-tunisia / verifier-eligibilite-offre-remote-tunisie -> guide-remote-work.webp
- best-internship-application-tips / conseils-candidature-stage -> guide-cv-application.webp
- compare-job-opportunities-as-a-professional / comparer-des-offres-emploi -> guide-job-shortlist.webp
- how-to-find-an-internship-as-a-student / trouver-un-stage-etudiant -> guide-find-internship.webp
- how-companies-can-hire-interns-faster / recruter-des-stagiaires-plus-vite -> guide-hiring-interns.webp
- what-makes-a-good-internship-platform / bonne-plateforme-de-stage -> guide-platform.webp

Confirm slugs against the current content. For additional published articles use a suitable existing local image or a clean text treatment. Do not hide articles just because they lack a mapped photo. Reuse the same article image consistently in the blog, detail page and landing teasers. Do not create a new generic image framework or change the backend article schema just to attach a few images.

These photos depict generated illustrative people and scenes. Do not identify them as actual users, employees, founders, offices, partners or testimonials. Add useful alt text where images convey meaning and empty alt text for decorative images. Load primary hero images promptly and lower-page imagery lazily.

## Page composition

Home:
- Bright split hero, headline "Find your next opportunity.", short explanation covering both students and professionals.
- Put search close to the opening content. Keep the existing keyword, opportunity type and location behaviour.
- Shortcuts for Tunisia, remote from Tunisia and relocation.
- Two audience panels, recent opportunities, three simple steps, clear source information, employer CTA and career guide teaser.
- Keep listings and counts connected to actual server data. The example jobs in the visual reference are not seed content to hardcode.

Professionals:
- Light split hero, "Your next job, in Tunisia or abroad.", professional image, clear job CTA.
- Useful job shortcuts and three location paths.
- Explain eligibility, unknown fields and source attribution with a clearly labelled example listing.
- Actual recent jobs, saved searches/profile/tracking features, relevant career guides and closing CTA.

Students:
- Light split hero, "Your first opportunity starts here.", student image, internship and first-job CTAs.
- Programme shortcuts, four simple journey steps, actual internships and entry-level jobs.
- Application preparation section, career guides, accessible FAQs and closing CTA.
- Preserve the real CV workflow: the current application form accepts attachments. Do not promise a reusable profile CV upload unless the current application supports it.

Career guides:
- White editorial introduction, "A little guidance for your next move.", featured article and image-led article grid.
- Use the current published post data, real titles, excerpts, categories, dates and reading times.
- If implementing the search and category controls shown in the mockup, make them actually filter the available posts with a clear reset and empty state. Prefer a simple local Vue implementation using existing props. No fake controls, new search service or new API.
- Article detail pages need readable body width, clear heading hierarchy, a relevant image where available, existing author information, related guides and the opportunity CTA.

Other public pages:
- Apply the same white/cool backgrounds, typography, header, footer, fields and buttons.
- Keep discovery pages focused on filters and results. Keep opportunity details focused on the role, source and application action.
- Contact must retain its existing fields, validation, throttling and submit flow.
- Legal pages need readable prose and usable contents links. Preserve their text and flag existing placeholders in the handoff instead of filling them with invented company or legal details.
- Keep About focused on the actual product and existing founder information. Do not introduce claims about full-time job availability or new founder biography content.

## Behaviour and content that must remain correct

Preserve routes, queries, pagination, existing Inertia props, account-aware navigation, language switching, localized links, canonical/hreflang/structured data, source links, bookmarks, source dates, external application destinations and native application behaviour.

Unknown salary, remote eligibility, relocation or visa details stay explicitly unknown. The mockup's simplified Yes/No source example does not justify reducing an existing unknown state. Remote does not mean worldwide. Opening an external link does not submit an application or send a CV.

No invented job counts, customers, endorsements, verified company labels, testimonials, guarantees, dates or product capabilities. Preserve honest empty states. Do not change imported listing content to match the design.

Keep English and French equally usable. Translate new interface copy naturally, preserve current translation links, allow longer French labels to wrap and do not assume translated article slugs match English slugs.

Use the installed icon components and existing project conventions. Keep clear keyboard focus, labelled fields, semantic headings, usable mobile menus and accessible accordions. Respect reduced motion. Keep content visible without entrance animation dependencies.

## Finish the implementation

Check the actual public routes in a browser at desktop and mobile widths, including English and French versions, catalogue filtering, article navigation and language switching. Verify that all supplied image paths resolve and that layouts do not overflow. Avoid submitting real contact messages or applications while checking.

Run the relevant existing checks, including npm run build and php artisan test when the local environment supports them. Do not edit tests or hide failures. Report any existing environment failure plainly.

Update the affected public design documentation, including AGENTS.md and LANDING_PAGE_DESIGN.md, to reflect the accepted white/cool direction. Make clear that older ivory public design notes have been superseded. Keep the work within this design scope and leave changes uncommitted for review.
