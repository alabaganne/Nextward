# Nextward cartoon illustration library

16 original illustrations for a student–company internship platform: four character poses, eight oversized reusable props and four story scenes. Each is supplied as editable SVG and transparent PNG. The contact sheet is a numbered index, not a replacement for the individual files.

## Art direction

Soft, white chibi characters, sweeping brown hair, bright oval eyes, small cheek accents, thick rounded dark outlines and restrained cel shading. The proportions and warmth are inspired by the supplied ABSoft mascot reference; the paths, character construction, poses, accessories and scene layouts are newly drawn. These are proposed Nextward illustration assets, not an official logo system. The wand is a narrative metaphor, not a new logo.

The current product palette informed the blue accents: primary #2563EB, deep blue #1D4ED8, light blues #DBE7FF and #B9D0FF. Coral #F78C78 is an illustration interpretation of the site's coral star accent. Brown, cream and blush support the mascot style. No font files or type dependencies are required. Product reference: https://internly.alabaganne.com/ (the product is named Nextward in this pack).

## Files

- `svg/`: 16 editable vector originals, with explicit viewBox, descriptive title and named groups.
- `png/`: 16 matching transparent PNG exports. Characters and props: 1600 × 1600. Scenes: 1600 × 1105. SVG viewBoxes remain 768 × 768 and 1100 × 760 respectively; PNGs are exported at 1600 px wide with the original aspect ratio preserved (height rounded to the nearest pixel).
- `contact-sheet.png`: numbered, light-background overview.
- `manifest.json`: filenames, dimensions and suggested uses.
- `palette.json`: the illustration color values.
- `build_assets.py`: self-contained source generator; Python 3 and Inkscape are required to rerender. No network calls.

## Asset guide

| # | Asset | Suggested use |
|---|---|---|
| 01 | Student wave | Welcome screen, video intro, friendly onboarding guide |
| 02 | Student prepare CV | Resume checklist, profile completion, skills explanation |
| 03 | Recruiter clipboard | Employer-side review, shortlisting, hiring tutorials |
| 04 | Mentor pointing | Explain a UI feature, reveal a callout or next step |
| 05 | Resume document | CV-upload instruction or candidate profile overview |
| 06 | Internship listing board | Browse opportunities, explain a listing card |
| 07 | Search internships | Search/filter tutorial or matching introduction |
| 08 | Application envelope | Application submission or confirmation |
| 09 | Interview calendar | Interview scheduling or reminder explanation |
| 10 | Portfolio laptop | Portfolio creation, project examples or profile completion |
| 11 | Learning books and cap | Skills development, readiness or learning resources |
| 12 | Wand and stars | Delight, progress reveal or chapter transition |
| 13 | Discover internship | Step 1 of a student journey explainer |
| 14 | Prepare application | Step 2: CV, portfolio and application preparation |
| 15 | Student–company conversation | Step 3: connect and discuss the opportunity |
| 16 | First-day celebration | Step 4: accepted opportunity and onboarding |

## Editing and motion

Open an SVG in Figma, Illustrator or Inkscape. The artwork uses ordinary paths, rectangles and ellipses; there are no external images, scripts, fonts, filters or embedded raster content. Named groups separate face, hair, body, limbs, held objects and scene components. Each element has a unique ID. These are layered illustrations, not skeletal animation rigs.

For a tutorial, keep a character in one corner and pair it with a single prop beside the UI. For an explainer, use scenes 13–16 as the four story beats. Suggested motion: gentle 2–4% character bob, a small pivot of the wave arm, 150–250 ms scale-and-fade prop reveals, and slow staggered star pops. Keep movement subtle and avoid distorting the linework. Use the SVG at any output size; the PNGs are convenient previews and overlays. The outer canvas remains transparent; scene accent ovals are intentional editable objects. Contact sheet labels are not present in the individual assets.

## Quality checks

All 16 SVGs were parsed and exported with Inkscape. The rendered contact sheet was visually inspected for silhouettes, spacing, clipping, readability and consistency. PNG alpha bounds stay within each canvas. SVGs contain no external references or font-dependent text. IDs are unique per document.
