# Priyadarshini: project brief

_Last updated 2026-11-07_

## What it is
A marketplace where artisans are treated as brands. The focus is on the artisan: each has their own brand page and their own reviews (not only product reviews). Artisans can reply to reviews in their own voice.

## Brand and feel
- Inspired by Madhubani (Mithila) painting and the vibrant colors of Bihar and nearby regions.
- Background: handmade paper texture.
- Splash: a Madhubani art element appears while loading, then turns into the logo as it moves to the header. The fish is a placeholder motif; the final art should come from a Madhubani artist or illustrator. Other options considered: peacock, lotus, tree of life.
- Fonts must support Devanagari (Hindi, Maithili) as well as Latin.

## Colors (palette A, "Pigment", chosen)
| Token | Hex | Role |
|---|---|---|
| `--kagaz` | `#F4EBD9` | Page paper |
| `--kagaz-gehra` | `#E9DCC3` | Panels, dividers |
| `--card` | `#FFFAF0` | Card surface |
| `--kajal` | `#2A1E17` | Text and outlines (13.7:1 on paper) |
| `--sindoor` | `#C8321F` | Primary action, errors (white text 5.3:1) |
| `--neel` | `#25306B` | Links, navigation, verified badge (10.4:1) |
| `--haldi` | `#E9A30C` | Stars and reviews only, fill only |
| `--patta` | `#3E7B3A` | Success (4.3:1) |
| `--gulabi` | `#D9467A` | Artisan accent, fill only |
| `--kusum` | `#E26A1B` | Artisan accent, fill only |

Rules:
- One color, one job. Sindoor means action, neel means trust and information, haldi means ratings, patta means success.
- Color belongs to the artisan: the app shell stays paper and ink, and each brand page takes one accent for its top band and avatar.
- Haldi, gulabi and kusum are under 4.5:1 on paper, so use them as fills and shapes, never small text.
- One primary (sindoor) button per screen.

## Fonts (pairing 1, chosen)
- Headings and brand names: **Yatra One**
- Body and interface: **Mukta** (400, 600, 700)
- Artisan quotes, signatures and replies only: **Kalam**
- Type scale: display 52, h1 36, h2 28, h3 20, body 17, small 14, hand 20.

## Shape and spacing
- Spacing: 4, 8, 12, 16, 24, 32, 48, 64.
- Radius 6px (chips 99px, avatars 50%). Outline 1.5px on surfaces, 2px on controls.
- Shadow: hard 3px offset in kajal, no blur.
- Tap targets at least 44px, body text at least 16px, focus ring 3px neel.
- Breakpoints: 400, 760, 1100.

## Tech stack
WordPress + WooCommerce extended by our own plugin `pd-marketplace` (no third-party multi-vendor plugin), custom block theme. Artisans use the Priyadarshini Artisan app, forked from the official WooCommerce mobile app (Android first) and extended for easy management. Front end from Gutenberg blocks (core, WooCommerce, custom `pd/*` blocks) with the WordPress Interactivity API. See SRS.md 3.6.3 and Appendix D.

## Plan and status
1. Colors and fonts: done, chosen (A and 1).
2. Design system: v2 approved and refined (portrait-dominant artisan identity, imagery placeholders with aspect ratios, award badge, Buy now / Commission now pair).
3. SRS: `SRS.md` (IEEE 29148, Markdown), v0.8 (corrective architecture pass); unconfirmed assumptions in its Appendix B.
4. App blueprint: 11 responsive screens done (`design/app-blueprint.html`): Home, Directory, Artisan page, Work, Search, Bag, Checkout, Confirmation, Dashboard, Reply, Awards.
5. Continuity system: done (this pass). Eleven new sections at the end of `design/design-system.html` (principles, shell, Artisan Thread, Work Thread, choreography, shared state model, loading and optimistic feedback, focus, reduced motion, Interactivity API mapping, five journeys) and a working prototype `design/app-journey.html` that runs five journeys with a simulated network. Superseded in part by item 8.
6. Block plugin plan: `BLOCKS.md` v0.3 (26 registered blocks, 36 internal PHP components, 9 stores with a shared-state contract, shared-element contract, M-1 spikes, milestones) and an interactive `design/block-tree.html`. Awaiting owner review.
7. SRS check: `SRS-ALIGNMENT.md` lists what matched, what was fixed and what needs an owner decision.
8. Corrective architecture pass (2026-11-07): hybrid artisan model (`pd_artisan` post + `pd_artisans` table), normalised review repository with `pd_review_index`, one WooCommerce order with artisan allocations (no child orders), continuity and shared-state requirements CNT-01 to CNT-38, security SEC-08 to SEC-14, design system sections for the shared-element contract, motion vocabulary, shell anatomy, routed regions, feedback ladder, reduced motion and shared state, blueprint transition identities and 4:5 work cards, eight prototype journeys with live shared-state check.
9. Next: owner reviews; then milestone M-1 (14 feasibility spikes) before any build. Architecture is not approved until the spikes pass.

## Files
- `design/colors-and-fonts.html`, `design/design-system.html`, `design/app-blueprint.html`, `design/app-journey.html`, `design/block-tree.html` and `BLOCKS.md` in this folder.
- Published: colors and fonts https://claude.ai/artifact/MP7pLLKA1ccFjEeFfKkCGU, design system https://claude.ai/artifact/1sneGSmrDyZwJTjbGgtves, app blueprint https://claude.ai/artifact/9YKyeLjgcrpQDep2vE67ra, continuity prototype https://claude.ai/artifact/RWY8cDcNWfUEqfpLbfKbqq, block tree https://claude.ai/artifact/JprX3ggMj7hBxm2ZHX61P6 (all republished 2026-11-07)

## Continuity decisions (this pass)
- Shell is a fixed 108px (64px bar + 44px context row). Nothing in it changes size. It stays paper and ink on every route (UI-05 confirmed by the owner 2026-11-07; the maker-pigment rule was declined).
- Artisan Thread: Seal, pigment, bilingual name and fixed attribution words ("Made by", "by", "will make and ship this", "replied") follow the maker across every screen.
- Work Thread: 4:5 plate, same catalogue line "Work No. 014 · by Meera Devi" everywhere; the blueprint's small card is now 4:5 as well.
- The address holds anything shareable (filters, step, review target); the store holds the rest. Back restores scroll, filters and the focused control.
- Splash: at most 1.8s, once per session, skippable, replaced by the static logo under reduced motion (matches SRS UI-08, UI-09).
- Feedback ladder: press 0-100ms, optimistic 100-300ms, skeleton shaped like the content from 300ms, failures keep input and offer Try again.

## Architecture decisions (2026-11-07)
- Artisan = `pd_artisan` post (public identity, `/a/{slug}`) + `pd_artisans` table (operations). No field in both. Public id is the post id.
- Reviews: product reviews stay WooCommerce comments, artisan reviews in `pd_artisan_reviews`; one repository returns a common DTO; `pd_review_index` is the derived read model; `pd/reputation-thread` is dynamic. No comment blocks merge the sources.
- Orders: one WooCommerce order; artisan fulfilment in `pd_order_allocations`, `pd_order_allocation_items`, `pd_shipments`. WooCommerce owns payment, tax, stock, refunds, totals.
- Blocks: 26 registered, 36 internal PHP components, 9 variations, 7 patterns, 16 templates. Editors never see card internals.
- State: URL, UI, session, server. One owner and one writer per shared key; mirrors derived; seeded once in PHP; each key reseed, persist or reset on navigation.
- Shared elements: `data-pd-entity`, `data-pd-entity-id`, `data-pd-transition`, `data-pd-focus-id` from immutable ids; four motion kinds; reduced motion keeps meaning and focus.
- Owner decisions 2026-11-07: two plugins plus theme; label "Made on order" (BUY-09); shell stays ink (UI-05); one tax invoice per order (CHK-08); Devanagari field for every public text from day one (DB-15); pro artisans get a restricted block editor for their story (BRD-13); six-accent set final.
- Still open: Commission wording in BRD-06 and AWD-07; definition of "pro artisan"; review index threshold; tax adviser check that one invoice naming several artisans meets GST and TCS rules.

## Platform decisions (from the SRS thread)
- WordPress with WooCommerce, extended by a custom Priyadarshini plugin (no off-the-shelf multi-vendor plugin).
- Block theme with custom Gutenberg blocks and the Interactivity API.
- Artisan mobile app forked from the official WooCommerce app; artisans manage mainly there. The web dashboard is its twin.
- Products and artisans both have reviews. Awards are showcased: an Awards page lists winners, linking to the artisan page and a "Commission now" action; award badges appear on artisan identity. Two purchase paths on works and artisan pages: "Buy now" (primary) and "Commission now" (secondary).

- Domain: priyadarshini-studio.in (owner registering, 2026-11-07; registration not yet confirmed). Hosting, SSL and sender-email setup are open.
