# Nextward — Design Language

This document is a workspace design reference retained from the earlier prototype.
The implemented Vue components and current user-approved direction take precedence.
For the public landing page and authentication pages, follow
[LANDING_PAGE_DESIGN.md](../LANDING_PAGE_DESIGN.md) and the existing `Public.vue` /
`Guest.vue` layouts. Keep their flat surfaces and restrained interactions.

Prototype helpers under `design/tailwind/` are historical references, not build
configuration or instructions to replace the approved public/auth designs. See
[INTEGRATION.md](INTEGRATION.md) for the current Laravel 13, Vue 3, Inertia 3,
Tailwind 4, and Vite 8 integration.

> **Redesign (26 October 2026).** The whole app, workspace included, now
> follows the Hired 128 Plus Webflow template: the fonts, tokens and patterns
> below are live everywhere.

---

## 1. Personality

Nextward is a **calm, modern, trustworthy SaaS product** for students,
professionals and companies, covering internships and jobs.

- **Clean, not flashy.** White space does the work. One accent colour, used with intent.
- **One friendly geometric face.** Poppins for headings and body; uppercase,
  widely spaced labels and buttons.
- **Flat surfaces.** Use spacing, thin neutral borders, and dividers for separation.
  Avoid adding card or button shadows and decorative glows.
- **Soft corners** (10px, `rounded-card`). Nothing is sharp-cornered.
- **Restrained motion.** Subtle colour and underline changes, without hover lifts
  or layout movement. Keep content visible and respect reduced motion.

Avoid: heavy gradients everywhere, neon, emoji as UI, dense data tables, hard
drop-shadows, more than one accent hue, decorative SVG illustration.

---

## 2. Colour tokens

Current tokens are defined with Tailwind 4 `@theme` in
`resources/css/app.css`; shared workspace patterns live in
`resources/css/workspace.css`. Prefer these tokens for new workspace UI.

| Token | Hex | Use |
|---|---|---|
| `brand` / `blue-600` | `#0047ff` | **Primary action.** Buttons, active nav, links |
| `brand-dark` / `blue-700` | `#003ad6` | Primary hover, link text |
| `blue-50` / `pale` / `paper-2` | `#f2f6ff` | Ice bands, footer, app background, soft chips |
| `blue-100` / `blue-200` | `#e2eaff` / `#c4d4ff` | Chip borders, soft outlines |
| `blue-300` / `blue-400` | `#96b3ff` / `#5f8bff` | Light text and accents on navy |
| `blue-500` | `#2f6bff` | The highlighted heading word on navy |
| `navy` / `navy-800` / `ink` | `#001f6d` | Headings, hero, dark bands, sidebar |
| `navy-600` / `navy-700` / `navy-900` | `#183b94` / `#0b2a80` / `#00175a` | Navy hovers and deeper panels |
| `ink-700` | `#2c3a63` | Long reading text, labels |
| `subtle` / `muted` | `#656d80` | Body and secondary text |
| `subtle-2` / `muted-2` | `#6b7387` / `#8d94a6` | Meta text, placeholders, icon strokes |
| `mist` / `mist-2` | `#d6def5` / `#a9b7dc` | Body text on navy |
| `line` | `#e6e9f0` | Default hairline borders |
| `line-2` | `#eef1f6` | Inner dividers (lighter) |
| `paper` | `#ffffff` | Cards, surfaces |
| `paper-3` | `#e8eefc` | Neutral chips, subtle fills |

`slate-*` and `gray-*` are the same blue-grey ramp (`#f7f8fb` to `#0f1424`),
with 500 and darker passing AA contrast on white.

**Status colours** for pills live in `resources/js/utils/status.js` (literal hex,
used by `StatusPill`). In templates use Tailwind's green, amber, red and violet
50/700 pairs for the same meanings.

| Meaning | Text | Background |
|---|---|---|
| Success / Interview / Applied | `#047857` | `#dcfce7` |
| Warning / In review | `#b45309` | `#fef3c7` |
| Info / Viewed | `#0039d1` | `#e6edff` |
| Offer | `#6d28d9` | `#ede9fe` |
| Neutral / Submitted / Closed | `#4b5670` | `#f2f6ff` |
| Danger / Destructive | `#dc2626` | `#fef2f2` (border `#fecaca`) |

**Company and person initials** sit on a solid colour: `logoColor(name)` in
`resources/js/utils/brand.js` picks one from a small set (cobalt, navy, light
cobalt, green, amber, pink, teal) by the name, so a company always gets the same
one. No gradients. Initials are white, `font-medium`.

---

## 3. Typography

One family everywhere: **Poppins** (`font-sans`, `font-body` and `font-display`
all point at it), weights 400, 500 and 600. Keep text light like the template:
body, labels and buttons 400, headings and numbers 500; 600 only for the logo
and small count badges.

The woff2 files (Latin and Latin Extended) live in `resources/fonts/` and the
`@font-face` rules in `resources/css/fonts.css`. Do not load fonts from Google
Fonts or any other outside service; the privacy policy says pages contact no
other site for fonts. To add a weight, add its woff2 file there and a matching
`@font-face` rule.

Scale & conventions:

- Hero H1: 500, `clamp(40px,5.6vw,72px)`, `leading-[1.1]`
- Section H2: 400, `clamp(30px,3.4vw,46px)`, `leading-[1.2]`
- Card title: `text-[19px]`–`[22px] font-medium`
- Body: `text-[16px]`–`[18px]`, `leading-[1.7]`, colour `subtle` or `ink-700`
- No negative letter spacing; uppercase labels and buttons use `tracking-caps` (.1em).
- `text-wrap: pretty` / `text-balance` on headings where supported.

### The eyebrow (signature element)
Small uppercase blue label above most section/page titles:
```html
<span class="text-[15px] tracking-caps uppercase text-brand">Overview</span>
```

---

## 4. Shape, surfaces, spacing

- **Radii:** cards, buttons, fields and chips `rounded-card` (10px); small tags
  `rounded-md`.
- **Surfaces:** flat fills and borders. There are no shadow tokens; never add
  `shadow-*` or `box-shadow` to cards, menus, drawers or the navbar.
- **Borders:** workspace cards are white with no border on the ice page
  (`bg-white rounded-card`); inside a white card, use an ice tint (`bg-pale`)
  for inner blocks and `border-line-2` for dividers. Public cards on white keep
  `border border-line`.
  Never put a border on only one side (top accent stripe, left quote bar) of a
  card, panel, quote or email block; use no border, a soft tint, or a solid
  blue fill with light text instead.
- **Page max width:** `max-w-wrap` (1200px), centered; responsive workspace
  gutters. Public/auth spacing follows their existing layouts.
- **Rhythm:** cards padded `p-6`/`px-7 py-6`; gaps `gap-4`/`gap-6`. Use
  spacing to establish groups before adding another card.

---

## 5. Core workspace component patterns

**Buttons** are the shared `.cta` classes in `resources/css/elements.css`, the
same on public pages and in the workspace: `cta cta-primary` (cobalt),
`cta cta-secondary` (white with a cobalt border), `cta cta-light` and
`cta cta-ghost` (on navy), and `cta cta-danger` (red outline) for delete.
Add `cta-sm` for smaller buttons in rows, cards and tool bars. Buttons are
weight 400.
Every button, `role="button"` element and `<summary>` shows a pointer cursor
(set once in `elements.css`), so there is no need to add `cursor-pointer`.

**Card**
```html
<article class="bg-white border border-transparent rounded-card p-6 transition-colors hover:border-brand/40"> … </article>
```

**Tag / chip** — `inline-flex items-center px-2.5 py-[5px] rounded-md text-xs font-medium bg-pale text-ink-700`

**Status pill** — `<StatusPill :status="…" />`: a `rounded-md` pill with a dot,
coloured from `utils/status.js`.

**Input** — `.fld` from `workspace.css`: filled ice (`bg-pale`) with no visible
border, 15.5px weight 400, `rounded-card`, a cobalt border and white fill on
focus. A field that sits straight on the ice page gets `bg-white`. Labels are
14px navy with no extra weight. Checkboxes use `.ck`.

**Page header** — `PageHeader.vue` with `title`, `description`, an optional
uppercase `label`, and `tone="navy"` for a navy band with line art (the
dashboard), which also takes `#title`, `#actions` and `#art` slots.

**Avatar / logo** — `CompanyLogo`, or `size-12 rounded-card grid place-items-center text-white font-medium` on `logoColor(name)`.

**Icons:** use library components: the shared `Icon.vue` wrapper uses
`@heroicons/vue` for public/auth UI; workspace components import named icons from
`@lucide/vue`. Size icons explicitly, mark decorative icons `aria-hidden="true"`,
and give icon-only buttons an accessible label. Underline link text only. Do not
handwrite SVG paths, embed SVG icon strings, or substitute Unicode/emoji icons.
---

## 6. Layout chrome

- **Workspace (signed-in)**: a navy sidebar (248px) with the thin line art, light
  links and a solid cobalt active item, and a white 72px topbar with search (a
  `rounded-card` field with a clear button; `/` focuses it from anywhere outside a
  text field), credits, notifications and the account menu. The page background is ice.
  In HTML this is the `<app-shell active="…">…</app-shell>` wrapper. In Vue it's the
  `AppShell` persistent layout with `<slot/>`. The page only writes the content;
  the chrome is never duplicated per page.
- **Public pages**: use `Public.vue` for the navy header/footer and
  `Guest.vue` for authentication. Do not recreate the obsolete `MarketingLayout`
  or `AuthSplit` prototypes.
- **Public sections**: a dark navy hero with cartoon mascots, then white, cool grey
  and ice blue sections, and a flat cobalt closing banner. No cream, glows or
  decorative gradients. See [LANDING_PAGE_DESIGN.md](../LANDING_PAGE_DESIGN.md).
---

## 7. Motion

- Content is visible by default. Preserve the public/auth pages' static entrances.
- Hover feedback uses colour, border, or underline changes around 150–200ms.
  Keep controls and cards in place without adding shadows or glows.
- Honour reduced motion for any optional transitions. Avoid looping decoration
  and effects that delay reading or interaction.

---

## 8. Voice

- Headings: confident, short, sentence case. ("Find your next role.")
- Body: plain, warm, second person. No corporate filler, no exclamation spam.
- Buttons: verb-first ("Browse roles", "Save preferences", "Message recruiter").
- Say "role" ("offre" in French) when a sentence covers both internships and jobs,
  and "candidate" when it covers both students and professionals. "Opportunity"
  stays a code name only.
- Numbers are concrete; never invent metric slop to fill space. One thousand no's
  for every yes — if a section feels empty, solve it with layout, not filler.
