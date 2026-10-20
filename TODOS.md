# To do

Unfinished work, small fixes and chores that are not on the roadmap (`FUTURE_PLANS.md`).

## Finish resumes, themes and credits (in progress)

Pushed unfinished. The plan: candidates keep up to 10 resumes (uploaded PDFs, exports of their profile, AI translations) with one main resume; resumes and kit CVs use the six Resume Studio themes, printed by headless Chrome; kits are written in the listing's own language; credits are on (20 at sign-up, 5 a month, 1 credit per AI call).

Done so far:
- Themes: `resources/views/cv/*`, `resources/css/cv.css`, fonts in `public/fonts/cv/` (Newsreader, Hanken Grotesk, OFL), `app/Support/CvTemplates.php`, `app/Support/CvPdf.php` (Browsershot, `config/cv.php`). dompdf and `resources/views/pdf/` are gone.
- `app/Support/Cv.php` (one CV shape, `fromProfile`, `current` flag), `resumes` table and `App\Models\Resume`, migrations `2026_10_21_100000` (moves `candidates.resume_path` into `resumes`, drops the old columns, adds `style` to kits and `resume_id` to credit transactions) and `2026_10_21_100100` (tops candidates up to 20 credits).
- `ResumeController` (rewritten) and `ResumeTranslationController`, routes in `routes/candidate_workflows.php`, AI `ResumeTranslator` + `TranslateResume` job, `ResumeReader` returns the language.
- Credits: `Credits` works for kits and resumes, `spend()` for resume reads, `credits:monthly` (scheduled on the 1st), billing on by default, packs 20/60/150, AI routes need a verified email.
- Kits: languages from `ApplicationKits::locales()` (listing language first, then English and French), original listing text in the prompt, `current` flag and translated titles in the prompt, themes and live preview on `Kits/Show.vue`, Word CV, `regenerate` refuses closed listings.
- Vue: `Components/Cv/{CvEditor,CvPreview,StylePicker}.vue`, `Components/Candidate/ResumeList.vue` on the profile page, `Pages/Resumes/Show.vue`, `utils/cvDocx.js`, Settings resume tab links to the profile page.

Left to do:
- [ ] On a new machine: `composer install`, `npm install`, `php artisan migrate`. PDFs need Chrome (`/usr/bin/google-chrome` is found on its own, or set `CV_CHROME_PATH`; or run `npx puppeteer browsers install chrome`).
- [ ] Pick a resume when applying: `ApplicationController::create/edit` send ready resumes and this listing's kit CVs; `ApplicationRequest` takes `resume_id` (own, ready) or `kit_id` (own, this listing, has a CV) and `remove_resume`, and keeps the total at 10 files; the choice is copied into `applications/` as the first attachment with `resume: true` (kit CVs printed with `CvPdf`); `update` swaps or removes it; `destroy` deletes attachment files; `Application::attachmentLinks()` passes the `resume` flag. `Applications/Edit.vue`: a Resume choice above Attachments, defaulting to the kit saved in `sessionStorage` under `nextward.kit-cv.{id}` (set by `Kits/Show.vue`), else the main resume. `Applications/Show.vue`: label it "Resume".
- [ ] Fix the failing tests (16 fail, 3 of them are the `intl` ones below):
  - `ApplicationKitTest`: upload through `POST /resumes` instead of `/profile/resume`; the resume test moves to a new resume test file; seeder test checks `mainResume->path`.
  - `BillingTest`: sign-up credits are now 20 (`BILLING_SIGNUP_CREDITS`), and `phpunit.xml` sets `BILLING_ENABLED=false`; check what each test expects.
  - `PublicCandidateProfileTest`: create `Resume` rows instead of `forceFill(['resume_path' => …])`.
  - `ResumeProfileTest`: upload through `/resumes`; reading now costs a credit with billing on and needs a verified email.
- [ ] New tests: resume management (upload, limit of 10, main, delete promotes the newest, public profile follows the main one, export from profile makes no AI call and costs nothing, replace on and off, style validation, refresh), translation (PDF attached vs JSON, draft then save, same language twice is one call, 422/503, failure refunds), CV rendering (every theme renders with the right headings, `/cv/preview` needs a login), the resume migration, `credits:monthly` (once a month, verified only), kits (German listing gives `de` and the original text in the prompt, Arabic gives 422, regenerate on a closed listing gives 409, style saved). `CV_PDF_FAKE=true` in `phpunit.xml` keeps Chrome out of tests.
- [ ] Copy that says the AI tools are free: `Welcome.vue`, `About.vue`, `Terms.vue` → "20 free credits, then 5 every month". `Privacy.vue`: several resumes, AI translation.
- [ ] Docs: `AGENTS.md` (decisions: Resumes, Resume themes, which may keep their accent rules and colour band since they are the candidate's documents, Kit language, What is free, Payments with credits on, the Stack line with Chrome for PDFs); `README.md` (line 80 still says dompdf; AI, Credits, `credits:monthly`, Chrome setup); `DEPLOY.md` and `VPS_DEPLOYMENT.md` (install Chrome and its libraries, `CV_CHROME_PATH`, `CV_CHROME_NO_SANDBOX`); `design/INTEGRATION.md` (`resumes[]`).
- [ ] Check in the browser: Profile > Resumes (upload, make from profile, translate, main, delete), `Resumes/Show` on desktop and phone, theme thumbnails (each change sends 6 preview requests after 1.5 s), Sören page 2, kit page preview and downloads, Billing page labels.
- [ ] Not started: editing an uploaded PDF (only translation for now); Arabic CVs (right-to-left and an Arabic font).

## Fix the failing location tests

Three tests in `tests/Feature/OpportunityCleanupTest.php` fail on machines without PHP's `intl` extension:

- `test_locations_have_no_repeats…` expects `Tunis, Tunisia` and gets `Tunis, TN`.
- `test_board_pages_get_clean_places…` expects `Ariana, Tunisia` and gets `Ariana, TN`.
- The `opportunities:clean` test expects `location: Tunis, Tunis, TN → Tunis, Tunisia` in its output.

`OpportunityNormalizer` (`app/Importing/OpportunityNormalizer.php`, around line 215) turns country codes into names with `Locale::getDisplayRegion`. Without `intl` it keeps the code, so imported listings show "TN" instead of "Tunisia".

- [ ] Install `intl` locally (`sudo apt install php8.5-intl`) and make sure the server has it too (`DEPLOY.md`, `VPS_DEPLOYMENT.md`).
- [ ] Or keep a small fallback list of country names for when `intl` is missing, so the tests and the site work either way.
- [ ] Run `php artisan test` and check that all three pass.
