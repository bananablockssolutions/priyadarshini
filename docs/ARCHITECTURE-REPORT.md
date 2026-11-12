# Priyadarshini corrective architecture pass: report (2026-11-07)

Status: proposed, not approved. The architecture is a design until the 14 M-1 spikes pass on a real WordPress and WooCommerce install. Nothing here has been measured on WordPress yet; the prototype only proves the interaction and state rules.

## 1. Files changed

| File | Change |
|---|---|
| SRS.md | v0.7 to v0.8. "Sub-order" replaced by order allocation. New DB-08..14, CNT-01..38, SEC-08..14, 3.5.4 and 3.5.5, verification rows, open issues 15-18. |
| BLOCKS.md | v0.2 to v0.3, rewritten (about 985 lines). |
| design/block-tree.html | Regenerated from the same data as BLOCKS.md. |
| design/design-system.html | New sections: contract, motion vocabulary, shell anatomy, routed regions, feedback ladder, reduced-motion equivalence, shared state. Interactivity and journeys sections replaced. |
| design/app-blueprint.html | Transition identities (`data-pd-*`) on seals and plates, identities table, new WordPress mapping, "vendor" and "split orders" removed. |
| design/app-journey.html | Shared-element engine, 8 journeys, History and Back panel, Shared state check panel. |
| PROJECT.md, SRS-ALIGNMENT.md | Updated with the decisions and a v0.8 addendum. |

## 2. Decisions

- **Artisan model (hybrid):** a `pd_artisan` post type holds public identity (story, portrait, village, craft and district taxonomies, `/a/{slug}`, revisions, SEO, published only while verified). A `pd_artisans` table holds operations (verification, taking orders, lead time, away until, rating cache, counts). The public id is the post id, for example `artisan-184`.
- **Review model:** product reviews stay WooCommerce comments. Artisan reviews and replies live in their own tables. A `PD\Reviews\Repository` returns one review DTO; a rebuildable `pd_review_index` is the read model. Artisan and product reviews are two separate scopes, never one mixed list. Numbered pages, keyset cursor above 10,000 reviews, deep link `?review=`, one reply per review, editable for 7 days.
- **Order model:** one canonical WooCommerce order and no child orders. `pd_order_allocations`, `pd_order_allocation_items` and `pd_shipments` split it by artisan. Allocations reconcile to the paisa; reviews and payouts attach to the allocation. Reason: refunds, tax, stock, emails, analytics, payment reconciliation and HPOS all keep working.

## 3. Counts

| | Old | New |
|---|---|---|
| Registered custom blocks | 108 in v0.1, 148 in v0.2 trees | **26** |
| Internal PHP components | not separate | 36 |
| Block variations / styles / patterns / templates | | 9 / 4 / 7 / 16 |
| Interactivity stores | | 9 |

Registered blocks: app-shell, routed-main, artisan-card, catalogue-filters, work-card, artisan-masthead, reputation-summary, artisan-facts, award-badges, reputation-thread, review-form, award-index, award-winners, work-gallery, buy-box, cart-groups, checkout-flow, order-groups, commission-request, my-commissions, studio, featured-artisan, craft-ledger, follow-feed, search-results, follow-button.

## 4. Stores and ownership

Nine stores in BLOCKS.md section 8. Four state categories: URL, UI, session, server. The Interactivity API coordinates server state and is never its source of truth. `pd/bag` and `pd/checkout` are thin adapters over the WooCommerce cart and checkout. Section 9.1 lists 22 shared keys with owner, source of truth, writers, readers, lifetime and what happens on Router navigation. Rules: one owner and writer per key; mirrors derived, never copied; seeded once in PHP; private keys start with `_`; server truth wins.

## 5. Router regions

Persistent: shell, progress line, live regions, footer. Router regions: `pd-main`, and nested `pd-catalogue`, `pd-reviews`, `pd-award-view`, `pd-studio`.

## 6. Shared-element transitions (prototype journeys)

1. Home: artisan seal grows into the artisan masthead.
2. Artisan: work plate grows into the gallery.
3. Work: plate shrinks and docks into the bag group.
4. Bag to checkout to confirmation: thumbnails regroup into the order group.
5. Award winner: seal moves into the masthead, then into Commission.
6. Review reference: reveal and focus on the exact review (no snapshot).
7. Directory filters, artisan page, Back: filters, scroll and focus restored.
8. Studio dashboard: reply posted, badge and counts update everywhere.

Reduced motion: no movement, double-rule outline on the destination, same URL, focus and announcements. Browsers without view transitions: normal navigation.

## 7. M-1 spike checklist (about 2 weeks)

1. Artisan CPT plus operational-table querying
2. Product Collection customisation
3. Artisan data in Store API
4. Cart grouped by artisan
5. Checkout extension
6. Order-confirmation customisation
7. Normalised review thread
8. Interactivity Router navigation
9. Back and Forward restoration
10. Shared-element transitions
11. Server-rendered state hydration
12. Reduced-motion fallback
13. No sensitive data in public state
14. Cross-block shared state (added by me, beyond the brief)

Each has a pass condition, a fallback and the feature at risk in BLOCKS.md section 12.

## 8. Verification done

Prototype in Playwright: all 8 journeys in normal and reduced motion, no page errors; 160 shared-state checks, 0 mismatches; a deliberate break was caught; one primary button per view; no horizontal scroll at 380px in the blueprint and block tree.

## 9. Owner decisions

Decided 2026-11-07 (SRS v0.9): two plugins plus theme; label "Made on order"; shell stays paper and ink (UI-05 unchanged); one tax invoice per order; Devanagari field for every public text from day one; restricted block editor for pro artisans; six-accent set final.

Still open: Commission wording in BRD-06 and AWD-07; what makes an artisan "pro"; `pd_review_index` and the 10,000-review threshold; tax adviser check that one invoice naming several artisans meets GST and TCS rules. Unconfirmed from before: artisan share of at least 50%, 7-day returns, artisan as seller of record, GPL duties of the app fork (lawyer).

## 10. Deferred until WooCommerce extensibility is proven

Optimistic cart quantity, three-step checkout presentation with reservation timer, grid sort by rating (if meta copy is needed), grow and dock motion (falls back to crossfade), milestones and commission thread screens (not yet drawn in the prototype).

## 11. Final quality test (14 questions)

All answered yes in the design, with one honest caveat: every answer about WooCommerce behaviour is a design claim until the spikes run. No answer depends on a measured result.
