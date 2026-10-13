# Landing page design — Nextward

## Direction

A warm, editorial page about taking the first step into a career. Keep the existing Nextward logo, cobalt blue, and navy identity. Use Space Grotesk for headings and Inter for body text.

- Warm ivory (`#FAF9F6`) and white surfaces, generous spacing, and thin dividers.
- Open compositions with no card shadows, floating badges, image glows, or decorative frames around illustrations.
- One flat illustration in the hero, placed directly on the background.
- A quiet application-status preview in the student section, explicitly labeled as illustrative.
- A full-width dark employer section with a workspace photograph, a navy overlay, and light text. Keep the overlay stronger behind the copy and on narrow screens.
- Guide rows and native FAQ disclosures instead of stacked cards.
- A cobalt closing section with a clear primary action.

## Page flow

1. **Hero:** “Small step. Big beginning.” Lead with exploring internships and explain that browsing does not require an account.
2. **How it works:** Find your fit, make your introduction, keep things moving.
3. **For students:** Search, saved internships, application statuses, and direct company messaging.
4. **For companies:** Company profiles, posting roles, reviewing applications, and replying to students.
5. **Guides:** Existing published articles, presented as an editorial list.
6. **FAQ:** Practical answers grounded in current application behavior.
7. **Closing action:** Explore internships, with a secondary company action where appropriate.

## Assets and behavior

New optimized WebP assets and their generation prompts are documented in `public/nextward-assets/landing/README.md`. The hero preserves transparency; the employer photograph is decorative. Older 3D illustrations and video loops are retained in the asset library but are no longer used on the landing page.

Keep content visible immediately, preserve keyboard focus indicators, and honor reduced-motion preferences for interactive transitions. Student and company actions must respect the signed-in account type. Do not invent testimonials, user counts, live activity, company verification, or capabilities.

Use the installed `@heroicons/vue` library for all interface icons through the shared `Icon` component. Do not draw SVG paths or use text characters as icons. Underline link labels only; keep icons separate. Hover feedback should use restrained color changes without lifting buttons or adding shadows.

The founder introduction and walkthrough video is a future content task tracked in `FUTURE_PLANS.md`.

## Authentication pages

Login, registration, password recovery, password reset, password confirmation, and email verification share an open ivory-and-white layout. Reuse the flat hero illustration on desktop; prioritize the form on narrow screens. Keep inputs and buttons shadow-free, use Heroicons for controls, and retain visible keyboard focus.

Group registration into account and profile details, with an explicit student/company choice. Show field errors, submission states, and password visibility controls consistently. Preserve existing authentication endpoints and validation rules; leave login fields empty and never display demo credentials.
