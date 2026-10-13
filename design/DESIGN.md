# Internly — Design Language

This is the single source of truth for Internly's visual style. Read this before
building any new screen (in HTML, or in Vue — see `INTEGRATION.md`). The goal is
that a screen built six months from now is indistinguishable from the ones built
today.

The reference implementation is the set of `tailwind/*.html` files in this repo.
When in doubt, open the closest existing screen and copy its structure.

---

## 1. Personality

Internly is a **calm, modern, trustworthy SaaS product** for students and companies.

- **Clean, not flashy.** White space does the work. One accent colour, used with intent.
- **Editorial headings, friendly body.** A geometric display face for headings; a
  warm humanist sans for body copy.
- **Soft, deep shadows instead of hard borders** for elevation. Thin neutral hairlines
  for separation.
- **Generous radii** (12–26px). Nothing is sharp-cornered.
- **Restrained motion.** Gentle reveal-on-scroll, small hover lifts. Never bouncy,
  never looping.

Avoid: heavy gradients everywhere, neon, emoji as UI, dense data tables, hard
drop-shadows, more than one accent hue, decorative SVG illustration.

---

## 2. Colour tokens

These are defined as Tailwind theme colours (see `tailwind/setup.js` / your
`tailwind.config.js`). **Always use the token, never a raw hex in markup.**

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

Two families, loaded from Google Fonts:

- **Display — `Space Grotesk`** (`font-display`): all headings, numbers/stats,
  logo wordmark, eyebrows, button-like figures. Weights 500–700.
- **Body — `Plus Jakarta Sans`** (`font-body`): everything else. Weights 400–800.

```html
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800&display=swap" rel="stylesheet">
```

Scale & conventions:

- Page H1 (workspace): `font-display text-[32px] font-semibold tracking-[-.02em]`
- Marketing hero H1: `font-display font-semibold tracking-[-.02em] text-[clamp(40px,5.4vw,68px)] leading-[1.04]`
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

## 4. Shape, elevation, spacing

- **Radii:** cards `rounded-[18px]`, large feature panels `rounded-[26px]`,
  controls/buttons `rounded-[12px]`, chips/pills `rounded-full` or `rounded-lg`,
  small inner items `rounded-[10px]`–`[11px]`.
- **Shadows (tokens):** `shadow-xs` (resting card), `shadow-card` (hover lift),
  `shadow-big` (hero/floating), `shadow-cta` (primary button glow). Prefer borders
  + `shadow-xs` at rest; reserve big shadows for genuinely floating elements.
- **Borders:** `border border-line` for cards; `border-line-2` for inner dividers.
- **Page max width:** `max-w-wrap` (1200px), centered, `px-7` gutters on marketing;
  `px-10` on workspace content.
- **Rhythm:** marketing sections `py-[104px]`; cards padded `p-6`/`p-[22px]`;
  gaps `gap-4`/`gap-6`. Be generous — when unsure, add space.

---

## 5. Core components (copy these patterns verbatim)

**Primary button**
```html
<a class="inline-flex items-center gap-2.5 font-semibold text-[15px] px-[22px] py-[13px] rounded-[12px] bg-blue-600 text-white shadow-cta hover:bg-blue-700 hover:-translate-y-px transition-all">Label</a>
```

**Secondary button**
```html
<a class="inline-flex items-center gap-2.5 font-semibold text-[15px] px-[22px] py-[13px] rounded-[12px] bg-white text-ink border border-line hover:border-blue-300 hover:text-blue-700 transition-all">Label</a>
```

**Card**
```html
<article class="bg-white border border-line rounded-[18px] p-6 shadow-xs transition-all hover:-translate-y-1 hover:shadow-card hover:border-blue-200"> … </article>
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

**Icons:** inline `<svg viewBox="0 0 24 24" fill="none">` with `stroke="currentColor"
stroke-width="2"`, `stroke-linecap/linejoin="round"`. Sized 15–22px via parent
`[&_svg]:w-… [&_svg]:h-…`. Line icons only — never filled blobs.

---

## 6. Layout chrome

- **Workspace (signed-in)**: dark navy sidebar (248px) + white topbar with search.
  In HTML this is the `<app-shell active="…">…</app-shell>` wrapper. In Vue it's the
  `AppShell` persistent layout with `<slot/>`. The page only writes the content;
  the chrome is never duplicated per page.
- **Marketing (public)**: translucent sticky white header + dark navy footer
  (`<site-header>` / `<site-footer>`). In Vue, the `MarketingLayout`.
- **Dark sections** (hero panels, CTAs, sidebars, footer): navy-800→900 gradient,
  often with a faint dotted/grid overlay masked with a radial gradient, and a blue
  radial glow top-corner. Text turns to `#aebfdd` / `#9fb0ce`; headings white.

---

## 7. Motion

- **Reveal on scroll:** start `opacity-0 translate-y-[22px]`, transition
  `duration-700 ease-[cubic-bezier(.2,.7,.2,1)]`, reveal via IntersectionObserver.
  Stagger siblings ~70ms. Must degrade to visible (print / reduced-motion / no-JS).
- **Hover:** cards lift `-translate-y-1` + `shadow-card`; buttons `-translate-y-px`;
  links nudge their arrow (`gap` grows). Keep `transition-all`/`transition-colors`
  ~150–200ms.
- No infinite loops on content. One or two subtle floating accents on the hero is
  the ceiling.

---

## 8. Voice

- Headings: confident, short, sentence case. ("Your next internship is one click away.")
- Body: plain, warm, second person. No corporate filler, no exclamation spam.
- Buttons: verb-first ("Browse internships", "Save preferences", "Message recruiter").
- Numbers are concrete; never invent metric slop to fill space. One thousand no's
  for every yes — if a section feels empty, solve it with layout, not filler.
