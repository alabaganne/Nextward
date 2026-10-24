# To do

Unfinished work, small fixes and chores that are not on the roadmap (`FUTURE_PLANS.md`).

## Match imports to the owner's job search (do on 25 October 2026)

Update `CandidateProfiles` and the resume profile so imports favour the roles the owner would actually take. Today a profile only checks kind, level, field, skills and a plain country list; it doesn't know about visas or remote from Tunisia.

What the owner wants:
- **Places:** Europe in general, above all Germany, France, Italy, Spain, Belgium, Romania, Poland, Denmark and Switzerland; and the USA in general (first choice).
- **Visa:** the owner lives in Tunisia and can't work in the US or Europe without visa sponsorship. Hybrid and on-site roles there only count when the listing offers visa sponsorship (or relocation with a visa).
- **Fully remote from Tunisia:** any company that hires fully remote people in Tunisia, like Retain Health, Inc. (Boston, USA), where the owner has worked remotely from Tunisia for over 5 years. Remote roles count when someone in Tunisia can apply (`applicant_scope` `anywhere`, or a region that includes Tunisia, and TN not in `excluded_countries`).
- Fields stay the same: software engineering, web, mobile, general software development, and data science and AI.

To do:
- Give profiles the preferred countries above (Europe plus US) and a rule: on-site or hybrid needs `visa_sponsorship`; remote needs the role to be reachable from Tunisia (`remote_eligibility`).
- Let `opportunities:profile` save these preferences (`--wants` text or new options) and set them on the owner's resume profile.
- Add sources for US and European employers that sponsor visas or hire fully remote worldwide (Greenhouse, Lever and Ashby boards), and point source discovery at them.
- Tests for the new place and visa rules, then update `AGENTS.md` ("Profiles decide what is added") and `README.md`.

## Finish resumes, themes and credits (done)

The plan: candidates keep up to 10 resumes (uploaded PDFs, exports of their profile, AI translations) with one main resume; resumes and kit CVs use the six Resume Studio themes, printed by headless Chrome; kits are written in the listing's own language; credits are on (20 at sign-up, 5 a month, 1 credit per AI call).

Done:
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
- Editing an uploaded PDF: **Make editable** has the AI copy it word for word into an editable resume (a draft to check, 1 credit), tested with a real AI call. A real French translation was checked too.
- Arabic CVs: right to left in every theme, preview, PDF and Word, with self-hosted IBM Plex Sans Arabic and Noto Naskh Arabic (OFL). Kits for Arabic listings can be written in Arabic.
- Bergen (was Sören): the side column's tint now runs down page 2 and later pages in print.
- Checked at phone width (390 px): Profile > Resumes, `Resumes/Show`, the apply page's resume choice and the kit page. On wide screens the resume page's buttons sit above the preview, and both previews stay below the top bar.

Nothing left here. New ideas for resumes go in `FUTURE_PLANS.md`.

## Fix the failing location tests (done)

English and French country names now come from `resources/data/countries.php` (`App\Support\Countries`), made from ICU, so "Tunis, TN" reads "Tunis, Tunisia" with or without PHP's `intl` extension. `intl` is only used for other languages, when it is installed. The three tests in `tests/Feature/OpportunityCleanupTest.php` and `tests/Feature/CountriesTest.php` pass.
