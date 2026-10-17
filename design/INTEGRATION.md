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
`resources/css/app.css` and `resources/js/app.js`. Use `npm run dev` for HMR and
`npm run build` for production assets.

Tailwind 4 tokens live in the `@theme` block of `resources/css/app.css`. Add shared
workspace tokens there rather than introducing `tailwind.config.js`, a Tailwind 3
PostCSS pipeline, runtime Play CDN scripts, or a second Vite config.

```css
/* resources/css/app.css */
@import 'tailwindcss';
@import './custom.css' layer(components);
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

### 1c. Shared component CSS and icons

Workspace patterns (`.fld`, messages bubbles, chips) live in
`resources/css/workspace.css`. `custom.css` retains compatibility helpers and
imports `public.css`; `Guest.vue` loads `auth.css`. Keep workspace additions from
changing the approved public/auth forms and spacing.

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

### `AppSidebar.vue` / `AppTopbar.vue`
Port the markup from `tailwind/workspace-shell.js` (`navMarkup` / `topbarMarkup`).
Replace the `ROLES`/groups JS object with a `nav` data array and `v-for` the items;
replace `<a href>` with `<Link :href>`; mark the active item with
`:class="{ 'bg-blue-600 text-white': item.id === active }"`. Badges, the user
footer block, and the topbar search/actions all translate 1:1.

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

1. **Create the Inertia page** in `resources/js/Pages/` (e.g. `Internships/Index.vue`).
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
`Internship` covers every listing (`kind` is `internship` or `job`) and
`Student` covers every candidate (`career_stage` is `student`, `graduate` or
`professional`); the old names stay for compatibility.

- **Internship**: `id, title, company{name,slug,logoGradient,initials}, location,
  workType (remote|hybrid|onsite), durationWeeks, term, pay{amount,unit}, tags[],
  postedAt, applicantCount, isNew, isSaved, status?`
- **Application**: `id, internship, status (submitted|viewed|review|interview|offer|closed),
  appliedAt, updatedAt, timeline[]`
- **Company**: `id, slug, name, city, logoGradient, initials, about, openRoles,
  teamSize, foundedYear, remote, contact{email,website,hiringLead}`
- **User (candidate)**: `name, initials, headline, location, field, about, skills[],
  education[], experience[], links[], resume, profileStrength, openTo`
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
4. Verify responsive layouts, form errors, icon labels, and reduced-motion behaviour.
5. Run `npm run build` and the relevant `php artisan test` checks.

Keep changes focused enough to review against the current implemented screen and
approved direction, rather than treating historical prototype markup as immutable.
