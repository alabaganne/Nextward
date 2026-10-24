# Future plans

Nextward now supports students and professionals, internships and jobs, Tunisia and worldwide locations, public SSR pages, approved-source imports, external application tracking and optional saved-search digests. The implementation does not establish live source agreements or activate production email and indexing services.

## Rollout work

- [ ] Rehearse the production database upgrade and private attachment migration on a restored copy, with database and storage backups.
- [ ] Deploy the Node.js 24 SSR service, database queue worker and scheduler; monitor rendered public HTML and failed jobs.
- [ ] Secure permission and republication evidence for the international sources. Do not assume access to a board grants scraping or republication rights.
- [ ] Start approved sources in review mode, check field quality and attribution, then allow automatic publication source by source.
- [ ] Measure real inventory coverage, freshness, duplicate rates and source failures before expanding the catalogue or making coverage claims.
- [ ] Verify the production domain and sitemaps in Search Console. Review detail-page structured data, canonical URLs and indexing coverage.
- [ ] Mail goes through Resend (sender `nextward@alabaganne.com`). Add `RESEND_API_KEY`, send a test of each email to controlled accounts, then enable consent-based digests. Move the sender to a Nextward domain once one is verified in Resend.
- [ ] Optionally configure the Google Indexing API service account, ownership and quota; monitor the outbox after enabling it.
- [x] Update inherited test expectations for the new routes, optional worldwide profile fields, live homepage, sitemap index and migration order. The authorized updates passed the full suite: 59 tests and 726 assertions.

- [ ] After deploying the bilingual listings, run `php artisan opportunities:translate --limit=500` a few times so the open listings get their English or French version faster than the hourly 200.
- [ ] Before turning on paid credits: set pack prices, open Konnect, Flouci and Lemon Squeezy merchant accounts (check that Lemon Squeezy still takes new stores and pays out to a Tunisian bank), create one Lemon Squeezy variant per pack, test each gateway in sandbox, update the Terms and Privacy pages, then set `BILLING_ENABLED=true`.

Operational commands, environment switches and deployment steps are documented in `README.md`.

## Product improvements after observing real usage

- [ ] Expand source coverage using approved feeds and employer integrations, guided by unmet demand from students and professionals.
- [ ] Improve remote eligibility evidence and country coverage without treating an unknown restriction as worldwide availability.
- [ ] Refine recommendations from candidate feedback and preference quality before adding more complex matching.
- [ ] Add useful editorial landing pages only where there is enough distinct content and current inventory.
- [ ] Evaluate French and Arabic public content and search demand, with a separate localization and SEO plan.
- [ ] Revisit live messaging updates if employer and candidate usage justify restoring the existing realtime integration.

## Founder introduction and product walkthrough

- [ ] Create a walkthrough video with Claude, with voice work through ElevenLabs.
- [ ] Open with Ala Baganne introducing himself and explaining why he built Nextward.
- [ ] Show the student journey: internships, PFE or graduate opportunities, saved roles and application tracking.
- [ ] Show the professional journey: experience and location filters, worldwide remote eligibility, jobs and saved searches.
- [ ] Explain applying on an employer's website and then confirming the application in Nextward.
- [ ] Show employers posting an internship or job, reviewing internal applications and replying.
- [ ] Add captions, a poster image and user-controlled playback before publishing the video on the landing page.

The video is a future content task and is not required for the opportunity finder to work.
