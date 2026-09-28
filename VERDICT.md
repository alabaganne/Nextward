# Verdict: the software is dead — the recruitment business is not

**Rank 9 of 11 as software.** But there is a version of this that works, and it is not a SaaS.

## State of the code
35% done, ~14 weeks. Laravel 8 + Inertia + Vue 3. Originally a university project
(`alabaganne/internships-for-students`, DB `issatso_internships`), rebranded three times: Stagi in
the README, Qodwa in the design doc, Internly in the UI. Neither stagi.tn nor internly.tn resolves.
**Zero payment code** — no plan table among the 15 migrations, no PSP, no pricing page, no quota
enforcement. What exists is 43KB of strategy prose written in May 2025.

## Why the SaaS is dead — checked competitor by competitor
- **stage-tunisie.tn** — DNS no longer resolves. Dead.
- **stagebook.tn** — "We're working on it! This site is currently under construction."
- **stage.tn** — calls itself *"la plateforme n°1 des offres de stage en Tunisie"* and displays
  **3 active offers.**
- **stagi-edu.com** — live, and already using the name in your README.
- **hi-interns.com now redirects to hi-talents.com** — the closest real competitor (Tunisia +
  Morocco + France, exactly this product) renamed away from interns and **inverted the model**:
  companies post and browse free, **students** pay €14.99/mo. They tried charging companies and
  concluded companies will not pay.

The billing shape is also wrong. Companies hire in **bursts** — PFE season — then cancel. Monthly
subscription does not match that behaviour, which is why Keejob sells "packs" by phone rather than
publishing a pricing page.

And the ceiling: $10,000/mo = **303 companies at 99 TND** = 21% of every online-paying business in
Tunisia (1,456 active merchant sites nationwide, BCT H1 2026).

## The version that is not dead
**A placement fee per hire.** 500–1,000 TND when a student is actually hired. That is the billing
shape the behaviour wants: one-time, at the moment value is delivered, when the company is happiest.
30 placements a month at 800 TND is 24,000 TND.

Two channels worth more than the whole platform:
1. **Direct phone and in-person sales to company HR** — the only channel that has ever monetized
   this category in Tunisia.
2. **University 4C career centres** (4c.tn) — every public university runs one and they own the
   *convention de stage* workflow. That is the choke point, and it is institutional.

The existing code becomes useful later as the tool that runs a business you already have — not as
the business itself. Be clear-eyed: this is a recruitment agency, phone calls and all.
