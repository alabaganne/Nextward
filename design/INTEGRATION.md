# Integrating the Nextward UI into the Laravel + Inertia + Vue app

This is a workspace integration reference for the current **Laravel 13 +
Inertia.js 3 + Vue 3 + Tailwind CSS 4 + Vite 8** application. Historical prototype
helpers under `design/tailwind/` provide layout ideas; they are not the runtime
configuration or a requirement to reproduce every effect.

Read [DESIGN.md](DESIGN.md) for workspace guidance. The approved public/auth
appearance is documented in [LANDING_PAGE_DESIGN.md](../LANDING_PAGE_DESIGN.md)
and implemented in `Public.vue`, `Guest.vue`, and their page styles. Preserve that
flat direction: do not reintroduce card/button shadows, glows, or hover lifts.
User-approved designs and implemented components take precedence over prototypes.
---

## 1. One-time setup

### 1a. Current build and token entrypoints

`vite.config.js` is the sole Vite configuration. It uses `laravel-vite-plugin`,
`@vitejs/plugin-vue`, and `@tailwindcss/vite`, with entrypoints
`resources/css/app.css` and `resources/js/app.js`. Use `npm run dev` for the whole
local stack with HMR (`npm run dev:vite` for Vite alone) and `npm run build` for
production assets.

Tailwind 4 tokens live in the `@theme` block of `resources/css/app.css`. Add shared
workspace tokens there rather than introducing `tailwind.config.js`, a Tailwind 3
PostCSS pipeline, runtime Play CDN scripts, or a second Vite config.

```css
/* resources/css/app.css */
@import 'tailwindcss';
@import './fonts.css';
@import './elements.css' layer(components);
@import './public.css' layer(components);
@import './workspace.css' layer(components);
@plugin '@tailwindcss/forms';

@theme {
  --color-ink: #0c1322;
  --color-paper-2: #f6f8fc;
  --font-body: 'Plus Jakarta Sans', Inter, ui-sans-serif, system-ui, sans-serif;
  --container-wrap: 1200px;
}
```

This excerpt illustrates the existing entrypoint; retain the full implemented
brand/token definitions. Arbitrary-value utilities such as `text-[15.5px]` and
`gap-[18px]` work with the existing compiler.

### 1b. Fonts

Inter, Space Grotesk, and Plus Jakarta Sans are self-hosted: woff2 files in
`resources/fonts/`, `@font-face` rules in `resources/css/fonts.css` (imported by
`app.css`). No font is loaded from Google. `AppShell` uses `font-body` for workspace text. Public/auth pages use Inter through
`font-sans`; all share `font-display` for headings.

### 1c. Where styles go

Style with Tailwind classes in the template first. A class list used several times
in one component goes in a constant or helper in its script (see `navItem` in
`AppSidebar.vue` or `Components/Auth/classes.js` for the sign-in pages). Plain CSS is
for what Tailwind can't do well, and it lives next to the component that uses it:
a short `<style>` block, or a file with the component's name when it is long
(`Pages/Blog/Show.css` styles article HTML that comes from Markdown).

`resources/css/` keeps only what several components share:

| File | Holds |
|---|---|
| `app.css` | Tailwind setup, theme tokens, the `menu-pop` animation, the `short-screen` variant |
| `elements.css` | Defaults for focus outlines, labels and form fields |
| `public.css` | Public page classes: container, buttons (`.cta`), sections, hero, mascot stage, search field, chips, guide grid, FAQ |
| `workspace.css` | Workspace classes: `.cbtn`, `.icon-btn`, `.fld`, `.ck` |
| `fonts.css` | Self-hosted `@font-face` rules |
| `cv.css` | Resume and letter PDFs, inlined by `CvPdf`, not part of the site bundle |

When a shared class ends up used by one component only, move it into that
component. Use px breakpoints (`max-[1024px]:`, `max-[768px]:`). Tailwind sorts
named breakpoints (`max-lg:`) after px ones, so mixing them on one element lets the
wider rule win on phones.

Use `Shared/Icon.vue` / `@heroicons/vue` for public/auth icons and named
`@lucide/vue` components for workspace icons. Do not handwrite SVG paths or embed
SVG strings. Keep decoration hidden from assistive technology and label icon-only
controls. Link underlines apply to the text, never the icon.

---

## 2. The mental model: custom elements → Inertia layouts

| Prototype (HTML) | App (Inertia/Vue) |
|---|---|
| `<app-shell active="dashboard">…</app-shell>` | `AppShell.vue` persistent layout, `active` prop, `<slot/>` for `…` |
| `<site-header>` / `<site-footer>` | `Public.vue` for public pages |
| Authentication prototype | `Guest.vue` and the existing auth form components |
| `<app-sidebar>` / `<app-topbar>` | `AppSidebar.vue` / `AppTopbar.vue` (children of `AppShell`) |
| A `tailwind/*.html` body | An Inertia **page** in `resources/js/Pages/` |
| `href="tailwind/x.html"` | `<Link :href="route('x')">` |
| Hardcoded lists/cards | `v-for` over props passed from the Laravel controller |
| Reveal-on-scroll script | optional existing `v-reveal`; public/auth content stays visible |

**The `{children}` pattern you asked about is just Vue `<slot/>`** — and Inertia's
*persistent layouts* mean the sidebar/topbar mount once and don't re-render on
navigation.

---

## 3. Build the layouts

### `AppShell.vue` (workspace chrome — the `<slot>` wrapper)

Reuse the existing `resources/js/Layouts/AppShell.vue`. It owns the responsive
sidebar drawer, topbar, notification panel, and page slot. Its `active` prop marks
the current navigation item; `fill` supports the internally scrolling Messages
page. Keep mobile navigation and content sizing in this shared layout.

### Using it from a page (Inertia persistent layout)
```vue
<!-- resources/js/Pages/Dashboard.vue -->
<template>
  <div class="px-10 pt-[34px] pb-[50px] w-full">
    <!-- paste the CONTENT of tailwind/dashboard.html (inside <app-shell>) here,
         swapping hardcoded data for props -->
  </div>
</template>

<script>
import AppShell from '@/Layouts/AppShell.vue';
export default {
  // Inertia persistent layout — chrome persists across navigations:
  layout: (h, page) => h(AppShell, { active: 'dashboard' }, () => page),
  props: { stats: Object, applications: Array }, // from the controller
};
</script>
```
*(Messages page: `{ active: 'messages', fill: true }`.)*

The application is on Vue 3 and Inertia 3. Existing workspace pages use
`defineOptions({ layout: (h, page) => h(AppShell, { active: '…' }, () => page) })`.
The app resolver also defaults pages to `AppShell`. Public/auth pages assign their
own layouts. Match the existing page's pattern and avoid nested layout chrome.
Signed-in routes live under `/app` and only those render `AppShell`. Pages shared by
both sides (`Opportunities/Index`, `Opportunities/Show`) use `opportunityLayout`,
which picks `AppShell` for `/app` URLs and `Public` otherwise; inside `/app`, use
`appPath()` from `utils/url.js` so listing and company links stay in the workspace.

### `AppSidebar.vue` / `AppTopbar.vue`
Port the markup from `tailwind/workspace-shell.js` (`navMarkup` / `topbarMarkup`).
Replace the `ROLES`/groups JS object with a `nav` data array and `v-for` the items;
replace `<a href>` with `<Link :href>`; mark the active item with
`:class="{ 'bg-blue-600 text-white': item.id === active }"`. Badges, the user
footer block, and the topbar search/actions all translate 1:1.

An item can carry `children` to make a group (the admin Imports group). The group
button opens and closes it, it opens on its own when `active` is one of its pages
(`imports.sources`, `imports.review` and so on), and it shows the summed badge
while closed. Child links use `.nav-child`: indented under the group label, the
same flat tint when active, no side bars.

Credits are not a sidebar item. With billing on, candidates see their balance as a
pill in the topbar (`CreditsMenu.vue`, blue tint, amber at 0). Hovering it, or
clicking or tapping it, opens a panel with the balance, what a credit pays for, the
next free monthly credits (or a nudge to verify the email) and a button to the
Credits page (`#packs`) labelled "Buy more credits". During the beta
(`billing.beta`) it also has a beta note and a "Get 100 free credits" button that
works every time it is pressed. The Credits page shows the
packs greyed out (`grayscale`, low opacity, `inert`) under a grey overlay with a
white card: a lock in a rounded square and "Payments are coming soon".

### CV editor (`CvStudio.vue`)

The kit page (`Kits/Show`) and the resume editor (`Resumes/Show`) share one
full-height layout (`AppShell` with `fill`): a header bar with the title and
actions, then three panes that each scroll on their own and no max width. On the
left, the sections (Design, Name & contact, About, Experience, Education,
Projects, Skills, Languages, and Cover letter on kits), shown as icons only below
1400px. In the middle, one section at a time (`CvEditor` with a `section` prop),
with previous and next links. On the right, the preview on a grey background,
with each printed page drawn as its own sheet (no dashed break lines). Below
1280px the sections become a scrolling row of tabs and a Preview button swaps the
editor for the preview. Entries (roles, schools, projects) open one at a time and
can be moved up or down; skills and languages are chips; bullet points are one box
each (Enter adds the next). Ctrl+S saves, and leaving with unsaved changes asks
first (`utils/editor.js`). PDF downloads on kits save first.

### `Public.vue` and `Guest.vue`

Public pages use `Public.vue`; auth pages use `Guest.vue`. Their approved structure,
assets, responsive layouts, and form controls already exist. Reuse these components
rather than porting the obsolete marketing or split-auth prototype helpers.

Shared public styles live in `resources/css/public.css`. Guide cards use
`Components/Blog/GuideCard.vue`, and article photos are matched by slug in
`Components/Blog/guideImages.js` (English and French slugs are both listed).
The landing hero (`Components/Landing/LandingHero.vue`) and the cobalt closing
banner (`Components/Landing/ClosingBanner.vue`) are shared by the landing and blog pages.
---

## 4. Porting a page — checklist

For each `tailwind/*.html`:

1. **Create the Inertia page** in `resources/js/Pages/` (e.g. `Opportunities/Index.vue`).
2. **Copy the inner content** — everything *inside* `<app-shell>` (or between
   `<site-header>` and `<site-footer>`). Leave the chrome to the layout.
3. **Assign the layout** with the right `active` value (see table in `AppSidebar`).
4. **Swap links:** `href="tailwind/foo.html"` → `<Link :href="route('foo')">`.
   Add the matching routes in `routes/web.php` + controllers returning
   `Inertia::render('…', [...])`.
5. **Replace hardcoded data with props.** Turn repeated cards/rows into `v-for`
   over arrays the controller provides. Reuse the current shared components and flat patterns in `DESIGN.md`.
6. **Wire interactions to Vue state** instead of the inline `<script>`:
   - filter chips / tabs → `ref`/`data` + computed filtered list
   - save/bookmark toggles → `v-model` or an Inertia `router.post`
   - settings toggles, modal open/close, password show/hide → component state
   - forms → Inertia `useForm()` (`form.post(route(...))`), show server errors
     under fields.
7. **Follow the implemented design.** Preserve spacing and responsive behaviour
   while using the current tokens and library icons. Prototype shadows, glows,
   lifts, and obsolete public/auth layouts are not instructions to reintroduce them.

---

## 5. Motion and visibility

The existing Vue 3 directive is in `resources/js/directives/reveal.js`; it is
optional compatibility code, not a requirement for new pages. Public/auth content
stays visible without entrance effects. Prefer subtle colour/border/underline
feedback, keep controls stationary, and honour reduced motion for any new effects.

---

## 6. Data shapes (historical suggestions)

Use actual controller/resource props as the contract. These prototype suggestions
are guidance only; do not invent profile fields, metrics, or timeline data.
`Opportunity` covers every listing (`kind` is `internship` or `job`) and
`Candidate` covers every person looking for work, admins included.

- **Opportunity**: `id, title, company{name,slug,logoGradient,initials}, location,
  workType (remote|hybrid|onsite), durationWeeks, term, pay{amount,unit}, tags[],
  postedAt, applicantCount, isNew, isSaved, status?`
- **Application**: `id, internship, status (submitted|viewed|review|interview|offer|closed),
  appliedAt, updatedAt, timeline[]`
- **Company**: `id, slug, name, city, logoGradient, initials, about, openRoles,
  teamSize, foundedYear, remote, contact{email,website,hiringLead}`
- **User (candidate)**: `name, initials, headline, location, field, about, skills[],
  education[], experience[], links[], resumes[], profileStrength, openTo`
- **Resume**: `id, name, language{code,label}, source (uploaded|profile|translation|copy),
  status (queued|draft|ready|failed), is_main, editable, has_file, style, targets[]`.
  Up to 10 per candidate; the main one is shared, read by the AI and picked first when applying.
- **Application attachment**: `name, size, mime, resume, url`; the chosen resume comes first with `resume: true`.
- **Message thread**: `id, person{name,initials,gradient,role,company}, role(role applied to),
  online, unread, messages[{from(me|them), body, time, daySep?}]`

Pass status→colour mapping as a small helper (see `DESIGN.md` §2 status table) so
pills render consistently.

---

## 7. Extending the current app

1. Reuse tokens in `resources/css/app.css` and workspace patterns in `workspace.css`.
2. Reuse `AppShell`, `AppSidebar`, and `AppTopbar` for signed-in pages; `Public` and
   `Guest` cover public/auth pages.
3. Connect real controller props, routes, and Inertia `useForm()` interactions.
4. Lists of roles (opportunities, saved roles) use `RoleCard` in a grid with two cards
   a row on wide screens (`@container` + `@3xl:grid-cols-2`) and the shared `Pagination`
   (20 a page). Changing page starts at the top.
5. `public_url` values carry the `APP_URL` host (for SEO and emails). When one feeds an
   in-app `<Link>`, pass it through `sitePath()` from `utils/url.js`, so links keep
   working when the site is opened on another host (say `localhost` vs `127.0.0.1`).
6. `Pages/Leads/Index.vue` (admin only) lists job leads with `Components/Leads/LeadCard.vue`:
   eligibility, score, reasons, the true posting date, status, applied date, notes and contacts.
   Tabs are Today (with Hidden today), All leads and Left out.
7. Company names on cards and pages show `Components/Company/VerifiedBadge.vue` when the
   company prop has `verified: true` (`compact` for an icon only on cards, `dark` on navy).
8. Verify responsive layouts, form errors, icon labels, and reduced-motion behaviour.
9. Run `npm run build` and the relevant `php artisan test` checks.

Keep changes focused enough to review against the current implemented screen and
approved direction, rather than treating historical prototype markup as immutable.
