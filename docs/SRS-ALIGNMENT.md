# Priyadarshini: SRS alignment check

Date: 2026-11-07. Checked against `SRS.md` v0.7 (UI, BRD, AWD, CAT, DIS, CHK, BUY, ORD, REV, CR, DSH, PRF, PLT).
Checked: `design/design-system.html` (with continuity sections), `design/app-blueprint.html`, `design/app-journey.html` (prototype), `BLOCKS.md`, `design/block-tree.html`.

Legend: **OK** matches. **Fixed** was off, fixed in this pass. **Plan** now in the block plan (BLOCKS.md v0.2), not drawn in the prototype. **Decision** needs you. **Build** can only be checked when built.

## 1. Fixed in this pass

| SRS | Was | Now |
|---|---|---|
| BUY-09 | Cards and filters had no buying-format label or filter | Prototype cards show a format label; block plan has `pd/format-label` and `pd/filter-format` |
| REV-08 | No "New" rule | Prototype shows "New" under 3 reviews (work card and work page); `pd/rating-link`, `product-rating` note it |
| REV-10 | Reputation thread opened on "All" | Brand page opens on artisan reviews, second tab is product reviews; work page plan lists product reviews first, then a short artisan-reviews block |
| REV-15 | No review sort | Prototype sort: Most recent, Highest, Lowest, With photos; plan adds `pd/review-sort` |
| BUY-02 to BUY-04, BUY-11 | Buy box was only two buttons | Prototype shows GST note, stock and dispatch, delivery estimate, "Commission now from ₹X, lead time", "How commissioning works", returns by format; plan has `pd/commission-from`, `-presets`, `-how`, `pd/tax-note` |
| CHK-04 | Bag said shipping "At checkout" | Bag says "Shown before you pay" |
| DIS-05, AWD-10 | Directory filter was "region" | Plan: district filter and award-winners filter |
| DSH-01 | Studio order was KPI first | Plan: orders, commission requests, unreplied reviews, earnings, in that order; adds sale breakdown (DSH-03), payouts (DSH-04), away switch (DSH-07) |
| REV-05, REV-13, REV-11, REV-18, REV-20 | No blocks | Plan: `pd/review-flow` (two steps), "edited" mark, 7-day reply edit, helpful button, "custom piece" label |
| BUY-05 to BUY-08, CR-02 to CR-07 | Commission was one inline form | Plan: tree 10, commission request, thread, milestones, My commissions; never through the cart |
| BUY-10, CHK-02, CHK-06, DSH-07 | No blocks | Plan: waiting list, 15-minute reservation timer, gift options, away note |
| BRD-03, 04, 07, 09, 10 / AWD-05, 06, 09 / DIS-08 / CAT-07 | Missing from trees | Plan: story, lineage, sold filter and sold marking, share link, award view toggle (by year / by craft), curated slot, follow feed, GI badge |

## 2. Needs your decision

1. **UI-05, shell colour.** The SRS says the shell stays paper and ink and only the brand page takes the accent. The continuity layer lets the shell's lower rule take the maker's accent. Recommended: amend UI-05 to allow it (one line). Otherwise the rule goes back to ink.
2. **Commission wording.** BRD-06 says "Request a custom piece", AWD-07 says "Commission a work", BUY says "Commission now". Everything built uses **Commission now** (and "See available work"). Recommended: edit BRD-06 and AWD-07.
3. **BUY-09 labels.** Only "Ready to ship" and "Commission" exist. A made-to-order work that can be bought has no label. Prototype uses "Made to order · Commission". Recommended: add "Made to order" to BUY-09.
4. **One plugin or two.** Fits the SRS (PLT-07 says "custom plugins", PLT-08 only fixes where marketplace logic lives). Plan keeps `pd-marketplace` + `pd-blocks`.
5. **BRD-08 accent set.** SRS allows six accents (gulabi, kusum, neel, patta, sindoor, haldi). The prototype shows two. Sindoor and haldi accents need rules: UI-03 reserves sindoor for actions and errors, so the accent must be a fill only. Needs a design pass on the six, with contrast checks for ink on haldi.

## 3. Still not drawn in the prototype (in the plan only)

These are in BLOCKS.md but the prototype does not show them. None conflict with the SRS.

- REV-05 two-step review form, REV-13 "edited", REV-11 edit window, REV-12 voice reply.
- BUY-07 presets, BUY-10 waiting list, CR milestones and My commissions.
- CHK-02 reservation timer, CHK-06 gift note, CHK-03 three-step step labels match but PIN lookup is not simulated.
- DSH-03, 04, 07 earnings breakdown, payouts, away switch. UI-13 "speak instead" on artisan text fields (the app is a separate design).
- DIS-06 craft pages, DIS-07 collections, DIS-08 follow feed, AWD-06 award filters and toggle.
- BRD-01 URL: prototype uses hash routes; the build uses `/a/{handle}` and `/awards/{slug}`.
- UI-11: the designs use 760 and 1100 breakpoints, 400 only as a width choice, and have not been tested at 320 px.

## 4. Checked and matching

| SRS | Result |
|---|---|
| UI-01 tokens, UI-03 colour roles, UI-06 small-text rule | OK. Stylesheet checked: sindoor only on primary buttons and error text, haldi only on stars and rating bars, gulabi/kusum never on text |
| UI-04 one primary per screen | OK in every prototype view |
| UI-07 fonts | OK. Yatra One headings and names, Mukta body, Kalam artisan voice only |
| UI-08, UI-09 splash | OK. 1.7 s, skippable, once per session, static under reduced motion, never blocks content |
| UI-10 artisan-first | OK |
| UI-12 empty, loading, error | OK in prototype; every collection block lists a skeleton and empty state |
| BRD-05, BRD-12 | OK. Maker name and link on every card, plate and order line |
| AWD-04, 05, 07, 08 | OK, with AWD-07 wording noted above |
| CHK-01, 07 grouping and sub-orders | OK. Bag and confirmation grouped by maker |
| REV-03, 04, 06, 07, 11, 14 | OK. Separate product and artisan reviews, one reply, no delete or hide control for artisans |
| PLT-06, 10, 11, 12, 13, 14 | OK. Block theme, `block.json` v3, dynamic render, Interactivity API, Router for filters, link-first |
| PLT-08 | OK, see decision 4 |
| PRF-03, PRF-04 | Build. Splash must be inline SVG under 15 KB, initial JS 170 KB; the prototype splash is inline SVG but is not size-tested |
| UI-02, PRF-01, 02 | Build. Texture under 40 KB, LCP, INP, CLS are measured on the built theme |

## 5. Not yet in the SRS (proposed)

The continuity layer (shell, Artisan Thread, Work Thread, shared state, feedback, focus) is not in SRS v0.7. Proposed additions are in the design system sections `continuity` to `journeys`. They need to be written as numbered requirements once you approve them.

## 6. Addendum, 2026-11-07

This check was made against SRS v0.7. SRS v0.8 (corrective architecture pass) changes the data model (hybrid artisan, allocations instead of child orders), adds CNT-01 to CNT-38 and SEC-08 to SEC-14. Items above are unaffected except: "sub-order" now reads "order allocation" (CHK-07, REV-04, DB-01, SEC-06), and the block counts in section 1 are superseded by `BLOCKS.md` v0.3 (26 registered blocks). The Commission wording, BUY-09 label and UI-05 decisions are still open.


## Addendum 2: owner decisions (2026-11-07, SRS v0.9)

| # | Decision | Where recorded |
|---|---|---|
| 1 | Two plugins (`pd-marketplace`, `pd-blocks`) plus `pd-theme` | BLOCKS.md section 14 |
| 2 | Label "Made on order" | BUY-09; prototype, blueprint, design system |
| 3 | Shell stays paper and ink; no maker-accent rule | UI-05; prototype and design system changed |
| 4 | One tax invoice per order, not per allocation | CHK-08; `invoice_no` removed from OrderAllocation; Appendix B 16 |
| 5 | Devanagari field for every public text from day one | DB-15 |
| 6 | Pro artisans edit their story in a restricted block editor | BRD-13; Appendix B 19 (define "pro") |
| 7 | Six-accent set final | BRD-08 |

Open: Commission wording (BRD-06, AWD-07), "pro" definition, review index threshold, tax adviser check on the single invoice.
