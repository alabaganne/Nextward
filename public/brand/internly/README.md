# Internly logo

A minimal lowercase wordmark with a blue symbol combining a person-shaped lowercase “i” and a taller rounded step. The mark connects Internly's people and career-progress themes without a generic checkmark or detailed illustration.

## Files and public URLs

| File | Use |
|---|---|
| `/brand/internly/logo.svg` | Blue symbol and navy wordmark on light backgrounds |
| `/brand/internly/logo-dark.svg` | Light blue symbol and white wordmark on navy backgrounds |
| `/brand/internly/mark.svg` | Blue symbol only |
| `/brand/internly/mark-light.svg` | Light blue symbol only on dark backgrounds |
| `/brand/internly/wordmark.svg` | Navy wordmark only, including stacked layouts |
| `/brand/internly/wordmark-white.svg` | White wordmark only |
| `/brand/internly/logo-mono.svg` | All-navy horizontal logo |
| `/brand/internly/logo-white.svg` | All-white horizontal logo |
| `/brand/internly/favicon.svg` | Symbol with automatic light/dark color adaptation |
| `/brand/internly/logo.png` | Transparent 1032 × 256 raster fallback |
| `/brand/internly/mark-512.png` | Transparent 512 × 512 symbol |
| `/brand/internly/apple-touch-icon.png` | White symbol on blue, 180 × 180 |

All SVGs use outlined geometry, with no external images, scripts, text elements, or font dependencies. The horizontal logo's viewBox is 258 × 64. Render it with its natural aspect ratio, usually 28–36px high in navigation. The symbol's viewBox is 64 × 64. Keep whitespace around the logo and use the supplied light variant on dark surfaces.

The wordmark was outlined from Space Grotesk SemiBold (weight 600), matching the live site's heading family. The font is distributed under the SIL Open Font License; its original notice is included in `OFL-Space-Grotesk.txt`. Font source: https://github.com/google/fonts/tree/main/ofl/spacegrotesk. The symbol was authored as simple vector geometry for this project.

Use the logo through the shared `BrandLogo` component during implementation, preserving its existing props. Do not display the default and reversed logos together as competing identities. Taglines remain separate HTML rather than being baked into the logo.

See `LANDING_PAGE_IMPLEMENTATION_PROMPT.md` at the project root for the full implementation instructions. These files are prepared for integration; application components and favicon references have not been changed yet.
