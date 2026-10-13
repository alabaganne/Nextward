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
---

## 1. Personality

Nextward is a **calm, modern, trustworthy SaaS product** for students and companies.

- **Clean, not flashy.** White space does the work. One accent colour, used with intent.
- **Editorial headings, friendly body.** A geometric display face for headings; a
  warm humanist sans for body copy.
- **Flat surfaces.** Use spacing, thin neutral borders, and dividers for separation.
  Avoid adding card or button shadows and decorative glows.
- **Generous radii** (12–26px). Nothing is sharp-cornered.
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
| `blue-50` | `#eef4ff` | Tinted backgrounds, soft chips |
| `blue-100` | `#dbe7ff` | Chip borders, soft outlines |
| `blue-200` | `#b9d0ff` | Hover borders on cards |
| `blue-300` | `#8ab2ff` | Hover borders, avatar gradient stops |
| `blue-400` | `#5a8dff` | Gradient stops |
| `blue-500` | `#3b82f6` | Accents, focus rings |
| `blue-600` | `#2563eb` | **Primary action.** Buttons, active nav, links |
| `blue-700` | `#1d4ed8` | Primary hover, link text |
| `navy-700/800/900` | `#0f2150` / `#0a1738` / `#070f26` | Dark sections, sidebar, footer, hero panels |
| `ink` | `#0c1322` | Primary text |
| `ink-700` | `#283449` | Secondary-strong text, labels |
| `muted` | `#5b6677` | Body/secondary text |
| `muted-2` | `#8a93a3` | Tertiary text, meta, icon strokes |
| `line` | `#e7ebf1` | Default hairline borders |
| `line-2` | `#eef1f6` | Inner dividers (lighter) |
| `paper` | `#ffffff` | Cards, surfaces |
| `paper-2` | `#f6f8fc` | App background, inset wells |
| `paper-3` | `#eef2f9` | Neutral chips, subtle fills |

**Status colours** (used for pills/badges — keep these literal hex, they're outside the brand ramp):

| Meaning | Text | Background |
|---|---|---|
| Success / Interview / Applied | `#047857` | `#dcfce7` |
| Warning / In review | `#b45309` | `#fef3c7` |
| Info / Viewed | `blue-700` | `blue-50` |
| Offer | `#6d28d9` | `#ede9fe` |
| Neutral / Submitted / Closed | `muted` | `paper-3` |
| Danger / Destructive | `#dc2626` | `#fef2f2` (border `#fecaca`) |

**Company/avatar logos** use a 140° linear gradient, picked per-company and kept
consistent across screens:
`linear-gradient(140deg,#6366f1,#4338ca)` (Novabyte), `…#3b82f6,#1d4ed8` (Atlas),
`…#10b981,#047857` (Greenfield), `…#f59e0b,#d97706` (Stride), `…#ec4899,#be185d` (Lumen),
`…#06b6d4,#0e7490` (Vertex), `…#0ea5e9,#0369a1` (Realinflo). Initials in white,
`font-display`, bold.

---

## 3. Typography

Workspace typography uses two families loaded in `resources/views/app.blade.php`:
public/auth body text uses Inter (`font-sans`) with the same display family.

- **Display — `Space Grotesk`** (`font-display`): all headings, numbers/stats,
  logo wordmark, eyebrows, button-like figures. Weights 500–700.
- **Body — `Plus Jakarta Sans`** (`font-body`): everything else. Weights 400–800.

```html
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800&display=swap" rel="stylesheet">
```

Scale & conventions:

- Page H1 (workspace): `font-display text-[32px] font-semibold tracking-[-.02em]`
- Section H2: `font-display font-semibold tracking-[-.02em] text-[clamp(30px,4vw,46px)]`
- Card title: `font-display text-[18px] font-semibold`
- Body: `text-[15px]` / `text-[15.5px]`, `leading-[1.6]`–`1.7`, colour `muted` or `ink-700`
- Tighten tracking on large display text (`tracking-[-.02em]`); never on body.
- `text-wrap: pretty` / `text-balance` on headings where supported.

### The eyebrow (signature element)
Small uppercase blue label above most section/page titles:
```html
<span class="font-display text-[12.5px] font-semibold tracking-[.18em] uppercase text-blue-600">Overview</span>
```

---

## 4. Shape, surfaces, spacing

- **Radii:** cards `rounded-[18px]`, large feature panels `rounded-[26px]`,
  controls/buttons `rounded-[12px]`, chips/pills `rounded-full` or `rounded-lg`,
  small inner items `rounded-[10px]`–`[11px]`.
- **Surfaces:** flat fills and borders. Existing shadow tokens are retained for
  compatibility with older workspace screens, not as a default for new work.
- **Borders:** `border border-line` for cards; `border-line-2` for inner dividers.
- **Page max width:** `max-w-wrap` (1200px), centered; responsive workspace
  gutters. Public/auth spacing follows their existing layouts.
- **Rhythm:** cards padded `p-6`/`p-[22px]`; gaps `gap-4`/`gap-6`. Use
  spacing to establish groups before adding another card.

---

## 5. Core workspace component patterns

**Primary button**
```html
<a class="inline-flex items-center gap-2.5 font-semibold text-[15px] px-[22px] py-[13px] rounded-[12px] bg-blue-600 text-white hover:bg-blue-700 transition-colors">Label</a>
```

**Secondary button**
```html
<a class="inline-flex items-center gap-2.5 font-semibold text-[15px] px-[22px] py-[13px] rounded-[12px] bg-white text-ink border border-line hover:border-blue-300 hover:text-blue-700 transition-colors">Label</a>
```

**Card**
```html
<article class="bg-white border border-line rounded-[18px] p-6 transition-colors hover:border-blue-200"> … </article>
```

**Tag / chip** — `inline-flex items-center px-[11px] py-[5px] rounded-lg text-xs font-semibold bg-paper-3 text-ink-700`

**Status pill**
```html
<span class="inline-flex items-center gap-1.5 text-xs font-semibold px-[11px] py-[5px] rounded-full text-[#047857] bg-[#dcfce7]">
  <span class="w-1.5 h-1.5 rounded-full bg-[#059669]"></span> Interview
</span>
```

**Input** — `border-[1.5px] border-line rounded-[11px] px-3.5 py-3 text-[14.5px] bg-white focus:outline-none focus:border-blue-500 placeholder:text-muted-2`

**Avatar / logo** — `w-12 h-12 rounded-[13px] grid place-items-center text-white font-bold font-display` + inline gradient `style`.

**Icons:** use library components: the shared `Icon.vue` wrapper uses
`@heroicons/vue` for public/auth UI; workspace components import named icons from
`@lucide/vue`. Size icons explicitly, mark decorative icons `aria-hidden="true"`,
and give icon-only buttons an accessible label. Underline link text only. Do not
handwrite SVG paths, embed SVG icon strings, or substitute Unicode/emoji icons.
---

## 6. Layout chrome

- **Workspace (signed-in)**: dark navy sidebar (248px) + white topbar with search.
  In HTML this is the `<app-shell active="…">…</app-shell>` wrapper. In Vue it's the
  `AppShell` persistent layout with `<slot/>`. The page only writes the content;
  the chrome is never duplicated per page.
- **Public pages**: use `Public.vue` for the approved warm header/footer and
  `Guest.vue` for authentication. Do not recreate the obsolete `MarketingLayout`
  or `AuthSplit` prototypes.
- **Dark sections**: the approved landing page uses a photographic background,
  dark overlay, and light text. Follow its existing implementation and the public
  design notes; avoid adding decorative glows or a new hero treatment by default.
---

## 7. Motion

- Content is visible by default. Preserve the public/auth pages' static entrances.
- Hover feedback uses colour, border, or underline changes around 150–200ms.
  Keep controls and cards in place without adding shadows or glows.
- Honour reduced motion for any optional transitions. Avoid looping decoration
  and effects that delay reading or interaction.

---

## 8. Voice

- Headings: confident, short, sentence case. ("Your next internship is one click away.")
- Body: plain, warm, second person. No corporate filler, no exclamation spam.
- Buttons: verb-first ("Browse internships", "Save preferences", "Message recruiter").
- Numbers are concrete; never invent metric slop to fill space. One thousand no's
  for every yes — if a section feels empty, solve it with layout, not filler.
