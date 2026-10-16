# Landing page design — Nextward

## Direction

A warm, editorial page about the next step in a career, for students and professionals, covering internships and jobs in Tunisia and worldwide. Keep the existing Nextward logo, cobalt blue, and navy identity. Use Space Grotesk for headings and Inter for body text.

- Warm ivory (`#FAF9F6`) and white surfaces, generous spacing, and thin dividers.
- Open compositions with no card shadows, floating badges, image glows, or decorative frames around illustrations.
- A dark navy hero with two flat mascots (a student and a recruiter) placed directly on the background.
- A full-width dark employer section with a workspace photograph, a navy overlay, and light text. Keep the overlay stronger behind the copy and on narrow screens.
- Guide rows instead of stacked cards. FAQs live on the audience pages as native disclosures.

## Page flow

1. **Hero:** “Find your next opportunity.” Lead with exploring internships and jobs, with a search for keyword, type (internships, jobs or both) and location (worldwide or Tunisia). Say that browsing does not require an account.
2. **For students and for professionals:** two side-by-side sections. Students: PFE and summer internships, graduate programmes and first jobs. Professionals: full-time, part-time, contract and freelance roles, local, remote or abroad. Each links to its own page (`/for-students`, `/for-professionals`).
3. **Recent opportunities:** live listings, with an honest empty state.
4. **How it works:** Find your fit. Keep moving.
5. **Clear sources:** explain where listings come from and what remote eligibility means.
6. **For employers:** “Hire students and professionals.” Post an internship or a job, review applications and reply.
7. **Guides:** Existing published articles, presented as an editorial list.

## Assets and behavior

New optimized WebP assets and their generation prompts are documented in `public/nextward-assets/landing/README.md`. The hero preserves transparency; the employer photograph is decorative. Older 3D illustrations and video loops are retained in the asset library but are no longer used on the landing page.

Keep content visible immediately, preserve keyboard focus indicators, and honor reduced-motion preferences for interactive transitions. Candidate and company actions must respect the signed-in account type. Do not invent testimonials, user counts, live activity, company verification, or capabilities.

Use the installed `@heroicons/vue` library for all interface icons through the shared `Icon` component. Do not draw SVG paths or use text characters as icons. Underline link labels only; keep icons separate. Hover feedback should use restrained color changes without lifting buttons or adding shadows.

The founder introduction and walkthrough video is a future content task tracked in `FUTURE_PLANS.md`.

## Authentication pages

Login, registration, password recovery, password reset, password confirmation, and email verification share an open ivory-and-white layout. Reuse the flat hero illustration on desktop; prioritize the form on narrow screens. Keep inputs and buttons shadow-free, use Heroicons for controls, and retain visible keyboard focus.

Group registration into account and profile details, with an explicit candidate/company choice. The candidate option covers students, graduates and professionals, with a career stage field. Show field errors, submission states, and password visibility controls consistently. Preserve existing authentication endpoints and validation rules; leave login fields empty and never display demo credentials.
