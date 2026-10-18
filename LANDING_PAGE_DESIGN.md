# Public pages design — Nextward

> Updated 17 October 2026. One design for every public page. It keeps the dark hero, cartoon mascots and navy footer of the earlier version, and replaces its ivory (`#FAF9F6`) background with white. The earlier ivory notes no longer apply.

## Direction

A bright, friendly site about the next step in a career, for students and professionals, covering internships and jobs in Tunisia and worldwide. Keep the existing Nextward logo. Use Space Grotesk for headings and Inter for body text, both self-hosted.

- **Frame:** navy header, dark navy hero, navy footer.
- **Sections:** white `#FFFFFF`, cool grey `#F6F8FC` and ice blue `#EEF4FF`. No cream, beige or ivory.
- **Colour:** cobalt (`--color-brand`) for actions and links; light blue for the highlighted phrase in hero headings; coral `#F78C78` only for small sparkles and link underlines on navy. Section labels are plain text, with no coloured dot in front.
- **Images:** flat cartoon mascots in heroes, steps, tools, FAQs and closing banners; the night desk scene (`21-cozy-career-workspace-night.webp`) fills the navy employer card on the home page, with white text on its empty left side; article photos only on guide cards and article pages.
- **Layout:** about 1200px wide, generous gaps on desktop, 20px side padding on phones.
- **Cards:** full thin borders and rounded corners (14–18px). No shadows, glows or one-sided accent stripes. No gradients.
- **Closing banners:** flat cobalt, inside the page width, rounded, with a mascot.

Shared styles are in `resources/css/public.css`. The landing hero and the closing banner are shared components in `resources/js/Components/Landing/`. Catalogue, opportunity and company pages keep their white cards on the cool grey page background, because they are shared with the signed-in workspace.

## Pages

Keep each landing page short: hero, search, live listings right after the search, one “how it works” block, guides and the closing banner. Don't add a second block that explains the same thing.

- **Home:** dark hero “Find your next opportunity.” with the two mascots, then a white search card overlapping the hero (keyword and type) with shortcuts for Tunisia, remote from Tunisia and relocation. Then live recent opportunities with a one-line note on what each listing shows, a white student card and a navy professional card, three illustrated steps, the navy employer card with the night desk scene and three guide cards.
- **For students:** “Your first opportunity starts here.”, internship search (keyword and programme) with popular searches and live counts, live internships and first jobs, four illustrated steps on a dashed line, guides, FAQs (including what a company sees when you apply) and the closing banner.
- **For professionals:** “Your next job, in Tunisia or abroad.”, a keyword search with one row of popular searches (places first, then job types) and live counts, live jobs, a clearly labelled example listing that keeps “Not given by the source”, guides and the closing banner.
- **Opportunities:** category tabs, a search box and one row of quick filters (field, type, country, work mode, experience, date) that apply as soon as they change. Field comes first and lets people pick several fields at once from a panel of checkboxes; when more than one is picked, each shows as a chip you can remove. Contract, city, employer, pay and internship programme sit under “More filters”. Filters with no control here (salary, listing language, duration, start date from older links and saved searches) still apply and show as chips you can remove. Results show in two columns when there is room, and the same page is used in the signed-in workspace.
- **Career guides:** dark hero “A little guidance for your next move.”, a search box and category tabs that filter the posts on the page (with a reset and an empty state), the newest guide as the featured one, and an image grid. Articles have a dark header with their photo, the readable body with contents, related guides and the closing banner.
- **About, Contact, Privacy, Terms:** dark heroes (with a mascot on About and Contact). Legal pages keep their contents list, and `[[...]]` placeholders stay highlighted until the owner fills them in.

## Article photos

Article photos are matched by slug in `resources/js/Components/Blog/guideImages.js`, with English and French slugs listed separately. The same photo is used on the card, the article and every teaser. A post without a photo gets a clean text card with an icon. Front matter `cover` is still the share image and wins when set.

## Honest content

Listings, counts and guides come from the server. Unknown salary, remote eligibility, relocation and visa stay unknown. Remote does not mean worldwide. Opening an outside link does not apply or send a CV. Do not invent testimonials, user counts, live activity, company verification or features. The photos are generated scenes, never real users, staff or partners.

## Behaviour

Content is visible without waiting for animations; mascots fade in and sparkles pop once, and both stop under reduced motion. Keep keyboard focus visible, label every field, use native `<details>` for FAQs and the menus. Long French labels must wrap. Icons come from `@lucide/vue` or the shared `Icon` component.

## Authentication pages

Login, registration and the other account pages use `Guest.vue` and `auth.css`. They were outside this update and still use the older ivory layout with the flat mascot. Group registration into account and profile details, with an explicit candidate/company choice. Keep inputs and buttons shadow-free, show field errors consistently, and never display demo credentials.
