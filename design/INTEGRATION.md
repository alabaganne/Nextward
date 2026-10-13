# Integrating the Internly UI into the Laravel + Inertia + Vue app

This guide explains how to port the static `tailwind/*.html` prototype in this repo
into the existing **Laravel + Inertia.js + Vue 2 + Tailwind CSS** application.

Read `DESIGN.md` first — it's the visual source of truth. This file is about the
*mechanics* of moving the markup into Vue/Inertia without losing the design.

> The prototype is plain HTML with three custom-element helpers
> (`<app-shell>`, `<site-header>`, `<site-footer>`) and a shared Tailwind token
> config. Every one of those maps cleanly onto an Inertia concept: **layouts with
> `<slot>`, page components, and `tailwind.config.js`.**

---

## 1. One-time setup

### 1a. Port the design tokens into `tailwind.config.js`
The prototype loads tokens at runtime via `tailwind/setup.js`. In the real app they
belong in `theme.extend` so they're available everywhere and tree-shaken in prod.
Copy the object from `tailwind/setup.js` verbatim:

```js
// tailwind.config.js
export default {
  content: ['./resources/views/**/*.blade.php', './resources/js/**/*.{vue,js}'],
  theme: {
    extend: {
      colors: {
        blue: { 50:'#eef4ff',100:'#dbe7ff',200:'#b9d0ff',300:'#8ab2ff',400:'#5a8dff',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8' },
        navy: { 700:'#0f2150',800:'#0a1738',900:'#070f26' },
        ink:  { DEFAULT:'#0c1322', 700:'#283449' },
        muted:{ DEFAULT:'#5b6677', 2:'#8a93a3' },
        line: { DEFAULT:'#e7ebf1', 2:'#eef1f6' },
        paper:{ DEFAULT:'#ffffff', 2:'#f6f8fc', 3:'#eef2f9' },
      },
      fontFamily: {
        display: ['"Space Grotesk"','system-ui','sans-serif'],
        body:    ['"Plus Jakarta Sans"','system-ui','sans-serif'],
      },
      borderRadius: { sm2:'12px', DEFAULT:'18px', xl2:'18px', '2xl2':'26px' },
      maxWidth: { wrap:'1200px' },
      boxShadow: {
        xs:'0 1px 2px rgba(12,19,34,.05)',
        card:'0 18px 40px -22px rgba(15,33,80,.28)',
        big:'0 40px 90px -45px rgba(15,33,80,.45)',
        cta:'0 14px 26px -14px rgba(37,99,235,.85)',
      },
      letterSpacing: { eyebrow:'.18em' },
    },
  },
};
```

After this, **delete the `tailwind/setup.js` reference** — utilities like
`bg-blue-600`, `font-display`, `rounded-[18px]`, `shadow-cta` now compile at build time.

> The prototype uses arbitrary values heavily (`text-[15.5px]`, `gap-[18px]`,
> `px-[22px]`). Those work as-is with the Tailwind compiler — no config needed.
> Over time you may promote the most common ones to named tokens, but it's not required.

### 1b. Fonts
Either keep the Google Fonts `<link>` in `app.blade.php`, or install
`@fontsource/space-grotesk` and `@fontsource/plus-jakarta-sans` and import them in
your app entry. Set the body default once:

```css
/* resources/css/app.css */
@tailwind base; @tailwind components; @tailwind utilities;
body { @apply font-body text-ink bg-paper antialiased; }
```

### 1c. Shared component CSS
A few prototype files use `<style type="text/tailwindcss">@layer …</style>` blocks
for repeated patterns (`.reveal`, `.fld`, form checkboxes, the messages bubbles).
Move those into `resources/css/app.css` under `@layer components` so every page
shares them. Keep the names.

---

## 2. The mental model: custom elements → Inertia layouts

| Prototype (HTML) | App (Inertia/Vue) |
|---|---|
| `<app-shell active="dashboard">…</app-shell>` | `AppShell.vue` persistent layout, `active` prop, `<slot/>` for `…` |
| `<site-header>` / `<site-footer>` | `MarketingLayout.vue` |
| `<app-sidebar>` / `<app-topbar>` | `AppSidebar.vue` / `AppTopbar.vue` (children of `AppShell`) |
| A `tailwind/*.html` body | An Inertia **page** in `resources/js/Pages/` |
| `href="tailwind/x.html"` | `<Link :href="route('x')">` |
| Hardcoded lists/cards | `v-for` over props passed from the Laravel controller |
| Reveal-on-scroll script | a `v-reveal` directive (below) |

**The `{children}` pattern you asked about is just Vue `<slot/>`** — and Inertia's
*persistent layouts* mean the sidebar/topbar mount once and don't re-render on
navigation.

---

## 3. Build the layouts

### `AppShell.vue` (workspace chrome — the `<slot>` wrapper)
```vue
<template>
  <div class="grid grid-cols-[248px_1fr]" :class="fill ? 'h-screen' : 'min-h-screen'">
    <AppSidebar :active="active" />
    <div class="flex flex-col min-w-0" :class="{ 'overflow-hidden': fill }">
      <AppTopbar />
      <slot />            <!-- ← the page content goes here -->
    </div>
  </div>
</template>

<script>
import AppSidebar from './AppSidebar.vue';
import AppTopbar  from './AppTopbar.vue';
export default {
  components: { AppSidebar, AppTopbar },
  props: {
    active: { type: String, default: '' }, // 'dashboard' | 'applications' | …
    fill:   { type: Boolean, default: false }, // true for Messages (full-height)
  },
};
</script>
```

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
  layout: (h, page) => h(AppShell, { props: { active: 'dashboard' } }, [page]),
  props: { stats: Object, applications: Array }, // from the controller
};
</script>
```
*(Messages page: `props: { active: 'messages', fill: true }`.)*

> **Inertia v2 / Vue 3 note:** if the app is actually on Vue 3 + Inertia v2, use
> `defineOptions({ layout: AppShell })` or `<script setup>` with the
> `<AppShell active="…">` wrapper directly. The structure is identical; only the
> layout-assignment syntax differs. Match whatever the existing pages already do.

### `AppSidebar.vue` / `AppTopbar.vue`
Port the markup from `tailwind/workspace-shell.js` (`navMarkup` / `topbarMarkup`).
Replace the `ROLES`/groups JS object with a `nav` data array and `v-for` the items;
replace `<a href>` with `<Link :href>`; mark the active item with
`:class="{ 'bg-blue-600 text-white': item.id === active }"`. Badges, the user
footer block, and the topbar search/actions all translate 1:1.

### `MarketingLayout.vue`
Port `<site-header>`/`<site-footer>` from `tailwind/site-chrome.js`. Public pages
(`Welcome`, `About`, `Blog`, `Contact`, `Browse`) use this layout. The `active`
prop highlights the current nav link.

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
   over arrays the controller provides. Keep the exact card markup per `DESIGN.md`.
6. **Wire interactions to Vue state** instead of the inline `<script>`:
   - filter chips / tabs → `ref`/`data` + computed filtered list
   - save/bookmark toggles → `v-model` or an Inertia `router.post`
   - settings toggles, modal open/close, password show/hide → component state
   - forms → Inertia `useForm()` (`form.post(route(...))`), show server errors
     under fields.
7. **Keep class names identical.** Do not "tidy" the Tailwind — the classes *are*
   the design. Only structural wrappers change.

---

## 5. Reveal-on-scroll as a Vue directive

Replace the prototype's per-page IntersectionObserver script with one global directive:

```js
// resources/js/directives/reveal.js
export default {
  // Vue 2: bind/inserted; Vue 3: mounted
  inserted(el) {
    el.classList.add('opacity-0','translate-y-[22px]','transition-all','duration-700');
    el.style.transitionTimingFunction = 'cubic-bezier(.2,.7,.2,1)';
    const show = () => { el.style.opacity = '1'; el.style.transform = 'none'; };
    if (!('IntersectionObserver' in window)) return show();
    const io = new IntersectionObserver(es => es.forEach(e => {
      if (e.isIntersecting) { show(); io.unobserve(el); }
    }), { threshold: .08, rootMargin: '0px 0px -5% 0px' });
    io.observe(el);
  },
};
```
Register globally and use `v-reveal` where the prototype had `class="reveal"`.
**Always degrade to visible** (reduced-motion, SSR, no-JS) — never ship content
stuck at `opacity:0`.

---

## 6. Data shapes (suggested)

Mirror what the screens already display so controllers stay thin:

- **Internship**: `id, title, company{name,slug,logoGradient,initials}, location,
  workType (remote|hybrid|onsite), durationWeeks, term, pay{amount,unit}, tags[],
  postedAt, applicantCount, isNew, isSaved, status?`
- **Application**: `id, internship, status (submitted|review|interview|offer|closed),
  appliedAt, updatedAt, timeline[]`
- **Company**: `id, slug, name, city, logoGradient, initials, about, openRoles,
  teamSize, foundedYear, remote, contact{email,website,hiringLead}`
- **User (student)**: `name, initials, headline, location, field, about, skills[],
  education[], experience[], links[], resume, profileStrength, openTo`
- **Message thread**: `id, person{name,initials,gradient,role,company}, role(role applied to),
  online, unread, messages[{from(me|them), body, time, daySep?}]`

Pass status→colour mapping as a small helper (see `DESIGN.md` §2 status table) so
pills render consistently.

---

## 7. Order of work

1. Tokens into `tailwind.config.js`; fonts; shared `@layer` CSS. *(§1)*
2. `AppShell` + `AppSidebar` + `AppTopbar`; `MarketingLayout`. *(§3)*
3. `v-reveal` directive. *(§5)*
4. Port pages back-to-front of importance: **Dashboard → Internships/Browse →
   Internship detail → Applications → Application detail → Saved → Messages →
   Companies → Company detail → Profile → Settings**, then the marketing pages
   and auth. *(§4)*
5. Wire controllers/routes and replace mock data with real props.

Keep each PR to one or two screens so the design can be reviewed against the
matching `tailwind/*.html` reference.
