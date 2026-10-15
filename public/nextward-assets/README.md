# Nextward website illustrations

Ready-to-use assets for the landing page and public website. Browser URLs start with `/nextward-assets/`, not `/public/nextward-assets/`.

The current landing page uses the lighter editorial assets in [`landing/`](landing/README.md). The illustrations and loops below remain available for other uses.

| PNG | Dimensions | Suggested use |
|---|---|---|
| `stills/student-avatar.png` | 1254 × 1254 | Student role introduction |
| `stills/recruiter-avatar.png` | 1254 × 1254 | Company role introduction |
| `stills/conversation.png` | 1536 × 1024 | Hero or messaging section |
| `stills/student-workspace.png` | 1536 × 1024 | Student benefits or discovery |
| `stills/company-workspace.png` | 1536 × 1024 | Company benefits or application review |
| `stills/shared-success.png` | 1536 × 1024 | Closing call to action or completion |

All still images have real alpha transparency. Keep the complete scene visible with `object-fit: contain`; portraits can be cropped to circles. The characters are illustrations, not actual users or testimonials.

## Motion

For each of `conversation` and `success`, `loops/` contains:

- `<name>.webm`: transparent VP9 animation.
- `<name>.mp4`: H.264 animation with an opaque pale `#F6F8FC` background.
- `<name>-poster.png`: transparent still poster for the animation.

Both loops are silent, 1200 × 800, six seconds, and 30 fps. Use WebM on arbitrary backgrounds where alpha video is supported, MP4 on pale backgrounds, and PNG for reduced-motion or unsupported playback. Supply explicit dimensions and a poster, use muted inline playback, provide a pause control, and pause offscreen video.

`manifest.json` lists all twelve files, their public URLs, dimensions, file sizes, and SHA-256 checksums. The public copies match the original generated assets exactly.

The new logo lives in `/brand/nextward/`. The complete implementation prompt is `LANDING_PAGE_IMPLEMENTATION_PROMPT.md` at the project root. Original prompts, previews, validation results, and editable HyperFrames sources remain under `output/nextward-assets/`.

## Mascots

`mascots/` holds the cartoon pack from `brand/cartoon-assets-2026-10-14/`, used on the public landing pages. Files 01–16 are the editable SVGs (small, sharp at any size). Files 17–20 are WebP copies of the large PNGs: 17 and 18 are 900 × 900 with transparency, 19 is the 1672 × 941 workspace background with an empty left side for text, and 20 is the 941 × 1672 portrait background.
