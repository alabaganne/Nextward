# Public pages design — Nextward

> Updated 17 October 2026. One design for every public page. It keeps the dark hero, cartoon mascots and navy footer of the earlier version, and replaces its ivory (`#FAF9F6`) background with white. The earlier ivory notes no longer apply.

## Direction

A bright, friendly site about the next step in a career, for students and professionals, covering internships and jobs around the world. Keep the existing Nextward logo. Use Space Grotesk for headings and Inter for body text, both self-hosted.

- **Frame:** navy header, dark navy hero, navy footer.
- **Sections:** white `#FFFFFF`, cool grey `#F6F8FC` and ice blue `#EEF4FF`. No cream, beige or ivory.
- **Colour:** cobalt (`--color-brand`) for actions and links; light blue for the highlighted phrase in hero headings; coral `#F78C78` only for small sparkles and link underlines on navy. Section labels are plain text, with no coloured dot in front.
- **Images:** flat cartoon mascots in heroes, steps, tools, FAQs and closing banners; the night desk scene (`21-cozy-career-workspace-night.webp`) fills the navy employer card on the home page, with white text on its empty left side; article photos only on guide cards and article pages.
- **Layout:** about 1200px wide, generous gaps on desktop, 20px side padding on phones.
- **Cards:** full thin borders and rounded corners (14–18px). No shadows, glows or one-sided accent stripes. No gradients.
- **Closing banners:** a flat cobalt band across the full width, square, sitting right on the footer with no gap, with a mascot.

Pages are styled with Tailwind classes; classes shared by several pages are in `resources/css/public.css`. The landing hero and the closing banner are shared components in `resources/js/Components/Landing/`. Catalogue, opportunity and company pages keep their white cards on the cool grey page background, because they are shared with the signed-in workspace.

## Pages

Keep each landing page short: hero, search, live listings right after the search, one “how it works” block, guides and the closing banner. Don't add a second block that explains the same thing.

- **Home** (one page for everyone; `/for-students` and `/for-professionals` were merged into it and redirect here with a 301): dark hero “Find your next opportunity.” with the two mascots and two buttons, then a white search card overlapping the hero (keyword and type) with popular searches and live counts (empty ones hidden). Then live recent opportunities with a one-line note, a cool grey “Companies hiring now” band (a company search box and up to eight hiring companies with their open-role counts, linking to `/companies`), three illustrated steps, a cool grey band for the AI CV and cover letter tools (“Free while we launch”), “Know before you apply” with the clearly labelled example listing on white, the navy employer card, three guide cards, the FAQ (text in `config/nextward_public.php`, also sent as `FAQPage` data) and the closing banner.
- **Opportunities:** tabs All / Internships / Jobs, a search box and one row of quick filters (field, skills, type, country, work mode, experience, date) plus an “Open worldwide” toggle, all applied as soon as they change. Field comes first and lets people pick several fields at once from a panel of checkboxes; when more than one is picked, each shows as a chip you can remove. There is no “More filters” panel. Filters with no control here (contract, city, employer, pay, programme, salary, listing language, duration, start date from older links and saved searches) still apply and show as chips you can remove. Cards and detail pages show an “Open worldwide” badge for roles anyone can apply to, and never a Tunisia-specific line. Results show in two columns when there is room, and the same page is used in the signed-in workspace.
- **Companies** (`/companies`, `/fr/companies`): for visitors, a white header with a company search (name or city), then chips for “Hiring now” (the default), each IT field with open roles, and “All companies”. Cards show the logo, name, location, the three newest open roles and the open-role count, and link to the company page. Signed-in users see this public page too; their workspace companies list is at `/app/companies`.
- **Career guides:** dark hero “A little guidance for your next move.”, a search box and category tabs that filter the posts on the page (with a reset and an empty state), the newest guide as the featured one, and an image grid. Articles have a dark header with their photo, the readable body with contents, related guides and the closing banner.
- **About:** dark hero “IT careers, made clearer.”, then white sections only (no alternating grey bands): the mission with three tinted cards (what Nextward covers, both languages, help to apply) and one line on where listings come from, “Free to use, with AI help when you apply” (`#ai-tools`, one line on what is free, no pricing details), the founder in a navy card with contact links, and the closing banner.
- **About, Contact, Privacy, Terms:** dark heroes (with a mascot on About and Contact). Legal pages keep their contents list, and `[[...]]` placeholders stay highlighted until the owner fills them in.

## Article photos

Article photos are matched by slug in `resources/js/Components/Blog/guideImages.js`, with English and French slugs listed separately. The same photo is used on the card, the article and every teaser. A post without a photo gets a clean text card with an icon. Front matter `cover` is still the share image and wins when set.

## Honest content

Listings, counts and guides come from the server. Unknown salary, remote eligibility, relocation and visa stay unknown. Remote does not mean worldwide. Opening an outside link does not apply or send a CV. Do not invent testimonials, user counts, live activity, company verification or features. The photos are generated scenes, never real users, staff or partners.

## Behaviour

Content is visible without waiting for animations; mascots fade in and sparkles pop once, and both stop under reduced motion. Keep keyboard focus visible, label every field, use native `<details>` for FAQs and the menus. Long French labels must wrap. Icons come from `@lucide/vue` or the shared `Icon` component.

## Authentication pages

Login, registration and the other account pages use `Guest.vue`: a navy story panel with a mascot on the left and a white form panel on the right (the navy panel becomes a navy header on phones). Their shared pieces are `Components/Auth/` (`Heading`, `Field`, `Feedback` and the class lists in `classes.js`). Group registration into account and profile details, with an explicit candidate/company choice. Keep inputs and buttons shadow-free, show field errors consistently, and never display demo credentials.
