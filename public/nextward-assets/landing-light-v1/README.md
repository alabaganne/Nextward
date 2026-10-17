# Light public-page photo assets

17 photos extracted directly from the four generated page concepts dated 17 October 2026. Each photo has a lossless PNG crop and an optimized WebP for the website. No image was regenerated or upscaled.

These are generated illustrative scenes, not photos of actual Nextward users or companies. The original concepts are in `output/imagegen/nextward-light-concepts-2026-10-17/`.

## Use

Public URLs begin with `/nextward-assets/landing-light-v1/`. Prefer WebP, use the recorded image dimensions and preserve the subject when cropping. Smaller portraits are intended for small cards. The crops retain the original mockup resolution and are not full-resolution camera originals. UI text, buttons, logos and page layouts must be implemented in Vue, not taken from the mockups as images.

The student closing portrait includes its blue photo background. Some generated article photos contain incidental paper or book lettering, which is part of the image rather than website copy.

| File stem | Native dimensions | Intended placement |
|---|---|---|
| `home-hero` | 489 x 264 | Home hero: two people collaborating |
| `home-student-portrait` | 175 x 196 | Home student audience panel |
| `home-professional-portrait` | 171 x 196 | Home professional audience panel |
| `home-guide-portrait` | 162 x 111 | Home career guide preview |
| `professionals-hero` | 423 x 269 | Professionals hero |
| `professionals-guide-shortlist` | 164 x 108 | Professionals shortlist guide preview |
| `professionals-guide-remote` | 167 x 108 | Professionals remote guide preview |
| `students-hero` | 354 x 288 | Students hero |
| `students-guide-application` | 353 x 113 | Students application guide preview |
| `students-guide-library` | 355 x 113 | Students internship guide preview |
| `students-closing-portrait` | 361 x 142 | Students closing banner portrait with blue background |
| `guide-remote-work` | 560 x 298 | Remote eligibility featured article image |
| `guide-cv-application` | 406 x 164 | Internship application article image |
| `guide-job-shortlist` | 407 x 164 | Comparing job opportunities article image |
| `guide-find-internship` | 406 x 180 | Finding an internship article image |
| `guide-hiring-interns` | 407 x 180 | Hiring interns article image |
| `guide-platform` | 380 x 155 | Internship platform article image |

`manifest.json` records each original mockup, crop coordinates, image dimensions and public URL.

The complete implementation brief is saved at the repository root in `PUBLIC_PAGES_REDESIGN_PROMPT.md`. The contact sheet is `output/imagegen/nextward-light-concepts-2026-10-17/asset-contact-sheet.jpg`.

Only the six `guide-*` photos are used, as article images on guide cards, article pages and teasers. They are matched by slug in `resources/js/Components/Blog/guideImages.js`. The other crops (heroes, portraits, teaser crops and the closing portrait) are not used: the owner chose the cartoon mascots for the rest of the public pages.
