# To do

Small fixes and chores that are not on the roadmap (`FUTURE_PLANS.md`).

## Fix the failing location tests

Three tests in `tests/Feature/OpportunityCleanupTest.php` fail on machines without PHP's `intl` extension:

- `test_locations_have_no_repeats…` expects `Tunis, Tunisia` and gets `Tunis, TN`.
- `test_board_pages_get_clean_places…` expects `Ariana, Tunisia` and gets `Ariana, TN`.
- The `opportunities:clean` test expects `location: Tunis, Tunis, TN → Tunis, Tunisia` in its output.

`OpportunityNormalizer` (`app/Importing/OpportunityNormalizer.php`, around line 215) turns country codes into names with `Locale::getDisplayRegion`. Without `intl` it keeps the code, so imported listings show "TN" instead of "Tunisia".

- [ ] Install `intl` locally (`sudo apt install php8.5-intl`) and make sure the server has it too (`DEPLOY.md`, `VPS_DEPLOYMENT.md`).
- [ ] Or keep a small fallback list of country names for when `intl` is missing, so the tests and the site work either way.
- [ ] Run `php artisan test` and check that all three pass.
