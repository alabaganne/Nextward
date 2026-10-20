# To do

Unfinished work, small fixes and chores that are not on the roadmap (`FUTURE_PLANS.md`).

## Finish resumes, themes and credits (in progress)

The plan: candidates keep up to 10 resumes (uploaded PDFs, exports of their profile, AI translations) with one main resume; resumes and kit CVs use the six Resume Studio themes, printed by headless Chrome; kits are written in the listing's own language; credits are on (20 at sign-up, 5 a month, 1 credit per AI call).

Done so far:
- Themes: `resources/views/cv/*`, `resources/css/cv.css`, fonts in `public/fonts/cv/` (Newsreader, Hanken Grotesk, OFL), `app/Support/CvTemplates.php`, `app/Support/CvPdf.php` (Browsershot, `config/cv.php`). dompdf and `resources/views/pdf/` are gone.
- `app/Support/Cv.php` (one CV shape, `fromProfile`, `current` flag), `resumes` table and `App\Models\Resume`, migrations `2026_10_21_100000` (moves `candidates.resume_path` into `resumes`, drops the old columns, adds `style` to kits and `resume_id` to credit transactions) and `2026_10_21_100100` (tops candidates up to 20 credits).
- `ResumeController` (rewritten) and `ResumeTranslationController`, routes in `routes/candidate_workflows.php`, AI `ResumeTranslator` + `TranslateResume` job, `ResumeReader` returns the language.
- Credits: `Credits` works for kits and resumes, `spend()` for resume reads, `credits:monthly` (scheduled on the 1st), billing on by default, packs 20/60/150, AI routes need a verified email.
- Kits: languages from `ApplicationKits::locales()` (listing language first, then English and French), original listing text in the prompt, `current` flag and translated titles in the prompt, themes and live preview on `Kits/Show.vue`, Word CV, `regenerate` refuses closed listings.
- Vue: `Components/Cv/{CvEditor,CvPreview,StylePicker}.vue`, `Components/Candidate/ResumeList.vue` on the profile page, `Pages/Resumes/Show.vue`, `utils/cvDocx.js`, Settings resume tab links to the profile page.
- Applying: the apply page lets the candidate pick a ready resume, a tailored CV for this listing (printed when they apply) or none; the main resume is picked by default, or the kit CV when they come from the kit page. A copy is stored as the first file with `resume: true`, counts toward the 10 files, can be swapped or removed when editing, and withdrawing deletes the files (`ApplyWithResumeTest`).
- Tests: the old failing tests are fixed, and resumes, translations, CV themes, the resume migration, `credits:monthly` and kit languages have tests. A translation asked for with no credit left no longer leaves a stuck draft behind.
- Copy and docs: Home, About, Terms and Privacy speak of credits (20 free, then 5 a month), several resumes and AI translation; `AGENTS.md`, `README.md`, `DEPLOY.md`, `VPS_DEPLOYMENT.md` and `design/INTEGRATION.md` are up to date.
- Checked in the browser on desktop (20 October 2026): profile resumes, `Resumes/Show` (the page was blank because `structuredClone` can't copy Vue props; fixed), `/resumes/new`, theme thumbnails, the kit page's "Apply with this CV and letter", applying with a kit CV (printed by Chrome), editing the application, the application page and the Credits page.

Left to do:
- [ ] On a new machine: `composer install`, `npm install`, `php artisan migrate`. PDFs need Chrome (`/usr/bin/google-chrome` is found on its own, or set `CV_CHROME_PATH`; or run `npx puppeteer browsers install chrome`).
- [ ] Check in the browser on a phone: Profile > Resumes, `Resumes/Show`, the apply page's resume choice, and the kit page. Also try a translation and Sören page 2 with a real AI key.
- [ ] Not started: editing an uploaded PDF (only translation for now); Arabic CVs (right-to-left and an Arabic font).

## Fix the failing location tests

Three tests in `tests/Feature/OpportunityCleanupTest.php` fail on machines without PHP's `intl` extension:

- `test_locations_have_no_repeats…` expects `Tunis, Tunisia` and gets `Tunis, TN`.
- `test_board_pages_get_clean_places…` expects `Ariana, Tunisia` and gets `Ariana, TN`.
- The `opportunities:clean` test expects `location: Tunis, Tunis, TN → Tunis, Tunisia` in its output.

`OpportunityNormalizer` (`app/Importing/OpportunityNormalizer.php`, around line 215) turns country codes into names with `Locale::getDisplayRegion`. Without `intl` it keeps the code, so imported listings show "TN" instead of "Tunisia".

- [ ] Install `intl` locally (`sudo apt install php8.5-intl`). The server docs now list it (`VPS_DEPLOYMENT.md`). The three tests pass on a machine that has it.
- [ ] Or keep a small fallback list of country names for when `intl` is missing, so the tests and the site work either way.
- [ ] Run `php artisan test` and check that all three pass.
