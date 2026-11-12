# Priyadarshini: block plugin plan

Status: plan **v0.3**, 2026-11-07, corrective architecture pass. Replaces v0.2. Companion to `SRS.md` v0.9 (PLT-10 to PLT-15, CNT, DB-08 to DB-15, SEC-08 to SEC-14), `design/design-system.html`, `design/app-blueprint.html`, `design/app-journey.html`, `design/block-tree.html`, and `SRS-ALIGNMENT.md`.

## 1. The idea in one page

- Priyadarshini is **one continuous application** built from server-rendered WordPress pages. The shell stays mounted, the routed main region is swapped by the Interactivity Router, and a shared element (a Seal, a plate, a review) can grow, shrink or move into its place on the next page. Without JavaScript every page and link still works as an ordinary page.
- **26 registered custom blocks** (down from 148 in the v0.2 trees and about 89 to 129 after merging). Everything smaller is an **internal PHP view component** (36 of them). Editors never see anatomy inside a card, a buy box, a checkout or a review entry.
- **Artisans are a hybrid**: a `pd_artisan` post type for public identity (name, story, media, permalink, revisions, translations) and a `pd_artisans` table for operations (verification, accent, motif, availability, rating cache). No field lives in both.
- **Reviews keep their own storage** (WooCommerce comments for products, `pd_artisan_reviews` for artisans) and are read through one **normalised repository** with a derived `pd_review_index`. `pd/reputation-thread` is a dynamic block over that repository. No comment blocks are used to merge sources.
- **One WooCommerce order per purchase.** Artisan fulfilment lives in `pd_order_allocations`, `pd_order_allocation_items` and `pd_shipments`. WooCommerce stays authoritative for payment, tax, coupons, stock, refunds and totals.
- **State has four homes**: URL, UI, session, server. The Interactivity API coordinates server state but is never its source of truth. WooCommerce owns the cart and checkout.
- **Nothing here is approved until milestone M−1 passes.** Fourteen spikes prove the WooCommerce and Router assumptions first (section 12).

### What changed from v0.2

| Topic | v0.2 | v0.3 |
|---|---|---|
| Registered custom blocks | 148 in trees, about 129 after merging | 26 |
| Block anatomy | Every leaf was a block (seal, plate, stars, review head ...) | Internal PHP components, not blocks |
| Artisan storage | Assumed a post type while the SRS said table only | Hybrid: `pd_artisan` post + `pd_artisans` table |
| Reviews | `core/comment-template` assumed to combine both sources | Normalised repository + `pd_review_index`, dynamic `pd/reputation-thread` |
| Orders | One child WooCommerce order per artisan | One order + allocations, items, shipments |
| Stores | pd/shell, nav, artisan, work, directory, reviews, bag, checkout, studio, with business state mixed in | 9 stores with a stated state category each; bag and checkout are adapters only |
| Transitions | `data-sh` keys from titles and slugs | Contract with immutable ids: `data-pd-entity`, `-entity-id`, `-transition`, `-focus-id` |
| Milestones | M0 to M6 | M−1 spikes added before M0 |

## 2. Five kinds of building block

| Kind | What it is | Who sees it | Count |
|---|---|---|---|
| 1 Registered Gutenberg block | A `block.json` block, dynamic, with `render.php` | Editors in the inserter or locked into templates | 26 |
| 2 Block variation | A named preset of a core or WooCommerce block (query, product collection, navigation, pullquote, group, button, image) | Editors, as a named block | 9 |
| 3 Pattern, template, template part | Block markup in the theme | Editors (patterns), theme (templates) | 7 patterns, 16 templates, 3 parts |
| 4 Internal PHP view component | A PHP function that prints one piece of UI from data, shared by blocks. Not registered | Nobody in the editor | 36 |
| 5 JavaScript store or utility | Interactivity API store or shared module | Nobody in the editor | 9 stores, 8 utilities |

**Rule for registering a block.** Only when editors genuinely need to insert it on its own, rearrange it, configure meaningful attributes, reuse it outside one composite, or control its content as a distinct editorial unit. Otherwise it is an internal component.

## 3. Data assumptions

### 3.1 Artisans: hybrid model

| Part | Stored in | Holds | Public? |
|---|---|---|---|
| Editorial identity | `pd_artisan` post (rewrite `/a/{slug}`) | Latin name (title), Devanagari name (meta), story (content), portrait (featured image), workshop media, craft narrative (excerpt), village, lineage, taxonomies (craft, district, language), SEO fields, publication state, revisions, translations | Yes |
| Operations | `pd_artisans` table (`post_id`, `user_id` unique) | verification state and date, accent token, motif key, taking-orders, typical lead time, away until, rating cache, review count, reply rate, sales count, follower count, timestamps | Public columns: accent, motif, taking orders, lead time, away until, rating, review count, reply rate, sales count, follower count. Private: user id, verified at, notes, payout and document references |

- The **public id of an artisan is the post id** (`artisan-184`). The ops row id is internal and never appears in markup or URLs.
- A `pd_artisan` post can be `publish` only while verification is verified (DB-13).
- `core/query` over `pd_artisan` works as is. Operational filters and sorts (taking orders, rating, replies) join `pd_artisans` in `posts_clauses` (spike 1).
- Each product carries `_pd_artisan_post_id`. Product Collection cards read the artisan from it.

### 3.2 REST surface for the blocks

| Route | Use |
|---|---|
| `wp/v2/pd_artisan` | Editorial identity, public |
| `pd/v1/artisans`, `/artisans/{postId}` | Editorial plus public operational columns |
| `pd/v1/artisans/{postId}/reviews` | Normalised review DTOs, one page |
| `pd/v1/reviews`, `/{typed-id}/reply`, `/{typed-id}/report` | Create review, reply once, report |
| `pd/v1/studio/allocations` and actions | Own allocation queue only |
| `wc/store/v1/cart` with `extensions.pd` | Cart with public artisan grouping fields |
| `admin-post.php` handlers | No-JavaScript fallbacks for every write |

Full table: SRS 3.5.5.

## 4. Registered blocks

**26 blocks.** Each one is dynamic (`render.php`, `apiVersion` 3). Columns: where the data comes from, which context it provides or reads, what children editors may place, which store it uses, how it behaves without JavaScript.

| Block | Group | Purpose | Data source | Provides | Reads | Children and lock | Store | Without JavaScript |
|---|---|---|---|---|---|---|---|---|
| `pd/app-shell` | Shell | Header template part: splash, brandmark, main navigation slot, search, bag link, context row, progress line, live regions. | Route, current maker (public), WooCommerce cart count (server-seeded) | none (shell stays paper and ink, UI-05) | pd/route | No editor children. One core/navigation variation allowed inside. templateLock all | pd/shell, pd/navigation | Plain links, GET search form, no splash |
| `pd/routed-main` | Shell | Wrapper for the routed main region. Carries the router region, the page-heading focus target and the page announcement text. | Template | pd/route | none | Any page blocks. Inserted by templates only, hidden from the inserter | pd/navigation | Ordinary main element |
| `pd/artisan-card` | Collection | One artisan card in a query grid. | pd_artisan post (core Query context postId) + pd_artisans public columns | none | postId, postType, queryId | Leaf. No InnerBlocks | none | Plain link |
| `pd/catalogue-filters` | Collection | Filters, sort and result count for any catalogue (artisans, works, award winners). One block, configured by attributes. | URL query args, taxonomy terms, price range | none | queryId | Leaf. Attributes: scope, fields[], sortOptions[] | pd/catalogue | GET form with Apply button, works as a link set |
| `pd/work-card` | Collection | One work card in a Product Collection grid. | WooCommerce product (postId) + _pd_artisan_post_id + public artisan columns | none | postId, postType, queryId | Leaf. Attributes: showMaker, showFormat, density | none | Plain link |
| `pd/artisan-masthead` | Artisan page | The painted-frame head of the artisan page. | pd_artisan post + public columns | pd/artisanPostId, pd/accent | postId | Locked template: [pd/follow-button, core/button variation pd-commission-now]. templateLock contentOnly | pd/artisan | Follow is a form post, Commission now is a link |
| `pd/reputation-summary` | Artisan page | Rating, review count, works sold, reply rate. Shows "New" under 3 reviews. | pd_artisans public columns | none | pd/artisanPostId | Leaf | none | Static |
| `pd/artisan-facts` | Artisan page | Facts list with chosen fields: materials, technique, typical time, languages, village, lineage, ships from. | pd_artisan meta and taxonomies | none | pd/artisanPostId | Leaf. Attribute: fields[] | none | Static |
| `pd/award-badges` | Reusable | Awards of an artisan as badges, or only the top award. | pd_artisan_awards (verified only) | none | pd/artisanPostId | Leaf. Attribute: mode all or top | none | Links to award pages |
| `pd/reputation-thread` | Reviews | Server-rendered review thread over the normalised review repository. Scope tabs, sort, filters, pagination, deep link, replies. | Review repository (see section 7) | pd/reviewId (per entry) | pd/artisanPostId, pd/workId | Leaf. Attributes: scope, perPage | pd/reviews | Tabs, sort and filters are links and a GET form; paging is links; report and reply are form posts |
| `pd/review-form` | Reviews | Two-step review: rate the piece, rate the artisan. Either part can be sent alone. | Delivered order allocation items (own orders only) | none | none | Leaf | pd/reviews | Standard form post |
| `pd/award-index` | Awards | Awards hall ledger with winner counts. | pd_award terms, verified pd_artisan_awards | none | none | Leaf | none | Plain links |
| `pd/award-winners` | Awards | Winners of one award, grouped by year or by craft, with craft and district filters. | pd_award term, pd_artisan_awards, pd_artisan posts | pd/awardId | term id | Leaf. Attribute: defaultView | pd/catalogue | Toggle and filters are links and a GET form |
| `pd/work-gallery` | Work page | Main plate and thumbnails. | WooCommerce product gallery | pd/workId | postId | Leaf | pd/work | Thumbnails are links to ?img=n |
| `pd/buy-box` | Work page | Price, stock, delivery, Buy now, Commission now, disclosures. | WooCommerce product (price, stock), public artisan columns, commission settings | none | pd/workId, pd/artisanPostId | Leaf. Attributes: showPresets | pd/work, pd/bag | Buy now is a form to checkout, Commission now is a link, pincode is a GET form |
| `pd/cart-groups` | Bag | Cart lines grouped by artisan. Inner block of the WooCommerce Cart block. | WooCommerce Store API cart + extensions.pd (public grouping fields) | none | cart (Woo) | Leaf | pd/bag | WooCommerce Cart block form posts |
| `pd/checkout-flow` | Checkout | Step list, review step, reservation timer, gift options, calm mode. Inner blocks of the WooCommerce Checkout block. | WooCommerce Store API checkout, reservation (server) | none | checkout (Woo) | Leaf. WooCommerce fields stay WooCommerce blocks | pd/checkout | WooCommerce checkout form; steps are anchors |
| `pd/order-groups` | Checkout | Order confirmation and order view grouped by artisan with allocation status and timeline. | WooCommerce order + order allocations (customer-visible fields) | none | order (Woo) | Leaf | none | Server rendered |
| `pd/commission-request` | Commission | Request form (pre-filled from a work or blank), terms, thread, quote and milestones. | pd_commission, thread messages (participants only) | none | pd/artisanPostId, pd/workId (optional) | Leaf | pd/artisan | Form posts |
| `pd/my-commissions` | Commission | Buyer list of commissions with stage and progress photos. | pd_commission (own rows only) | none | none | Leaf | none | Server rendered |
| `pd/studio` | Studio | Artisan workspace: overview, orders (allocations), commissions, reviews inbox and reply, earnings, profile and identity. Role gated. | Allocations, reviews, commissions, payouts (own rows only) | none | none | Leaf. Attribute: panel | pd/studio | Each panel is its own URL and works with forms |
| `pd/featured-artisan` | Home | Editor picks one artisan to feature. | pd_artisan post chosen in the sidebar | pd/artisanPostId | none | Leaf. Attribute: artisanPostId | none | Plain links |
| `pd/craft-ledger` | Home | Crafts with maker counts, linking to the directory. | product_cat terms, counts | none | none | Leaf. Attribute: limit | none | Plain links |
| `pd/follow-feed` | Home | New works from artisans the buyer follows. Hidden for signed-out visitors. | pd_follows (own rows), products | none | none | Leaf. Attribute: limit | none | Server rendered |
| `pd/search-results` | Search | Search results grouped as artisans and works, plus the suggestions listbox. | Search index (artisans, works) | none | none | Leaf | pd/shell (suggestions) | GET search form and a results page |
| `pd/follow-button` | Reusable | Follow or unfollow an artisan. | pd_follows, signed-in state | none | pd/artisanPostId | Leaf | pd/artisan | Form post, redirect, get |

### Internal components used by each block

| Block | Internal components |
|---|---|
| `pd/app-shell` | Splash, Brandmark, ContextRow, SearchForm, BagLink, ProgressLine, LiveRegions |
| `pd/routed-main` | none |
| `pd/artisan-card` | Plate (portrait), SealLink, CraftLine, VerifiedLine, RatingLine, OrderStatus, ShipsIn, Skeleton |
| `pd/catalogue-filters` | FilterSelect, FilterChips, PriceRange, SortSelect, ResultCount, ResetLink |
| `pd/work-card` | Plate (4:5), CatalogueLine, WorkTitle, WorkMeta, FormatLabel, WorkFoot (Price, Rating or New, Availability), MakerLine, Skeleton |
| `pd/artisan-masthead` | Plate (portrait), Seal md, BilingualName, VerifiedLine, AwardList |
| `pd/reputation-summary` | Stars, NewTag, FactList |
| `pd/artisan-facts` | FactList |
| `pd/award-badges` | AwardBadge |
| `pd/reputation-thread` | ReviewTabs, SortSelect, RatingFilter, ThreadSummary, ReviewEntry (ReviewHead, ReviewText, ReviewPhotos, Reply, HelpfulButton), Pagination, EmptyState |
| `pd/review-form` | StarInput, SubRatings, PhotoInput, FieldError |
| `pd/award-index` | LedgerRow |
| `pd/award-winners` | WinnerGroup, WinnerRow, CommissionOrAvailable |
| `pd/work-gallery` | Plate, ThumbButton |
| `pd/buy-box` | MakerLine, CatalogueLine, WorkTitle, FactList, PriceRow, TaxNote, StockLine, PincodeCheck, BuyNow, CommissionNow (From, Presets, How), AddToBag, Disclosures, ReturnRule, GiBadge, ArtisanRatingLine |
| `pd/cart-groups` | BagGroup, SealLink, BagLine, WorkThumb, QtyStepper, UndoNote, DeliveryEstimate, BagEmpty |
| `pd/checkout-flow` | StepList, ReservationTimer, GiftOptions, ReviewOrder, OrderThumbs, ErrorSummary |
| `pd/order-groups` | MakerGroup, WorkThumb, OrderTimeline, MessageLink |
| `pd/commission-request` | CommissionReference, Field, CommissionTerms, QuoteCard, MilestoneList, ProgressPhotos, MessageList |
| `pd/my-commissions` | CommissionRow, ProgressPhotos |
| `pd/studio` | TodoList, AllocationTable, ShipForm, CommissionInbox, ReviewInbox, ReplyForm, SaleBreakdown, Payouts, AwaySwitch, IdentityEditor |
| `pd/featured-artisan` | Plate, Seal, BilingualName, QuoteLine |
| `pd/craft-ledger` | LedgerRow |
| `pd/follow-feed` | WorkCardInline |
| `pd/search-results` | ResultGroup, SuggestionList |
| `pd/follow-button` | FollowState |

## 5. Variations, styles, patterns, templates

### Block variations
| Base block | Variation | Purpose |
|---|---|---|
| `core/query` | `pd-artisans` | Query over the pd_artisan post type. Provides postId to pd/artisan-card. Router region for filters. |
| `woocommerce/product-collection` | `pd-works` | Works grid. Uses pd/work-card as its card and pd/catalogue-filters. |
| `woocommerce/product-collection` | `pd-artisan-shelf` | Works of one artisan, maker line hidden, link to all works. |
| `woocommerce/product-collection` | `pd-related-works` | Three related works, same maker first. |
| `core/navigation` | `pd-main-nav` | Artisans, Awards, Studio (artisan role). Devanagari sub-labels. |
| `core/pullquote` | `pd-artisan-quote` | Kalam quote with the signed flourish. |
| `core/group` | `pd-painted-frame` | Double-rule frame with the accent top band. |
| `core/button` | `pd-commission-now` | Link to the commission request, pre-filled when a work is known. |
| `core/image` | `pd-plate` | 4:5 plate styling for editorial images in a story. |

### Style variations
| Style | Use |
|---|---|
| `is-style-calm` | Checkout calm mode: no search, no navigation noise. |
| `is-style-ledger` | Ruled ledger layout for lists. |
| `is-style-hatch` | Hatch fill for skeletons and progress. |
| `is-style-double-rule` | Double rule separator. |

### Templates
| Template | Contents |
|---|---|
| `front-page` | Home: pattern pd/home-haat |
| `archive-pd_artisan` | Artisan directory: core/query pd-artisans + pd/catalogue-filters + pd/artisan-card |
| `single-pd_artisan` | Artisan page: pd/artisan-masthead, story as core/post-content, pattern pd/artisan-practice, shelf, pd/reputation-thread |
| `archive-product` | Works: Product Collection pd-works + pd/catalogue-filters + pd/work-card |
| `taxonomy-product_cat` | Craft page: craft intro pattern + works + artisans |
| `single-product` | Work page: pd/work-gallery, pd/buy-box, pd/reputation-thread (scope product), related works |
| `taxonomy-pd_award` | Award page: pd/award-winners |
| `page-awards` | Awards hall: pd/award-index |
| `page-cart` | Bag: WooCommerce Cart block with pd/cart-groups |
| `page-checkout` | Checkout: WooCommerce Checkout block with pd/checkout-flow |
| `order-confirmation` | Confirmation: WooCommerce order confirmation blocks + pd/order-groups |
| `search` | Search: pd/search-results |
| `page-studio` | Studio (artisan role): pd/studio |
| `page-my-commissions` | My commissions: pd/my-commissions |
| `page-leave-review` | Leave a review: pd/review-form |
| `page-commission` | Commission request: pd/commission-request |

### Template parts
| Part | Contents |
|---|---|
| `header` | pd/app-shell |
| `checkout-header` | Calm header, logo and step list only |
| `footer` | Static footer |

### Patterns
| Pattern | Contents |
|---|---|
| `pd/home-haat` | Home haat: featured artisan, directory row, craft ledger, awards teaser, follow feed |
| `pd/artisan-practice` | core/columns: story text, pd-plate image, pd/artisan-facts |
| `pd/awards-teaser` | Group, text and button to the Awards hall |
| `pd/craft-intro` | Craft page opening: heading, region, GI status |
| `pd/directory-intro` | Directory heading and note |
| `pd/how-commissioning-works` | The six steps of commissioning, reused on the buy box, artisan page and help |
| `pd/empty-state` | Designed empty state with one action |

## 6. Internal PHP view components

Printed by `pd_component( $name, $args )` from `pd-blocks/src/components/`. Each takes plain data, returns escaped markup, and carries the transition attributes where it is a transition source or destination. Not registered with WordPress.

| Component | What it prints |
|---|---|
| `Seal` | xs 30, sm 56, md 96. Pigment disc with monogram, motif ring. |
| `Plate` | 4:5 and 3:2 plates; portrait, work, gallery, studio, thumbnail variants. |
| `Stars` | Haldi stars with rated-out-of-5 label. |
| `NewTag` | "New" under 3 reviews (REV-08). |
| `CatalogueLine` | "Work No. 014 · by Meera Devi". One string everywhere. |
| `CraftLine` | Craft · village. |
| `VerifiedLine` | Verified artisan · since year. |
| `RatingLine` | Stars + number + count, or New. |
| `Availability` | One available / Made on order. |
| `FormatLabel` | Ready to ship, Made on order, Commission, or a pair (BUY-09). |
| `ShipsIn` | Ships in 3 to 5 days. |
| `OrderStatus` | Taking orders now / Between orders / Away. |
| `BilingualName` | English large, Devanagari beneath, lang attributes. |
| `WorkTitle` | Yatra One title with Devanagari line. |
| `WorkMeta` | Material, size, making time. |
| `WorkFoot` | Price, rating, availability row. |
| `PriceRow` | Price and availability. |
| `TaxNote` | Price includes GST, shipping shown before you pay. |
| `FactList` | Definition list of facts. |
| `MakerLine` | Seal xs + name link. |
| `SealLink` | Seal + bilingual name link; the shared-element source for artisans. |
| `AwardBadge` | Diamond mark + name + year. |
| `ReviewHead` | Stars, verified, name, city, date, edited mark, custom-piece label. |
| `ReviewEntry` | Head, text, photos, reply, helpful. Carries transition identity. |
| `Reply` | Curved line, Seal xs, Kalam text, edit window. |
| `ReplyCounter` | n of 500. |
| `BagGroup` | Per-artisan group with left double rule, Seal sm, delivery estimate. |
| `BagLine` | 64px thumb, catalogue line, qty, price, Remove with Undo. |
| `WorkThumb` | 64px plate, shared-element destination for the bag. |
| `OrderThumbs` | Side summary thumbnails. |
| `OrderTimeline` | Placed, made by hand, on its way. |
| `Skeleton` | Shaped like portrait row, work plate, ledger, thread. |
| `ProgressLine` | Hatch line under the shell. |
| `LiveRegions` | Polite status and assertive alert. |
| `ErrorSummary` | One summary, focus on it. |
| `EmptyState` | Designed empty state. |

## 7. Review architecture

Product reviews are WooCommerce comments (type `review`). Artisan reviews are rows in `pd_artisan_reviews`, replies in `pd_artisan_review_replies`. They are **not** one data source and WordPress comment blocks are not used to merge them.

### Review DTO (one shape for both)
```json
{ "id": "review-product-912", "type": "product", "artisanId": 184, "productId": 3014,
  "rating": 5, "authorName": "Suresh P., Delhi", "verifiedPurchase": true,
  "content": "...", "lang": "hi", "createdAt": "2026-10-03T09:12:00Z", "edited": false,
  "photos": [], "kindLabel": null, "moderation": "published",   // moderation only for authorised callers
  "reply": { "text": "...", "lang": "hi", "createdAt": "...", "editableUntil": "..." } | null }
```

`PD\Reviews\Repository` (in `pd-marketplace`) exposes `find( $typedId )`, `query( $args )` and `counts( $args )`, always returning DTOs. Callers never touch the two source tables directly.

| Topic | Decision |
|---|---|
| Scopes | Artisan reviews (default) and product reviews are two lists, as REV-10 requires. There is no mixed list, so no cross-table paging problem. Piece page: product scope first, then a short artisan snippet. |
| Read model | Derived table `pd_review_index` (typed id, type, artisan id, product id, rating, has photos, language, state, created at). Rebuildable from the sources. Lists query the index and hydrate DTOs from the sources. |
| Filtering | Rating, with photos, language; product scope also by work and by craft. URL parameters. |
| Sorting | Most recent (default), highest, lowest, with photos. URL parameter `sort`. |
| Pagination | Numbered pages `?rp=2` over the index, server rendered. With JavaScript the Router swaps only the `pd-reviews` region. Switch to a keyset cursor (created_at, id) if an artisan passes 10,000 reviews. |
| Deep link | `?review=review-product-912`. The repository finds the entry's scope and page in the current sort, the server renders that page, the entry is highlighted and focused. |
| Cache invalidation | On create, edit, reply, moderate: update the index row, recalculate the rating cache in `pd_artisans`, bump an object-cache version for the artisan, purge the artisan page, the work page and any home snippet. |
| Permissions | Create or edit: buyer with a delivered order. Reply: owning artisan or consented collective manager, once, editable 7 days. Moderate: moderator capability. Artisans cannot delete, hide or reorder. |
| Moderation | States pending, published, flagged, removed. Only published rows reach public output or hydrated state. Reasons limited to the published policy. |
| Reply creation | REST `POST /reviews/{id}/reply` with JavaScript, plain form post to `admin-post.php` without. Capability and ownership checked on the server; draft kept on failure. |
| Progressive enhancement | Tabs, sort and filter are links and a GET form. Paging is links. Report and reply are form posts with redirect. JavaScript only swaps the region and manages focus. |

## 8. Order architecture

**Decision: one canonical WooCommerce order plus artisan allocations.** No child WooCommerce orders. Reasons are in SRS 3.5.4: refunds, tax, stock, emails, analytics, payment reconciliation, HPOS.

| Table | Columns |
|---|---|
| `pd_order_allocations` | id, wc_order_id, artisan_id, status, items_subtotal, commission, artisan_payable, shipping_amount, tax_amount, est_ready_date, accepted_at, ready_at, shipped_at, completed_at |
| `pd_order_allocation_items` | allocation_id, wc_order_item_id, quantity, unit_amount, commission_amount, artisan_amount |
| `pd_shipments` | id, wc_order_id, allocation_id, carrier, awb, tracking_url, status, shipped_at, delivered_at |

| WooCommerce owns | Priyadarshini allocations own |
|---|---|
| Customer order, payment, tax, coupons, stock reduction, refunds, customer totals, checkout status | Artisan work queue, making status, estimated ready date, artisan payout calculation, shipment status per artisan, artisan timeline |

- Allocations are created idempotently when payment completes. Amounts must reconcile to the paisa against the order (DB-09); a refund recalculates the affected allocation items (DB-10).
- Shipping: WooCommerce shipping packages are split by artisan so each shipping line maps to one allocation.
- Reviews (REV-04) and payouts (PAY) key to the allocation.
- Blocks never read allocations directly in public output. `pd/order-groups` shows customer-visible fields to the order owner only; `pd/studio` shows own rows only.

## 9. State model and stores

| Category | Lifetime | Examples | Held in |
|---|---|---|---|
| A · URL | Shareable and survives reload | Filters, sort, page, search query, checkout step, review target, award and year, gallery image, commission prefill | URL parameters read on the server; Router updates history |
| B · UI | Local and forgotten on navigation | Menu open, search expanded, disclosures, pending visual states, selected dashboard row, gallery hover | Interactivity API context |
| C · Session | Lives in the browser tab | Splash seen, scroll and focus snapshots, navigation origin, recently fetched routed responses, reply and commission drafts | sessionStorage, in-memory map (last 10 routes) |
| D · Server / business | Truth lives on the server | Prices, stock, bag, orders, payments, follows, reviews, replies, commissions, verification, payouts | WooCommerce, pd-marketplace tables; Interactivity API only mirrors it |

**Rule.** If it must be shared, survive reload or be truthful, it is URL or server state. The Interactivity API mirrors server state for the screen and never decides it. `pd/bag` and `pd/checkout` do not hold cart or checkout truth.

### Store contracts
#### `pd/shell`

Menu, search and shell presentation. Owned by pd/app-shell, pd/search-results.

| Item | Contract |
|---|---|
| Server-seeded state | route label, maker context (public), bag count, signed-in role (boolean) |
| Client-only state | menuOpen, searchOpen, suggestions, activeSuggestion |
| Derived state | isCompactNav, contextText |
| Actions | toggleMenu, openSearch, closeSearch, chooseSuggestion |
| Callbacks | onResize, onEscape |
| REST / Store API | GET pd/v1/search/suggest (public) |
| URL synchronisation | Search query goes to the URL only on submit |
| Loading | Suggestions show a small skeleton after 300 ms |
| Error and rollback | Keeps typed text; shows "Try again" |
| Announcement | Polite: "5 suggestions" |
| Persistence | UI only. Reset on navigation (menu closes) |

#### `pd/navigation`

Navigation lifecycle, transitions, focus, scroll and announcements. Owned by pd/app-shell, pd/routed-main.

| Item | Contract |
|---|---|
| Server-seeded state | page title, heading id, transition ids on the page |
| Client-only state | phase (idle, pending, loading, settling), source (entity, id, focusId, scroll), progressVisible, skeletonVisible |
| Derived state | isNavigating, reducedMotion |
| Actions | navigate, back, prefetch, retry |
| Callbacks | onLinkIntent, onPopState, onRouteSettled |
| REST / Store API | Interactivity Router (HTML of the next page) |
| URL synchronisation | Owns history entries. Never invents URLs |
| Loading | Progress line at about 100 ms, skeleton at about 300 ms, old content stays |
| Error and rollback | Keeps old content, polite error with Try again |
| Announcement | Polite: page title. Assertive only on failure |
| Persistence | Session: scroll and focus snapshots for the last 10 routes, splash seen flag |

#### `pd/catalogue`

Filter controls and result-update status. Owned by pd/catalogue-filters, pd/award-winners.

| Item | Contract |
|---|---|
| Server-seeded state | available filters and counts, current values from the URL |
| Client-only state | disclosureOpen, pendingField |
| Derived state | activeFilterCount, summaryText |
| Actions | setFilter, setSort, reset, toggleView |
| Callbacks | onResultsReplaced |
| REST / Store API | Router navigation to the new URL (no custom fetch) |
| URL synchronisation | All filters, sort, page, view are URL parameters with replace for typing, push for pick |
| Loading | Results dim slightly; skeleton cards after 300 ms |
| Error and rollback | Previous results stay; Try again |
| Announcement | Polite: "2 makers, Madhubani painting, highest rated first" |
| Persistence | URL only. Disclosure state resets |

#### `pd/artisan`

Follow and commission interaction state. Owned by pd/follow-button, pd/artisan-masthead, pd/commission-request.

| Item | Contract |
|---|---|
| Server-seeded state | followedByMe (own), followerCount (public), takingOrders |
| Client-only state | followPending, commissionOpen, draft fields |
| Derived state | followLabel |
| Actions | toggleFollow, openCommission, saveDraft, sendRequest |
| Callbacks | none |
| REST / Store API | POST pd/v1/follow/{postId}, POST pd/v1/commissions |
| URL synchronisation | ?commission=1&work={id} opens the form |
| Loading | Optimistic follow with rollback |
| Error and rollback | Rollback follow; keep commission input; Try again |
| Announcement | Polite: "You follow Meera Devi" |
| Persistence | Draft in session storage |

#### `pd/work`

Gallery, pincode state and add-to-bag presentation. Owned by pd/work-gallery, pd/buy-box.

| Item | Contract |
|---|---|
| Server-seeded state | gallery items, price, stock (public), formats |
| Client-only state | selectedImage, pincode, pincodeResult, addState |
| Derived state | canBuyNow, primaryAction |
| Actions | selectImage, checkPincode, addToBag, buyNow |
| Callbacks | none |
| REST / Store API | GET pd/v1/delivery?pin= (public), Store API add-item |
| URL synchronisation | ?img=n for the gallery selection |
| Loading | Pressed state, then optimistic "In your bag" only for add to bag |
| Error and rollback | Roll back "In your bag", keep pincode, Try again |
| Announcement | Polite: "Added to your bag" |
| Persistence | UI only |

#### `pd/reviews`

Review filters, deep-link highlight and reply draft. Owned by pd/reputation-thread, pd/review-form, studio reply.

| Item | Contract |
|---|---|
| Server-seeded state | scope, sort, page, target id, counts |
| Client-only state | highlightId, replyDraft, replyPending, reportOpen |
| Derived state | visibleCount |
| Actions | setScope, setSort, setFilter, goToPage, focusTarget, saveReplyDraft, sendReply |
| Callbacks | onThreadReplaced (focus target) |
| REST / Store API | GET pd/v1/artisans/{id}/reviews, POST pd/v1/reviews/{typed-id}/reply |
| URL synchronisation | scope, sort, rating, photos, rp, review |
| Loading | Thread region keeps old entries; shaped skeleton after 300 ms |
| Error and rollback | Reply draft kept; rollback optimistic publish; Try again |
| Announcement | Polite: "4 reviews, 3 with a reply" |
| Persistence | Reply draft in session storage |

#### `pd/bag`

Thin presentation adapter over the WooCommerce cart store. Owned by pd/cart-groups, pd/app-shell bag link.

| Item | Contract |
|---|---|
| Server-seeded state | nothing of its own; reads the WooCommerce cart store state |
| Client-only state | undoStack (last removed line), flyingThumb |
| Derived state | groups by artisan (from extensions.pd), count |
| Actions | remove, undo, setQuantity (wrap WooCommerce actions) |
| Callbacks | onCartChanged (updates the bag link) |
| REST / Store API | WooCommerce Store API (authoritative) |
| URL synchronisation | none |
| Loading | Optimistic remove with Undo; quantity shows pending |
| Error and rollback | Rollback to server cart, polite message |
| Announcement | Polite: "Removed X. Undo is available" |
| Persistence | Server (cart session). Undo is UI only |

#### `pd/checkout`

Step presentation only. Owned by pd/checkout-flow.

| Item | Contract |
|---|---|
| Server-seeded state | step from the URL, reservation expiry (server time) |
| Client-only state | stepFocus, timerText |
| Derived state | minutesLeft |
| Actions | goToStep, focusErrors |
| Callbacks | onTick |
| REST / Store API | WooCommerce checkout store and Store API (authoritative) |
| URL synchronisation | ?step=1\|2\|3 with a history entry per step |
| Loading | Place order is aria-disabled while placing; never shows success until the server confirms |
| Error and rollback | Error summary with focus; input kept; Try again |
| Announcement | Polite: step name and "5 minutes left"; assertive at 1 minute |
| Persistence | URL and server reservation |

#### `pd/studio`

Artisan workspace presentation. Owned by pd/studio.

| Item | Contract |
|---|---|
| Server-seeded state | panel counts, own queue rows (never public) |
| Client-only state | selectedRow, filterText, replyOpen |
| Derived state | needsAttention |
| Actions | selectPanel, markAccepted, markReady, ship, reply, setAway |
| Callbacks | none |
| REST / Store API | pd/v1/studio/* (own rows only) |
| URL synchronisation | /studio/{panel} |
| Loading | Optimistic only for reversible toggles; ship and reply wait for the server |
| Error and rollback | Rollback; draft kept |
| Announcement | Polite: "Reply published" |
| Persistence | UI only |

### 9.1 Shared state contract (how blocks share global state)

Stores are global per namespace; blocks are many. This contract keeps every block that shows the same value in agreement.

| Rule | Meaning |
|---|---|
| One owner, one writer | Every shared key has exactly one owning store. Other blocks read it and ask the owner to change it through the owner's actions. They never write it. |
| State is global, context is local | Anything two blocks must agree on is store state. Anything that only identifies one instance (workId, artisanPostId, reviewId, itemKey) is `data-wp-context`. |
| Mirrors are derived, never copied | A block binds to the owner's state or a getter on it. It does not keep its own copy of a shared value, so it cannot drift. |
| Seed once, in PHP | A single provider (`PD\Blocks\State`) seeds each store with `wp_interactivity_state()` once per request. Blocks call `State::require( "pd/bag" )`; they do not seed the same keys themselves, so merge order never matters. |
| Public surface only | Other blocks and stores read documented keys and call documented actions. Private keys start with an underscore. A store never reaches into another store's private keys. |
| Talk by action or event | Cross-store changes go through the owner's action, or a named event (`pd:bag:changed`, `pd:reviews:replied`) that stores subscribe to with `data-wp-watch`. No store polls another. |
| Server truth wins | Every Store API or REST response overwrites the owner's key. Mirrors do not reconcile themselves. |
| Optimistic writes live in the owner | The owner applies the change, keeps a pending token, then replaces it with the server value or rolls back. Because mirrors are derived they all update together, in both directions. |
| Router lifecycle is declared per key | Each key is reseed (server value replaces it on every navigation), persist (kept across navigation) or reset (cleared on navigation). The table below fixes it so nothing stale survives a route change. |
| Nothing private in shared state | Shared state holds only public values and the signed-in user's own booleans (SEC-10). No addresses, phones, notes, payment, payout or verification data. |
| Provable | A check compares every mirror of every shared key with its owner after each change. The prototype runs it live; the build runs it in Playwright on each journey. |

#### Ownership of every shared key

| Key | Owner | Source of truth | Written by | Read by | Lifetime | Router rule |
|---|---|---|---|---|---|---|
| bag.count | pd/bag (reads the WooCommerce cart store) | WooCommerce cart | pd/bag actions, WooCommerce | pd/app-shell bag link, pd/cart-groups, pd/buy-box, pd/checkout-flow | Server session | Reseed on every navigation |
| bag.lines (grouped by artisan) | pd/bag | WooCommerce cart + extensions.pd | pd/bag actions | pd/cart-groups, pd/checkout-flow summary, pd/order-groups (server) | Server session | Reseed |
| bag.undo | pd/bag | Client only | pd/bag.remove, pd/bag.undo | pd/cart-groups | 8 seconds | Reset on navigation |
| bag.has(workId) | pd/bag (derived getter) | Derived from bag.lines | nobody | pd/buy-box, pd/work-card | Derived | Recomputed |
| work.selectedImage | pd/work | URL ?img= | pd/work.selectImage | pd/work-gallery, pd/buy-box | Page | Reseed from URL |
| work.pincode, pincodeResult | pd/work | Browser session | pd/work.checkPincode | pd/buy-box, pd/cart-groups (delivery estimate), pd/checkout-flow (default PIN) | Tab session | Persist |
| artisan.followed[artisanPostId] | pd/artisan | pd_follows (server) | pd/follow-button via pd/artisan.toggleFollow | pd/artisan-masthead, pd/follow-button anywhere, pd/follow-feed | Server | Reseed |
| artisan.followerCount[artisanPostId] | pd/artisan | pd_artisans (public column) | pd/artisan.toggleFollow (optimistic), server reply | pd/artisan-masthead, pd/artisan-card | Server | Reseed |
| reviews.scope, sort, rating, page, target | pd/reviews | URL | pd/reviews actions | pd/reputation-thread, ThreadSummary, review links | Page | Reseed from URL |
| reviews.replyDraft[reviewId] | pd/reviews | Browser session | pd/reviews.saveReplyDraft | pd/studio ReplyForm, thread entry (own reviews) | Tab session | Persist |
| reviews.unreplied | pd/reviews | Review repository (server) | pd/reviews.sendReply (optimistic decrement) | pd/app-shell studio badge, pd/studio TodoList, ReviewInbox | Server | Reseed |
| catalogue.filters, sort, page | pd/catalogue | URL | pd/catalogue actions | pd/catalogue-filters, result regions, award winners view | Page | Reseed from URL |
| catalogue.resultCount | pd/catalogue (getter) | Server HTML of the region | nobody | ResultCount, context row of the shell | Page | Reseed |
| shell.menuOpen, searchOpen | pd/shell | Client only | pd/shell actions | pd/app-shell, pd/search-results | UI | Reset on navigation |
| navigation.phase, progressVisible | pd/navigation | Client only | pd/navigation only | ProgressLine in the shell, skeleton regions | UI | Reset when settled |
| navigation.source, snapshots (scroll, focus) | pd/navigation | Browser session | pd/navigation only | pd/navigation (Back restore) | Last 10 routes | Persist |
| session.splashSeen | pd/navigation | Browser session | pd/app-shell splash | pd/app-shell | Tab session | Persist |
| checkout.step | pd/checkout | URL ?step= | pd/checkout.goToStep | pd/checkout-flow StepList, context row, checkout header | Page | Reseed from URL |
| checkout.reservationExpiry | pd/checkout | WooCommerce checkout + reservation (server time) | nobody on the client | ReservationTimer | Server | Reseed |
| checkout.placing | pd/checkout | Client only | pd/checkout.place | ReviewOrder button | UI | Reset |
| session.role, capabilities (booleans) | pd/shell | Server | nobody on the client | Studio link, pd/follow-button, pd/studio | Server session | Reseed |
| studio.panel | pd/studio | URL /studio/{panel} | pd/studio.selectPanel | pd/studio | Page | Reseed from URL |
| route.accent (maker context) | pd/navigation | Server (routed-main attribute) | nobody on the client | Cards, thread (not the shell) | Page | Reseed |

#### Seeding in PHP

```php
// pd-blocks/src/State.php: the only place that calls wp_interactivity_state()
PD\Blocks\State::require( "pd/bag" );       // idempotent: seeds once per request
PD\Blocks\State::require( "pd/artisan" );   // public follow flags and counts for artisans on this page
// A block's render.php calls require() for the stores it uses and prints data-wp-interactive.
// It never calls wp_interactivity_state() itself and never seeds another store's keys.
```

#### Reading and writing across stores

```js
// pd/app-shell view.js: reads the bag, never writes it
const bag = store( "pd/bag" );          // public: state.count
// pd/buy-box view.js: asks the owner to change it
actions.addToBag = function* () { yield bag.actions.add( getContext().workId ); };
// pd/reviews announces; pd/reviews owns "unreplied", the shell badge only reads it
document.dispatchEvent( new CustomEvent( "pd:reviews:replied", { detail: { reviewId } } ) );
```

#### Router lifecycle

- **Reseed**: the new page seeds the key and replaces the client value. Used for anything the server can change (bag, follows, counts, unreplied).
- **Persist**: survives navigation, held in session storage or the persistent shell (PIN, drafts, scroll and focus snapshots, splash seen).
- **Reset**: cleared when the route settles (menu open, disclosures, pending flags).
- A key not listed in the table above is not shared and must stay in one block's context.

### Shared utilities
| Module | Does |
|---|---|
| `pd/lib/announce` | Polite and assertive messages |
| `pd/lib/focus` | Focus ids, restore, move to heading |
| `pd/lib/scroll` | Scroll snapshots |
| `pd/lib/transition` | Shared-element snapshots, reduced-motion check |
| `pd/lib/optimistic` | Apply, reconcile, rollback |
| `pd/lib/prefetch` | Intent detection |
| `pd/lib/urlstate` | Read and write shareable state |
| `pd/lib/session` | Session storage helpers |

## 10. Shared-element navigation contract

### 10.1 Attributes
```html
<a href="/a/meera-devi/"
   data-pd-entity="artisan"
   data-pd-entity-id="184"
   data-pd-transition="artisan-184"
   data-pd-focus-id="artisan-184-link">
```

| Entity | Transition id | Visual | Id source |
|---|---|---|---|
| artisan | artisan-{postId} | Seal, portrait or winner portrait | post id of pd_artisan |
| work | work-{productId} | Work plate | WooCommerce product id |
| review | review-{type}-{id} | Review entry | typed id: review-product-912, review-artisan-45 |
| award | award-{termId} | Award badge or row | pd_award term id |

- Identity comes only from immutable ids. Never from titles, translated text, list position or DOM index.
- The destination exposes the same entity and transition identity. A page may have several sources of one identity (a card and a masthead); the Router picks the one that matches the direction of travel.
- The real accessible node is never moved between documents. Motion uses browser view-transition snapshots or a non-semantic clone with `aria-hidden`.

### 10.2 Navigation lifecycle

1. User activates a normal link (an anchor with an href that works without JavaScript).
2. Capture entity type, id, origin URL, focus id, scroll position and the relevant URL state.
3. Mark the chosen visual as the transition source (adds view-transition-name for that element only).
4. Begin Interactivity Router navigation.
5. Keep the persistent shell mounted.
6. Keep old content visible while loading. Progress line at about 100 ms, shaped skeleton at about 300 ms.
7. Replace the pd-main router region with the server-rendered HTML.
8. Find the destination entity with the same data-pd-transition.
9. Animate the snapshot between source and destination geometry.
10. Settle into the destination layout.
11. Focus the page heading, or the contextual target (the exact review, the invalid field).
12. Announce the new page title politely.
13. Remove temporary transition state (names, classes, snapshots).
14. On Back or Forward, restore the earlier screen, filters, scroll and the control that triggered the navigation, from the session snapshot first, then reconcile with the server.

### 10.3 Motion vocabulary

| Kind | Move | Transition id | Motion | Reduced motion |
|---|---|---|---|---|
| A · Grow and settle | Artisan card or Seal → artisan masthead | artisan-{postId} | Snapshot scales and moves to the masthead Seal; masthead content fades in | Instant swap; masthead Seal gets a double rule for one second |
| A · Grow and settle | Work card plate → work gallery main plate | work-{productId} | Plate grows from the card to the 4:5 main plate | Instant swap; plate outline |
| A · Grow and settle | Award winner portrait → artisan masthead | artisan-{postId} | Same as the artisan transition | Same as the artisan transition |
| B · Shrink and dock | Splash motif → header logo | brand | Motif draws, then shrinks into the logo (1.7 s at most, skippable, once per session) | Static logo; no splash |
| B · Shrink and dock | Work plate → bag thumbnail | work-{productId} | Plate shrinks toward the bag link or the bag line thumbnail | Count updates; thumbnail outlined |
| B · Shrink and dock | Artisan Seal → bag or confirmation group heading | artisan-{postId} | Seal shrinks to Seal sm in the group | Instant; group outlined |
| C · Move and regroup | Work → bag group (line joins its artisan group) | work-{productId} | Line slides into its group | Instant; line outlined |
| C · Move and regroup | Bag lines → checkout summary → confirmation groups | work-{productId}, artisan-{postId} | Thumbnails keep position between pages and regroup | Instant; no movement |
| D · Reveal and focus | Review link → exact review | review-{type}-{id} | Thread scrolls, entry highlights for 1.2 s, focus moves to it | Entry gets a double rule; same focus and announcement |
| D · Reveal and focus | Search suggestion → result group | search-group | Group scrolls into view and highlights | Group outlined |
| D · Reveal and focus | Dashboard notification → task | allocation-{id} or review-{type}-{id} | Task scrolls and highlights | Task outlined |
| D · Reveal and focus | Validation summary → invalid field | field-{name} | Focus moves to the field, field flashes the error rule | Field outlined, same focus |

Motion explains relationships between screens. Links, headings and controls do not morph.

### 10.4 Persistent shell and routed regions

```text
<body>
  persistent application shell        (pd/app-shell, 108px, never replaced)
  persistent progress line
  persistent polite and assertive live regions

  routed main region  "pd-main"       (pd/routed-main, replaced on navigation)
    server-rendered page blocks
      nested regions: pd-catalogue, pd-reviews, pd-award-view, pd-studio
  persistent footer
</body>
```

| Kind | Region | Rule |
|---|---|---|
| Persistent | Application shell (logo, search, bag count, menu, context row) | Never replaced. Fixed 108px. Bag count updates by state, not by replacement. |
| Persistent | Progress line | Shown by pd/navigation after about 100 ms. |
| Persistent | Live regions (polite, assertive) | Survive navigation so announcements are not lost. |
| Persistent | Footer | Static. |
| Router region | pd-main (pd/routed-main) | Replaced on every route change. Focus target is the page heading. |
| Nested router region | pd-catalogue (directory and works grids) | Replaced on filter, sort and page change. The rest of the page stays. |
| Nested router region | pd-reviews (reputation thread) | Replaced on scope, sort, filter and page change. |
| Nested router region | pd-award-view (award winners) | Replaced on view and filter change. |
| Nested router region | pd-studio (studio panel) | Replaced on panel change. |

### 10.5 Feedback ladder

| When | What the buyer sees |
|---|---|
| 0 to 100 ms | Pressed or selected state only. |
| 100 to 300 ms | Optimistic feedback if the action is reversible. Route progress line if navigation is unresolved (from about 100 ms). |
| After about 300 ms | Destination-shaped skeleton: portrait row, 4:5 work plate, ledger rows, review thread. |
| Failure | Keep old content or user input; roll back unsafe optimism; offer Try again. |
| Success | Reconcile with the authoritative server response. |
| Never | Blank paper canvas. Success shown before the server confirms checkout or payment. |

- Prefetch only after strong intent: hover with a short delay, keyboard focus, deliberate touch. Only the target page. Never the catalogue.
- Back restores from the last 10 route snapshots in session storage immediately, then reconciles with the server.
- Checkout and payment are never shown as successful before the server says so.

### 10.6 Reduced motion and fallback

- Under `prefers-reduced-motion`: no movement, scale, fly-to-bag or morph. Content changes instantly or by opacity only. URL, history, focus and announcements are identical. The destination gets a double rule, outline or brief static highlight. The splash is replaced by the static logo.
- If the browser has no view-transition support, or the transition fails: normal routed navigation with the same focus and scroll behaviour. Animation never blocks navigation and is never needed to understand a relationship.

## 11. Page trees

Key: **registered** = our block. **variation** = core or Woo block with a variation. **core** and **Woo** = as is. **internal** = PHP component, not a block. **pattern/part** = theme markup.

### 1 · Shell and routed region (every page)

The shell is outside the router region and is never replaced. Only pd-main changes.
Screens: Every page. SRS: CNT-02, CNT-03, UI-08, UI-09.

- `body` [Pattern / part] Document body. Not a block.
  - `pd/app-shell` [Registered block] PERSISTENT. Header template part.
    - `Splash, Brandmark` [Internal component] Splash docks into the logo. Once per session, skippable, static under reduced motion.
    - `core/navigation (pd-main-nav)` [Variation] Artisans, Awards, Studio.
    - `SearchForm, BagLink, ContextRow` [Internal component] Search form, bag count, 44px context row (maker Seal, work catalogue line, step, studio status).
    - `ProgressLine, LiveRegions` [Internal component] PERSISTENT. Progress line, polite and assertive regions.
  - `pd/routed-main` [Registered block] ROUTED REGION "pd-main". Replaced on every navigation.
    - `page blocks` [Pattern / part] See the trees below.
  - `footer` [Pattern / part] PERSISTENT static template part.

### 2 · Artisan directory (archive-pd_artisan)

Core Query Loop over the pd_artisan post type. Filters join the operational table. Nested router region "pd-catalogue".
Screens: Directory, Home row, Craft page. SRS: DIS-05, BRD-04, PLT-14.

- `core/query (pd-artisans)` [Variation] Query over pd_artisan. tax_query for craft, district, language; posts_clauses join to pd_artisans for taking orders, rating, replies.
  - `pd/catalogue-filters` [Registered block] scope artisans. Fields: craft, district, language, award, open. Sort: Suggested, Highest rated, Most reviews.
    - `FilterSelect, SortSelect, ResultCount, ResetLink` [Internal component] Result count is announced politely.
  - `core/post-template` [Core block] Grid area. Repeats the card.
    - `pd/artisan-card` [Registered block] Card. Reads postId from the query.
      - `Plate, SealLink, CraftLine, VerifiedLine, RatingLine, OrderStatus, ShipsIn` [Internal component] Seal-link is the transition source for artisan-{postId}.
  - `core/query-pagination` [Core block] Router-enhanced.
  - `core/query-no-results` [Variation] Designed empty state with Clear filters.
  - `Skeleton` [Internal component] Portrait-row shapes.

### 3 · Works collection (archive-product, craft page, shelf)

WooCommerce Product Collection stays in charge of the query. We only supply the card and the filters.
Screens: Works, Craft page, Artisan shelf, Related. SRS: CAT-xx, DIS-02, BUY-09.

- `woocommerce/product-collection (pd-works | pd-artisan-shelf | pd-related-works)` [Variation] Router region "pd-catalogue".
  - `pd/catalogue-filters` [Registered block] scope works. Fields: craft, material, price, size, format, availability, occasion, maker. Sort: Newest, Price, Highest rated, Making time.
  - `woocommerce/product-template` [WooCommerce block] Grid area. Two columns under 760, three from 760, four from 1100.
    - `pd/work-card` [Registered block] Card. Reads postId (product). Maker from _pd_artisan_post_id.
      - `Plate, CatalogueLine, WorkTitle, WorkMeta, FormatLabel, WorkFoot, MakerLine` [Internal component] Plate is the transition source for work-{productId}.
  - `woocommerce/product-collection-pagination` [WooCommerce block] Router-enhanced.
  - `woocommerce/product-collection-no-results` [Variation] Empty state.
  - `Skeleton` [Internal component] 4:5 plate-shaped.

### 4 · Artisan page (single-pd_artisan)

Editorial content is core. Public columns come from the operational table through the masthead and reputation blocks.
Screens: Artisan page. SRS: BRD-01 to BRD-12, AWD-04.

- `single-pd_artisan` [Pattern / part] Template. post-content is the story (core/post-content).
  - `pd/artisan-masthead` [Registered block] Provides pd/artisanPostId and pd/accent. Transition destination for artisan-{postId}.
    - `Plate, Seal md, BilingualName, VerifiedLine, AwardList` [Internal component] Seal md is the hero element.
    - `pd/follow-button` [Registered block] Reusable.
    - `core/button (pd-commission-now)` [Variation] Commission now, or See available work.
  - `pd/reputation-summary` [Registered block] Rating, reviews, sold, reply rate. New under 3.
  - `core/pullquote (pd-artisan-quote)` [Variation] Kalam quote.
  - `core/post-content` [Core block] Story text, photos, video.
  - `pattern pd/artisan-practice` [Pattern / part] core/columns
    - `core/image (pd-plate)` [Variation] Studio plate.
    - `pd/artisan-facts` [Registered block] Materials, technique, time, languages, lineage.
  - `pd/award-badges` [Registered block] Awards section.
  - `woocommerce/product-collection (pd-artisan-shelf)` [Variation] Works shelf. Filters available, made on order, sold.
  - `pd/reputation-thread` [Registered block] Scope tabs: artisan reviews first, then product reviews. Router region "pd-reviews".

### 5 · Work page (single-product)

Two registered blocks do the work: the gallery and the buy box.
Screens: Work. SRS: CAT-xx, BUY-xx, REV-10.

- `single-product` [Pattern / part] Template.
  - `pd/work-gallery` [Registered block] Provides pd/workId. Destination for work-{id}.
    - `Plate, ThumbButton` [Internal component] Main plate 4:5.
  - `pd/buy-box` [Registered block] Right column. Internal anatomy only.
    - `MakerLine, CatalogueLine, WorkTitle, FactList, ArtisanRatingLine` [Internal component] Maker line carries the artisan transition identity.
    - `PriceRow, TaxNote, StockLine, PincodeCheck` [Internal component] GST note, stock, delivery estimate.
    - `BuyNow, CommissionNow, AddToBag` [Internal component] Buy now primary while in stock, else Commission now (BUY-02).
    - `Disclosures, ReturnRule, GiBadge` [Internal component] Returns by format (BUY-11).
  - `pd/reputation-thread` [Registered block] scope product: this work first.
  - `woocommerce/product-collection (pd-related-works)` [Variation] Related works.

### 6 · Bag (page-cart)

WooCommerce Cart block is authoritative. Our block replaces the line-items area to group by artisan, using data from the Store API extension.
Screens: Bag. SRS: CHK-01, CHK-04.

- `woocommerce/cart` [WooCommerce block] Cart block. Store API for all changes.
  - `pd/cart-groups` [Registered block] Inner block. Groups lines by artisan from extensions.pd.
    - `BagGroup, SealLink, BagLine, WorkThumb, QtyStepper, UndoNote, DeliveryEstimate` [Internal component] Seal-link is the shared source for the checkout and confirmation groups; thumb is the destination for the work plate.
    - `BagEmpty` [Internal component] Designed empty state.
  - `woocommerce/cart-order-summary-block` [WooCommerce block] Subtotal incl. GST, shipping before you pay, Continue to checkout.

### 7 · Checkout and confirmation

WooCommerce fields and payment remain WooCommerce blocks. We add step presentation, the reservation timer and the grouped summary.
Screens: Checkout, Confirmation. SRS: CHK-02 to CHK-08.

- `woocommerce/checkout` [WooCommerce block] Checkout block. Template part checkout-header, style is-style-calm.
  - `pd/checkout-flow` [Registered block] Inner block. Step presentation only.
    - `StepList, ReservationTimer, GiftOptions, ErrorSummary` [Internal component] Steps live in the URL ?step=. No business state in the store.
    - `ReviewOrder, OrderThumbs` [Internal component] Thumbs are the transition destination for work plates.
  - `woocommerce/checkout-contact-information-block` [WooCommerce block] Fields.
  - `woocommerce/checkout-shipping-address-block` [WooCommerce block] Address and PIN lookup.
  - `woocommerce/checkout-payment-block` [WooCommerce block] Payment values never touch our code.
  - `order-confirmation` [Pattern / part] Template.
    - `woocommerce/order-confirmation-status` [Variation] Thank-you and order number.
    - `pd/order-groups` [Registered block] Per artisan: Seal sm, will make and ship, works, timeline, Message link.
      - `MakerGroup, WorkThumb, OrderTimeline, MessageLink` [Internal component] Reads allocations (customer-visible fields only).

### 8 · Reviews (thread and form)

One dynamic block over the normalised repository. No comment blocks.
Screens: Artisan page, Work page, Review form. SRS: REV-xx, CNT-30 to CNT-38.

- `pd/reputation-thread` [Registered block] Provides pd/reviewId per entry. Router region "pd-reviews".
  - `ReviewTabs, SortSelect, RatingFilter, ThreadSummary` [Internal component] Artisan reviews first (default), product reviews second.
  - `ReviewEntry` [Internal component] Typed id review-product-912 or review-artisan-45. Transition and deep-link target.
    - `ReviewHead, ReviewText, ReviewPhotos` [Internal component] Edited mark, custom-piece label, optional sub-ratings.
    - `Reply` [Internal component] Kalam reply, one per review.
    - `HelpfulButton` [Internal component] Could.
  - `Pagination, EmptyState` [Internal component] ?rp=2.
  - `pd/review-form` [Registered block] Separate template page-leave-review. Rate the piece, rate the artisan.

### 9 · Awards (hall and award page)

Awards are a taxonomy (pd_award) with verified artisan awards in a table.
Screens: Awards. SRS: AWD-xx.

- `page-awards` [Pattern / part] Awards hall.
  - `pd/award-index` [Registered block] Ledger with winner counts.
  - `taxonomy-pd_award` [Pattern / part] Award page.
    - `pd/award-winners` [Registered block] Provides pd/awardId. Grouped by year or by craft; craft and district filters.
      - `WinnerGroup, WinnerRow, CommissionOrAvailable` [Internal component] Winner portrait is the transition source for artisan-{postId}.

### 10 · Commissions

Commissions never use the cart. They are their own order type paid in milestones.
Screens: Commission request, My commissions. SRS: BUY-04 to BUY-08, CR-xx.

- `page-commission` [Pattern / part] Template.
  - `pd/commission-request` [Registered block] Pre-filled from a work or blank.
    - `CommissionReference, Field, CommissionTerms` [Internal component] Cancellation rules before the advance (CR-07).
    - `MessageList, QuoteCard, MilestoneList, ProgressPhotos` [Internal component] Thread with masked contact details (CR-05).
  - `pd/my-commissions` [Registered block] Buyer list.

### 11 · Studio

One registered block with route-driven panels. No public hydration; data loads only for the signed-in artisan.
Screens: Studio. SRS: DSH-xx, SEC-09.

- `page-studio` [Pattern / part] Role-gated template.
  - `pd/studio` [Registered block] Attribute panel: overview, orders, commissions, reviews, earnings, profile. Router region "pd-studio".
    - `TodoList (orders, commission requests, unreplied reviews, earnings)` [Internal component] DSH-01 order.
    - `AllocationTable, ShipForm` [Internal component] Own allocations only.
    - `ReviewInbox, ReplyForm, ReplyCounter` [Internal component] Reply workflow.
    - `SaleBreakdown, Payouts, AwaySwitch, IdentityEditor` [Internal component] DSH-03, DSH-04, DSH-07.

### 12 · Home and search

Mostly patterns.
Screens: Home, Search. SRS: DIS-01, DIS-03, AWD-09.

- `front-page` [Pattern / part] Pattern pd/home-haat.
  - `pd/featured-artisan` [Registered block] Editor picks the artisan.
  - `core/query (pd-artisans)` [Variation] Four artisans.
  - `pd/craft-ledger` [Registered block] Crafts and counts.
  - `pattern pd/awards-teaser` [Pattern / part] Curated award-winning masters slot.
  - `pd/follow-feed` [Registered block] Signed-in only.

### 12b · Search

Screens: Search. SRS: DIS-03, DIS-04.

- `search` [Pattern / part] Template.
  - `pd/search-results` [Registered block] Artisans and works in separate labelled groups.
    - `ResultGroup, SuggestionList` [Internal component] Suggestion pick is the source for the result group (reveal and focus).

## 12. Context and data boundaries

| Context key | Defined by | Provided by | Meaning |
|---|---|---|---|
| postId, postType, queryId | core | Query Loop and Product Collection | Cards read the post from here |
| pd/artisanPostId | pd-blocks | pd/artisan-masthead, pd/featured-artisan, single-pd_artisan template | The immutable entity id for an artisan. Never the ops table id |
| pd/accent | pd-blocks | Masthead and cards (never the shell) | Token name only (gulabi, kusum ...). Public |
| pd/workId | pd-blocks | pd/work-gallery | WooCommerce product id |
| pd/awardId | pd-blocks | pd/award-winners | pd_award term id |
| pd/reviewId | pd-blocks | Thread entries | Typed id: review-product-912 or review-artisan-45 |
| pd/route | pd-blocks | pd/routed-main | Template name for the context row and announcement |

| Data | Source | Visibility | Used by |
|---|---|---|---|
| Artisan identity (name, story, media, village, lineage, taxonomies) | pd_artisan post | Public | pd/artisan-card, masthead, facts, featured, search |
| Artisan operations (accent, motif, taking orders, lead time, rating, replies, sales) | pd_artisans table, public columns only | Public columns only | Cards, masthead, reputation summary, buy box |
| Artisan operations (user id, verified_at, notes, payout refs) | pd_artisans table, private columns | Private. Capability gated | Studio, admin only |
| Works, price, stock, gallery | WooCommerce product | Public | Work card, gallery, buy box |
| Product reviews | WooCommerce comments type review | Public when published | Review repository |
| Artisan reviews and replies | pd_artisan_reviews, pd_artisan_review_replies | Public when published | Review repository |
| Review list model | pd_review_index (derived) | Public rows only | Review repository |
| Cart | WooCommerce cart and Store API | Own session only | pd/cart-groups |
| Customer order, payment, tax, refunds | WooCommerce order | Owner only | Confirmation, account |
| Order allocations, items, shipments | pd_order_allocations, pd_order_allocation_items, pd_shipments | Artisan: own rows. Buyer: customer-visible fields of own order | Studio, pd/order-groups |
| Commissions and threads | pd_commission + thread | Buyer, artisan, admin | pd/commission-request, my commissions, studio |
| Follows | pd_follows | Own rows; counts public | pd/follow-button, pd/follow-feed |
| Payouts, bank details, verification documents | pd_payouts, protected storage | Finance and verifier roles only | Never in any block output |

### Security and privacy boundaries

- Every REST endpoint and form handler checks capability and ownership on the server. Nonces prove intent only (SEC-08).
| Area | Who may act | Check |
|---|---|---|
| Artisan ownership of works | Owning artisan, consented collective manager, admin | Product `_pd_artisan_post_id` matches the caller's artisan |
| Review replies | Owning artisan or consented manager | Review artisan id matches; one reply; 7 day edit |
| Order allocations | Owning artisan | Allocation artisan id from the logged-in user, never from the request |
| Customer orders | Order owner, admin | Order customer id matches |
| Collective-manager delegation | Manager for consented artisans | Link row active with permissions |
| Awards verification | Admin | Capability pd_verify_awards |
| Payout visibility | Artisan (own), finance | Own artisan id; finance capability |
| Commission threads | Buyer, artisan, admin | Participant check on the request |
| Profile changes | Artisan for own post; trust fields via admin review | Revision plus AuditLog |

**Public Interactivity API state and inline JSON never contain** customer addresses, phone numbers, private order notes, payment information, payout account information, verification documents, internal moderation notes or other artisans' private data. A test scans page output and public REST for these (spike 13).

## 13. Plugin structure, permissions and locks

```text
pd-marketplace/           data and rules
  src/Artisans/           CPT, ops table, queries, verification
  src/Reviews/            repository, index, moderation, replies
  src/Orders/             allocations, items, shipments, reconciliation
  src/Commissions/  src/Follow/  src/Payouts/  src/Awards/
  src/Rest/               pd/v1 controllers, Store API extension
pd-blocks/                presentation
  src/blocks/<name>/      26 blocks: block.json, render.php, edit.js, view.js if interactive
  src/components/         36 PHP view components
  src/stores/             pd/shell, navigation, catalogue, artisan, work, reviews, bag, checkout, studio
  src/lib/                announce, focus, scroll, transition, optimistic, prefetch, urlstate, session
  patterns/               block patterns
pd-theme/                 block theme: theme.json tokens, templates, parts, style variations
```

**Decided (owner, 2026-11-07): two plugins plus a theme.** This fits PLT-07 and PLT-08: marketplace behaviour lives in `pd-marketplace`; `pd-blocks` is presentation only.

| Role | Editor permissions |
|---|---|
| Administrator | All blocks, all templates, patterns, template parts |
| Editor (content team) | Insert and edit pd/featured-artisan, pd/craft-ledger, pd/follow-feed, patterns; edit page content; cannot edit templates or template parts |
| Shop manager | Works and orders; no block-template editing |
| Artisan | No block editor access to templates. Pro artisans edit own pd_artisan post content (story, media) in a restricted block editor (paragraph, heading, image, gallery, quote, list, video); other artisans use the form; never templates |
| Collective manager | Same as artisan for consented artisans |
| Buyer | No editor access |

| Block or area | allowedBlocks / templateLock |
|---|---|
| Cards, masthead, buy-box, thread and every leaf | No InnerBlocks; anatomy is internal PHP, so editors cannot break it |
| pd/artisan-masthead | templateLock contentOnly over [pd/follow-button, button pd-commission-now] |
| pd/routed-main | Hidden from the inserter; templates only |
| pd/app-shell | templateLock all; inside core/navigation only |
| Page sections (home, craft intro) | Pattern based; editors may reorder and edit text, not change registered blocks inside locked patterns |

## 14. M−1 spikes and milestones

### M−1: Feasibility and extension spikes (before any foundation work)

**The architecture is not approved until every spike below passes.** Each spike has a fallback and names the feature at risk, so a failure changes scope, not the whole plan.

| # | Spike | Method | Pass when | Fallback and feature at risk |
|---|---|---|---|---|
| 1 | Artisan CPT plus operational-table querying | Register pd_artisan with taxonomies; join pd_artisans in posts_clauses; run core/query with tax_query, taking-orders filter and rating sort. | Directory of 200 sample artisans queries in under 100 ms with correct counts and pagination; Query Loop works unmodified. | Query a denormalised meta copy kept by hooks. Feature at risk: sort by rating. |
| 2 | Product Collection customisation | Custom card block inside woocommerce/product-template; registered collection variations; extra filters. | Card renders, filters change the query, Router swaps the region. | Use core/query on product with our own card and filters. Risk: WooCommerce collection features. |
| 3 | Artisan data in Store API | Register extensions.pd with public artisan fields on cart items using ExtendSchema. | Cart response carries artisan post id, name, accent, delivery estimate and nothing private. | Look up artisan data on render. Risk: client-only changes show late. |
| 4 | Cart grouped by artisan | Inner block that replaces the line-items area in the Cart block. | Lines group by artisan, quantity and remove still run through WooCommerce. | Server-render a grouped cart template that posts to WooCommerce endpoints. Feature at risk: optimistic quantity. |
| 5 | Checkout extension | Add step presentation, reservation timer and gift options through supported inner-block and slot-fill points. | No WooCommerce file edits; payment still works; errors reach one summary. | Single-page checkout with section headings. Feature at risk: three-step presentation. |
| 6 | Order-confirmation customisation | Add pd/order-groups to the confirmation template. | Groups show artisans, works and timeline for the buyer only. | Own confirmation template for the order-received endpoint. |
| 7 | Normalised review thread | Repository over comments, pd_artisan_reviews and pd_review_index; thread block; sort, filter, page, deep link. | Both scopes list, sort and page correctly; deep link opens the right page and highlights; rating cache matches. | Build the index with a scheduled rebuild if hooks miss events. |
| 8 | Interactivity Router navigation | Router regions for pd-main and nested regions; prefetch on intent. | Navigation swaps regions; shell DOM nodes are the same objects before and after. | Plain full-page navigation with the shared-element effect off. |
| 9 | Back and Forward restoration | Session snapshots of scroll, filters, focus id. | Back restores filters, scroll and focus on the triggering control, 20 of 20 runs. | Rely on the browser history scroll restoration plus URL state. |
| 10 | Shared-element transitions | View-transition snapshots across a router swap, source and destination by data-pd-transition. | Plate grows into the gallery; no real DOM node moves; works with and without the browser API. | Crossfade of the region. Feature at risk: grow and dock motion. |
| 11 | Server-rendered state hydration | wp_interactivity_state and data-wp-context for every store; no fetch on load. | First paint has the state; no request after load for seeded data. | Inline JSON with the same schema, validated by test. |
| 12 | Reduced-motion fallback | Same journeys with prefers-reduced-motion. | No movement; same focus, URL, announcements; destination outlined. | Not applicable. Must pass. |
| 13 | No sensitive data in public state | Scan page source, Interactivity state and public REST for private fields on the sample site. | Zero matches for addresses, phones, notes, payout or verification data. | Not applicable. Must pass. |
| 14 | Cross-block shared state | Seed every store once through the PHP state provider; place several blocks that mirror bag count, follow state, unreplied count and PIN; navigate with the Router; add, remove, follow and reply with a forced failure. | All mirrors agree with their owner after every step, including after Router navigation and rollback; no key is seeded twice. | Fall back to a single seeded state object per page and full reseed on each navigation. Feature at risk: optimistic updates across blocks. |

### Milestones

Estimates are planning figures for a small team (one WordPress back end, one front end, part-time design) and will be reset after M−1.

| Milestone | Scope | Contents | Estimate |
|---|---|---|---|
| M−1 | Feasibility and extension spikes | 13 spikes above; pass or fail recorded; architecture is not approved until they pass | 2 weeks |
| M0 | Foundation | Theme tokens, shell block, routed-main, pd/navigation, transition library, live regions, skeleton component, test harness | 3 weeks |
| M1 | Artisans | pd_artisan post type and ops table, directory, artisan card, masthead, facts, follow | 3 weeks |
| M2 | Works | Product collection variations, work card, filters, gallery, buy box, Buy now | 3 weeks |
| M3 | Reviews | Review repository, index, thread, form, replies, moderation | 3 weeks |
| M4 | Bag, checkout, orders | Cart groups, checkout flow, allocations, shipments, order groups | 4 weeks |
| M5 | Commissions | Request, quote, milestones, my commissions | 3 weeks |
| M6 | Awards and studio | Awards hall and pages, studio panels, home and search | 3 weeks |

Total about 24 weeks to a launch candidate of this front end, plus the artisan app.

## 15. Decisions and open questions

**Decided in the brief:** hybrid artisan model; normalised review repository; one order with allocations; 26 registered blocks; four state categories; shared-element contract; M−1 spikes.

**Decided by the owner on 2026-11-07:**

1. Two plugins (`pd-marketplace`, `pd-blocks`) plus the `pd-theme` block theme.
2. BUY-09 label is **Made on order** (beside "Ready to ship" and "Commission").
3. UI-05 is unchanged: the shell stays paper and ink on every route. The maker accent is not extended to the shell rule.
4. One tax invoice per order, not per allocation (CHK-08). `invoice_no` is not on the allocation. The tax adviser still has to confirm that one invoice naming several sellers meets GST and TCS rules.
5. Bilingual content from day one: an English and a Devanagari field for every public text (DB-15).
6. Pro artisans may edit their story in a restricted block editor (BRD-13); other artisans use the form. The definition of "pro" is open.
7. The six-accent set (BRD-08) is final.

**Still open:**

1. Commission wording: SRS BRD-06 and AWD-07 still say "Request a custom piece" and "Commission a work". Everything built says **Commission now**.
2. What makes an artisan "pro" (by sales, tenure, verification or invitation).
3. Review index: confirm the `pd_review_index` read model and the 10,000 review keyset threshold.
4. Tax adviser check on the single invoice (see decision 4).

### Deferred or conditional until WooCommerce extensibility is proven (M−1)

| Feature | Depends on spike | If it fails |
|---|---|---|
| Cart grouped by artisan with optimistic quantity | 3, 4 | Server-rendered grouped cart, no optimistic quantity |
| Three-step checkout presentation and reservation timer inside the Checkout block | 5 | Single-page checkout with headings; timer in the header |
| Grouped order confirmation | 6 | Own confirmation template |
| Pagination and filters of works with Product Collection | 2 | Core query with our card and filters |
| Grow, dock and regroup motion across Router swaps | 8, 10 | Crossfade only |
| Directory sort by rating and taking orders | 1 | Denormalised meta kept by hooks |

## 16. Counts

| Item | v0.1 | v0.2 | v0.3 |
|---|---|---|---|
| Registered custom blocks | 108 in trees, about 89 after merging | 148 in trees, about 129 after merging | 26 |
| Internal PHP components | 0 | 0 | 36 |
| Variations | a few | a few | 9 |
| Stores | 9 | 9 | 9 |
