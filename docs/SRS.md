# Software Requirements Specification
## Priyadarshini: the artisan-as-brand marketplace

| | |
|---|---|
| Document | Software Requirements Specification (SRS) |
| Standard followed | ISO/IEC/IEEE 29148:2018 (SRS outline), with IEEE Std 830-1998 section names where they are better known |
| Version | 0.9 (draft for owner review) |
| Date | 2026-11-07 |
| Owner | BananaBlocks Solutions Pvt. Ltd. |
| Status | Draft. Items marked **TBD** need an owner decision. |

### Revision history

| Version | Date | Author | Change |
|---|---|---|---|
| 0.1 | 2026-11-06 | Claude for BananaBlocks | First draft from PROJECT.md, the design work, and the competitor analysis |
| 0.2 | 2026-11-06 | Claude for BananaBlocks | Reviews split into product reviews and artisan reviews, each with its own rating (3.2.9, 3.5) |
| 0.3 | 2026-11-06 | Claude for BananaBlocks | Platform set to WordPress + WooCommerce: 2.1.7, 2.4, 2.5, 3.5.3, 3.6.3, 3.7.4, Appendix D |
| 0.4 | 2026-11-06 | Claude for BananaBlocks | Front end built from Gutenberg blocks with the WordPress Interactivity API (PLT-10 to PLT-15, Appendix D) |
| 0.5 | 2026-11-06 | Claude for BananaBlocks | No third-party marketplace plugin: WooCommerce extended by our own plugin; artisan app forked from the official WooCommerce mobile app (3.6.4) |
| 0.6 | 2026-11-06 | Claude for BananaBlocks | Awards showcase: award pages listing winning artisans by year, leading to the artisan page and commissioning a work (3.2.3a) |
| 0.7 | 2026-11-06 | Claude for BananaBlocks | Two buying formats, Buy now and Commission now (3.2.7a); commission requests moved to Phase 1 |
| 0.8 | 2026-11-07 | Claude for BananaBlocks | Corrective architecture pass: hybrid artisan model (`pd_artisan` post type plus `pd_artisans` operational table); normalised review repository; one canonical WooCommerce order with artisan allocations instead of child orders; reduced custom-block inventory; continuity and interaction requirements (CNT); state model; security and privacy boundaries (SEC-08 to SEC-14); architecture spike M-1 |
| 0.9 | 2026-11-07 | Claude for BananaBlocks | Owner decisions recorded: two plugins plus theme; label "Made on order" (BUY-09); shell stays paper and ink (UI-05 unchanged); one tax invoice per order, not per allocation (CHK-08); Devanagari field for every public text from day one (DB-15); restricted block editor for pro artisans (BRD-13); six-accent set final (BRD-08). |

---

## Table of contents

1. [Introduction](#1-introduction)
   1.1 Purpose · 1.2 Scope · 1.3 Definitions, acronyms and abbreviations · 1.4 References · 1.5 Overview
2. [Overall description](#2-overall-description)
   2.1 Product perspective · 2.2 Product functions · 2.3 User characteristics · 2.4 Constraints · 2.5 Assumptions and dependencies · 2.6 Apportioning of requirements
3. [Specific requirements](#3-specific-requirements)
   3.1 External interface requirements · 3.2 Functional requirements · 3.3 Performance requirements · 3.4 Usability requirements · 3.5 Logical database requirements · 3.6 Design constraints · 3.7 Software system attributes · 3.8 Supporting information
4. [Verification](#4-verification)
5. [Appendices](#5-appendices)

### Requirement notation

Every requirement has an ID, a priority and a verification method.

- **ID format:** `AREA-NN`, for example `REV-04`. IDs are never reused.
- **Priority (MoSCoW):** **M** = Must (launch, MVP), **S** = Should (launch if possible, else first release after), **C** = Could (later phase), **W** = Won't for now (recorded so it is not forgotten).
- **Verification:** **T** = test, **I** = inspection, **D** = demonstration, **A** = analysis.
- "Shall" marks a binding requirement. "Should" marks a goal. "May" marks an option.

---

## 1. Introduction

### 1.1 Purpose

This document specifies the software requirements for **Priyadarshini**, an online marketplace where each artisan is presented as a brand in their own right. It is written for:

- the owner (BananaBlocks), to confirm the product is what they want before design and build go further;
- designers, to know which screens and states must exist;
- developers and testers, as the contract the system is built and checked against;
- partners (artisan collectives, NGOs, logistics and payment providers), to understand what the platform will and will not do.

### 1.2 Scope

**Product name:** Priyadarshini. Buyers use the website; artisans manage their shop in the **Priyadarshini Artisan app**, a fork of the official WooCommerce mobile app (Android first).

**What it does.** Priyadarshini lets buyers discover, follow and buy from individual artisans. Each artisan has a **brand page** with their face, name, village, craft lineage, awards and work. **Both the product and the artisan have their own reviews.** After delivery a buyer rates the piece (product review) and the artisan (artisan review) separately, so a piece has its own rating and the artisan builds a brand rating of their own. The artisan can reply to both in their own voice. The platform brand stays in the background, like the ground of a painting.

**Where.** First market is India, starting with crafts of **Bihar and the Mithila region**: Madhubani painting and textiles, Sikki grass, Sujani embroidery, Manjusha, Tikuli, Bhagalpuri (Tussar) silk, Bawan Buti, Khatwa applique. International shipping is a later phase.

**Why (benefits and goals).** The competitor analysis (see 1.4, [R3]) shows:

1. No Indian platform offers real reviews per artisan (only Etsy and NOVICA do, globally).
2. Artisans are hidden behind the platform brand on iTokri, Jaypore, Fabindia and Okhai.
3. Buyers cannot follow a favourite artisan anywhere in India.
4. Buyers doubt authenticity (printed look-alikes, power-loom "Bhagalpuri").
5. Bihar crafts other than Madhubani are barely online.
6. Commission is opaque to artisans.
7. Artisan tools are English only.
8. Custom orders happen off-platform on WhatsApp.

Priyadarshini's goals, each traceable to requirements in Section 3:

| Goal | Measure of success (first 12 months, **TBD** by owner) |
|---|---|
| G1. Make the artisan the brand | 100% of listings belong to a named, verified artisan with a brand page |
| G2. Build trust through product and artisan reviews | At least 30% of delivered orders leave a review; artisans reply to at least 50% |
| G3. Prove authenticity | Every listing has a maker's photo or making-of media; GI badge where applicable |
| G4. Pay artisans fairly and visibly | Artisan share shown on every payout; at least 50% of sale price to artisan |
| G5. Reach artisans in their language | Artisan onboarding and dashboard usable fully in Hindi and Maithili |
| G6. Bring custom work on-platform | Commission requests handled in-app with milestone payments |

**Out of scope for this release** (see 2.6 for phasing): workshops and classes, international shipping, a native buyer app, B2B bulk ordering, physical retail, the artisan's own logistics.

### 1.3 Definitions, acronyms and abbreviations

| Term | Meaning |
|---|---|
| **Artisan** | An individual maker who sells on Priyadarshini. The artisan is the brand. |
| **Collective** | A group of artisans (cooperative, SHG, NGO cluster) that may onboard and manage several artisans. Each member still has their own brand page. |
| **Brand page** | The artisan's storefront: identity, story, work, reviews, follow button. Also called *artisan page*. |
| **Haat** | A traditional village market. The UI metaphor: the app is an *illustrated artisan directory*, a digital haat. |
| **Piece** | A single listed item. A piece may be one-of-a-kind (stock 1) or made-to-order. |
| **Commission request** | A buyer's request for a custom piece, handled in-app with milestones. (Not to be confused with *platform commission*.) |
| **Platform commission / take rate** | The share of the sale price the platform keeps. |
| **Product review** | A buyer's rating and text about a piece: quality, match to photos and description. It counts toward that piece's rating. |
| **Artisan review** | A buyer's rating and text about the artisan as a maker and seller: craftsmanship, communication, packaging, keeping promises. It counts toward the artisan's rating. |
| **Artisan reply** | The artisan's public response to a review, shown in the Kalam handwriting font. |
| **Verified artisan** | An artisan whose identity and craft have been checked (see ONB-05). Shown with the neel verified badge. |
| **GI** | Geographical Indication tag under the Geographical Indications of Goods Act, 1999. |
| **Splash** | The loading sequence where a Madhubani motif draws in, then moves into the header to become the logo. |
| **Kagaz** | Handmade paper; the background texture and the base colour token. |
| Admin | Priyadarshini staff who moderate, verify and support. |
| COD | Cash on delivery. |
| DPDP Act | Digital Personal Data Protection Act, 2023 (India). |
| GST / GSTIN | Goods and Services Tax / its registration number. |
| KYC | Know your customer (identity verification). |
| MVP | Minimum viable product (launch scope). |
| PWA | Progressive web app. |
| SHG | Self-help group. |
| TCS | Tax collected at source by e-commerce operators under GST section 52. |
| UPI | Unified Payments Interface. |
| WCAG | Web Content Accessibility Guidelines. |

### 1.4 References

| Ref | Document |
|---|---|
| [R1] | `PROJECT.md`, Priyadarshini project brief, 2026-11-06 |
| [R2] | `design/colors-and-fonts.html` and published page https://claude.ai/artifact/MP7pLLKA1ccFjEeFfKkCGU (palette A "Pigment", fonts pairing 1) |
| [R3] | `Priyadarshini_Competitor_Analysis.xlsx`, Nov 2026 (competitors, feature matrix, Bihar craft prices, gaps) |
| [R4] | `design/design-system.html` v2 and published page https://claude.ai/artifact/1sneGSmrDyZwJTjbGgtves (awaiting owner review) |
| [R5] | ISO/IEC/IEEE 29148:2018, Systems and software engineering: Life cycle processes: Requirements engineering |
| [R6] | IEEE Std 830-1998, Recommended Practice for Software Requirements Specifications |
| [R7] | Consumer Protection (E-Commerce) Rules, 2020 (India) |
| [R8] | Digital Personal Data Protection Act, 2023 (India) and its Rules |
| [R9] | Information Technology Act, 2000 and IT (Intermediary Guidelines and Digital Media Ethics Code) Rules, 2021 |
| [R10] | CGST Act, 2017, section 52 (TCS by e-commerce operators) |
| [R11] | WCAG 2.2, W3C Recommendation |
| [R12] | Geographical Indications of Goods (Registration and Protection) Act, 1999 |

Legal references are listed for the team's awareness. A lawyer should confirm what applies before launch.

### 1.5 Overview

Section 2 describes the product in context: who uses it, the main functions, and the constraints. Section 3 lists the specific, testable requirements. Section 4 says how each kind of requirement will be verified. Section 5 holds the traceability matrix, open issues and the screen list that feeds the blueprint.

---

## 2. Overall description

### 2.1 Product perspective

Priyadarshini is a new, self-contained product. It is not a replacement for an existing system. It sits between three groups and several outside services:

```
                 ┌───────────────────────────────┐
  Buyers ───────►│         Priyadarshini          │◄─────── Artisans / Collectives
  (website)      │  Buyer site · Artisan app      │         (Android app; iOS later)
                 │  Admin console · API · DB      │
                 └──┬──────┬──────┬──────┬──────┬─┘
                    │      │      │      │      │
              Payments  Shipping  SMS/   Media   Maps/
              (UPI,     (aggreg-  WhatsApp storage PIN
              cards,    ator,     OTP &   & CDN   code
              COD,      India     alerts          lookup
              payouts)  Post)
```

#### 2.1.1 System interfaces
Payment gateway with split settlement or payouts, shipping aggregator, SMS and WhatsApp Business messaging, email, object storage and CDN for images and video, PIN-code and address lookup, analytics. Details in 3.1.3.

#### 2.1.2 User interfaces
Three interfaces share one design system [R4]:
- **Buyer web app**: responsive, mobile first; the digital haat.
- **Artisan app**: a native mobile app forked from the official WooCommerce app and extended so artisans can manage their shop easily: simplified, Hindi and Maithili first, voice-assisted (see 3.6.4).
- **Admin console**: verification, moderation, orders, payouts, content.

#### 2.1.3 Hardware interfaces
None specific. The artisan app must work on low-cost Android phones (2 GB RAM, Android 9+) with a camera and microphone.

#### 2.1.4 Software interfaces
Modern browsers (see 3.6.3). Outside services listed in 3.1.3.

#### 2.1.4a Technology platform (owner decision, 2026-11-06)
Priyadarshini shall be built on **WordPress** with **WooCommerce**. No third-party multi-vendor plugin is used: WooCommerce is extended into an artisan marketplace by **our own custom plugin** (`pd-marketplace`), which adds artisans, artisan order allocations, commissions and payouts, artisan reviews, follow, commission requests, collectives, and the REST API used by the artisan app. The theme is a **custom block theme**. Artisans manage their shop in a **mobile app forked from the official WooCommerce app** (3.6.4). The front end shall be built from **Gutenberg blocks** (core, WooCommerce and our own custom blocks), and interactive behaviour shall use the **WordPress Interactivity API**. Appendix D maps every requirement area to core, plugin or custom code.

#### 2.1.5 Communications interfaces
HTTPS (TLS 1.2+) for all traffic. Push notifications through the browser (web push) and, later, Firebase Cloud Messaging. SMS and WhatsApp for OTPs and order updates.

#### 2.1.6 Memory and operations
The artisan app shall store the artisan's drafts and recent orders on the phone so an artisan can prepare a listing with no signal and upload when back online.

### 2.2 Product functions

At a high level, the system shall let:

**Buyers**
- discover artisans and pieces by craft, region, price, occasion and story;
- open an artisan's brand page and follow the artisan;
- buy pieces (cart, checkout, UPI, card, net banking, COD where allowed);
- request a custom piece from an artisan (commission request);
- track orders, return or cancel within policy;
- review the artisan after delivery and read the artisan's reply;
- receive "new work from artisans you follow" updates.

**Artisans**
- onboard in Hindi, Maithili or English, with help from a collective or field agent;
- build and edit their brand page (story, lineage, awards, village, photos, video);
- list pieces with photos, voice description, price, stock, made-to-order time;
- receive and fulfil orders, print labels, hand over to courier;
- accept, quote and deliver commission requests in milestones;
- reply to reviews;
- see earnings, the commission split and payouts.

**Collectives**
- onboard and manage several artisans and act on their behalf with their consent.

**Admins**
- verify artisans and pieces; manage GI and authenticity badges;
- moderate reviews, replies and listings;
- manage orders, disputes, refunds and payouts;
- curate the home page and collections.

### 2.3 User characteristics

| Class | Description | Tech comfort | Language | Key needs |
|---|---|---|---|---|
| **Buyer: urban gift buyer** | 22–45, metro India, buys gifts and home decor | High | English, Hindi | Trust, speed, story, easy returns |
| **Buyer: art collector** | Buys paintings ₹5k–₹1L+ | High | English | Authenticity proof, artist credentials, safe shipping |
| **Buyer: diaspora** (later phase) | NRI buyers in US, UK, Gulf | High | English | International shipping, prices in their currency |
| **Artisan: master** | Award-winning, may already sell elsewhere | Low–medium | Maithili, Hindi | Recognition, fair pay, control of their brand |
| **Artisan: emerging** | Younger, smartphone user, WhatsApp-fluent | Medium | Hindi, Maithili, some English | Reach, simple listing, quick payouts |
| **Artisan: low literacy** | Skilled maker, limited reading | Low | Spoken Maithili, Bhojpuri, Angika | Voice and icons, help from collective |
| **Collective manager** | Runs an SHG, cooperative or NGO cluster | Medium | Hindi, English | Manage many artisans, bulk listing, reports |
| **Admin** | Priyadarshini staff | High | English, Hindi | Fast queues, audit trail |

### 2.4 Constraints

1. **Regulatory (India):** Consumer Protection (E-Commerce) Rules 2020 [R7] (seller details shown on listings, grievance officer, country of origin, no fake reviews, clear return and refund policy); DPDP Act 2023 [R8]; IT Act and Intermediary Rules [R9]; GST and TCS [R10].
2. **Brand and design:** palette A "Pigment", Yatra One, Mukta and Kalam fonts, handmade-paper background, Madhubani splash [R1, R2]. Fonts must render Devanagari.
3. **Connectivity:** many artisans are on 3G or patchy 4G in rural Bihar.
4. **Devices:** low-cost Android phones for artisans.
5. **Language:** Hindi and Maithili at launch for artisan tools; Maithili is written in Devanagari for this product.
6. **Budget and team:** small team; prefer managed services over custom infrastructure.
7. **Platform:** WordPress (current stable, PHP 8.2+) and WooCommerce with High-Performance Order Storage (HPOS) on; artisans (a `pd_artisan` post type plus an operational table), order allocations, commissions and payouts are implemented in our own `pd-marketplace` plugin on top of WooCommerce data. Third-party plugins are kept to a vetted list (see PLT-05).
8. **Motif art:** the final splash and logo art must come from a Madhubani artist or illustrator; the fish is a placeholder [R1].

### 2.5 Assumptions and dependencies

| ID | Assumption or dependency | Risk if wrong |
|---|---|---|
| AS-01 | A payment gateway with marketplace split settlement or payouts (for example Razorpay Route or Cashfree Easy Split) is available to BananaBlocks | Manual payouts; slower, error prone |
| AS-02 | A shipping aggregator (for example Shiprocket) or India Post covers the artisans' PIN codes | Must arrange pickup via collectives |
| AS-03 | Field partners (collectives, NGOs) help onboard the first artisans | Slower supply growth |
| AS-04 | Platform operates as a marketplace (artisan is seller of record), not inventory | Different tax and liability model |
| AS-05 | Small artisans below the GST threshold can sell through the marketplace under the current composition or exemption rules (**TBD**: confirm with tax adviser) | Some artisans cannot list until registered |
| AS-06 | WhatsApp Business API is approved for OTP and order messages | Fall back to SMS |
| AS-08 | The official WooCommerce mobile app source (open source, GPL) can be forked, rebranded and pointed at our artisan-scoped API without rewriting its core | More app work, or build the app from scratch |
| AS-10 | Razorpay Route (or an equivalent) can split each payment to linked artisan accounts from our own plugin | Manual payouts |
| AS-09 | WooCommerce product reviews (verified owner, star rating) meet the product-review needs with small customisation | Custom product review plugin |
| AS-07 | Speech-to-text for Hindi is good enough; Maithili quality is lower and needs human review | Voice listing may need more edits |

### 2.6 Apportioning of requirements (phasing)

| Phase | Contents |
|---|---|
| **Phase 1: MVP** (all **M** items) | Buyer web app, artisan brand pages, listing, cart and checkout (UPI, card, net banking), artisan reviews and replies, follow, Buy now and Commission now (commission requests with milestones), award pages, Priyadarshini Artisan app for Android (forked WooCommerce app, Hindi/Maithili), admin verification and moderation, payouts |
| **Phase 2** (most **S** items) | COD, voice listing, collective accounts, making-of video, "new from followed" feed, gift options, Hindi buyer UI |
| **Phase 3** (**C** items) | Workshops and classes, international shipping and currencies, iOS artisan app, recommendations, loyalty |
| Not planned (**W**) | Auctions, NFTs, B2B wholesale portal, platform-owned inventory |

---

## 3. Specific requirements

### 3.1 External interface requirements

#### 3.1.1 User interfaces

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| UI-01 | The system shall use the design tokens in [R1] (colours, type scale, spacing 4–64, radius 6px, 1.5px/2px outlines, hard 3px kajal shadow, no blur). | M | I |
| UI-02 | The page background shall be the kagaz handmade-paper texture; surfaces shall use `--card`. The texture shall be a compressed image under 40 KB or a CSS/SVG noise so it does not slow the page. | M | I, T |
| UI-03 | **Colour roles shall be fixed:** sindoor only for the primary action and errors; neel for links, navigation and the verified badge; haldi only for stars and ratings; patta for success. | M | I |
| UI-04 | Each screen shall have at most one primary (sindoor) button. | M | I |
| UI-05 | **Colour belongs to the artisan:** the app shell stays paper and ink on every route (confirmed by the owner 2026-11-07; the shell does not take the maker's accent); each brand page takes one accent (from an allowed set) for its top band and avatar ring. | M | I |
| UI-06 | Haldi, gulabi and kusum shall never be used for text smaller than 24px or as the only carrier of meaning. | M | I |
| UI-07 | Headings and artisan brand names shall use Yatra One; body and UI shall use Mukta (400/600/700); artisan quotes, signatures and replies shall use Kalam. All three shall render Devanagari. | M | I |
| UI-08 | **Splash:** on first load of a session, a Madhubani motif shall draw itself on paper, then move into the header and settle as the logo. The splash shall last at most 1.8 s, be skippable by tap or key, run only once per session, and be replaced by a static logo when the user has *reduced motion* set. | M | D, T |
| UI-09 | The splash shall never block content: if the page is ready first, the splash shall finish its move to the header immediately. | M | T |
| UI-10 | Layout shall follow the "digital haat / illustrated artisan directory" direction in [R4]: artisan-first cards (face, name, village, craft, rating) before product grids. | M | I |
| UI-11 | Breakpoints shall be 400, 760 and 1100 px; the buyer app shall be usable from 320 px wide. | M | T |
| UI-12 | Every screen shall have designed empty, loading (skeleton) and error states. | M | I |
| UI-13 | The artisan app shall use large icons with text labels, at most one task per screen, and a "speak instead" option on text fields. | S | D |

#### 3.1.2 Hardware interfaces

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| HW-01 | The artisan app shall use the device camera for piece photos and identity documents, and the microphone for voice input, asking permission at the moment of use. | M | D |
| HW-02 | The artisan app shall support printing shipping labels to a regular printer as PDF, and sharing the label to a nearby print shop over WhatsApp. | S | D |

#### 3.1.3 Software interfaces

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| SW-01 | **Payments:** the system shall integrate a PCI-DSS compliant gateway supporting UPI (intent and collect), cards, net banking and wallets. | M | T |
| SW-02 | **Split settlement:** the system shall route each order's artisan share to the artisan's verified bank account or UPI ID and keep the platform commission, through the gateway's marketplace product. | M | T |
| SW-03 | **COD:** the system shall support cash on delivery through the shipping partner, with a per-order value cap set by admin. | S | T |
| SW-04 | **Shipping:** the system shall integrate a shipping aggregator API for rate check, pickup booking, label, AWB number and tracking webhooks. | M | T |
| SW-05 | **Messaging:** the system shall send OTPs and order updates by SMS and, where the user consents, WhatsApp Business. | M | T |
| SW-06 | **Email:** transactional email for buyers (order, invoice, review request). | M | T |
| SW-07 | **Media:** images and video shall be stored in object storage and served through a CDN with automatic resizing and WebP/AVIF conversion. | M | T |
| SW-08 | **Speech-to-text:** the artisan app shall use a speech-to-text service supporting Hindi (and Maithili where available) for voice listing and replies. | S | D |
| SW-09 | **Translation:** the system may offer machine translation of artisan stories and replies between Hindi, Maithili and English, always labelled "translated" with the original one tap away. | S | D |
| SW-10 | **PIN code lookup:** checkout shall look up city and state from PIN code and check serviceability. | M | T |
| SW-11 | **Analytics:** privacy-respecting analytics with consent, no third-party ad trackers at launch. | M | I |

#### 3.1.4 Communications interfaces

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| NET-01 | All traffic shall use HTTPS with TLS 1.2 or higher and HSTS. | M | T |
| NET-02 | Webhooks from payment and shipping providers shall be verified by signature and processed idempotently. | M | T |
| NET-03 | The system shall support web push notifications for buyers (followed artisans, order status) and artisans (new order, new review), opt-in only. | S | T |

### 3.2 Functional requirements

Functional requirements are grouped by feature. Each group lists the purpose, then the requirements.

#### 3.2.1 Accounts and authentication (ACC)

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| ACC-01 | Buyers shall be able to browse, search and view all public pages without an account. | M | T |
| ACC-02 | Users shall sign up and sign in with mobile number and OTP. Email and password, Google sign-in may be offered for buyers. | M | T |
| ACC-03 | An account shall have one or more roles: buyer, artisan, collective manager, admin. One person may be both a buyer and an artisan. | M | T |
| ACC-04 | Guest checkout shall be allowed with mobile OTP; the order then attaches to that mobile number's account. | S | T |
| ACC-05 | Admin accounts shall require two-factor authentication. | M | T |
| ACC-06 | Users shall be able to download their data and delete their account (see 3.7.3). | M | T |
| ACC-07 | The interface language (English, Hindi, Maithili) shall be chosen at first launch and changeable at any time. | M | T |

#### 3.2.2 Artisan onboarding and verification (ONB)

Purpose: get real makers on the platform, in their own language, and prove they are who they say.

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| ONB-01 | An artisan shall be able to register themselves, or be registered by a collective manager or field agent with the artisan's recorded consent (OTP to the artisan's own phone). | M | T |
| ONB-02 | Onboarding shall be a step-by-step flow with save-and-continue, in Hindi, Maithili or English, with an audio explanation on each step. | M | D |
| ONB-03 | Onboarding shall collect: name (and name in Devanagari), display name, photo, craft(s), village/district, languages spoken, bank account or UPI ID, PAN (optional below thresholds, **TBD**), GSTIN if registered, government artisan card (Pehchan) number if any. | M | T |
| ONB-04 | The system shall verify the bank account or UPI ID by penny-drop or gateway verification before the first payout. | M | T |
| ONB-05 | **Artisan verification:** an admin shall verify identity (KYC document or Pehchan card) and craft (a video of the artisan at work, or a visit or reference from a partner collective). Only verified artisans get the verified badge. | M | T |
| ONB-06 | An unverified artisan may build their page and draft listings but shall not be visible to buyers until verified. | M | T |
| ONB-07 | The artisan shall accept the seller agreement, which shows the **commission split in plain words and numbers** before signing. | M | I |
| ONB-08 | Admin shall see a verification queue with status (new, in review, needs info, verified, rejected) and reasons sent to the artisan in their language. | M | T |

#### 3.2.3 Artisan brand page (BRD)

Purpose: the heart of the product. The artisan is the brand.

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| BRD-01 | Every verified artisan shall have a public brand page at a readable URL, for example `/a/{artisan-handle}`. | M | T |
| BRD-02 | The brand page shall show: name in Latin and Devanagari, portrait, accent band, craft(s), village and district, years of practice, craft lineage ("learned from"), awards, languages, verified badge, follower count, overall artisan rating and review count. | M | T |
| BRD-03 | The brand page shall have a **story** section: text, a pull quote in the artisan's hand (Kalam), photos, and optionally a short video. | M | T |
| BRD-04 | The brand page shall list the artisan's pieces, with filters (available, made-to-order, sold) and sort. | M | T |
| BRD-05 | The brand page shall show the artisan's **reviews** section (see REV). | M | T |
| BRD-06 | The brand page shall have a **Follow** button and, when commission requests are enabled for that artisan, a **Request a custom piece** button. | M / S | T |
| BRD-07 | Sold pieces shall stay visible as a portfolio, marked "sold", so the page grows over time. | S | T |
| BRD-08 | The artisan shall choose one accent colour for their page from the allowed set (gulabi, kusum, neel, patta, sindoor, haldi), with a preview. The set is final (owner decision 2026-11-07). | M | T |
| BRD-09 | Artisan stories written in Hindi or Maithili shall be shown in the original, with an optional labelled translation. | S | T |
| BRD-10 | The brand page shall have a share link and an Open Graph card (portrait, name, craft) for WhatsApp and social. | M | T |
| BRD-11 | A collective may have its own page that links to each member artisan's brand page; the artisan stays the primary brand. | S | T |
| BRD-12 | Product listings, cards and order documents shall always show the artisan's name and link back to the brand page. | M | I |
| BRD-13 | **Story editing for pro artisans.** A pro artisan may edit the story of their own `pd_artisan` post in a restricted block editor on the web: paragraph, heading, image, gallery, quote, list and video only; no templates, patterns, raw HTML or custom blocks; changes are saved as a revision and shown after moderation if the artisan has no earlier approved edit. Other artisans edit their story through the brand page form in the app or on the web. The criteria for "pro" are TBD (Appendix B). | S | T |

#### 3.2.3a Awards and recognition (AWD)

Purpose: many of our artisans are award winners (Padma Shri, Shilp Guru, National Award for master craftspersons, state awards). Awards are proof of mastery and a strong reason to buy or commission. Each award gets its own page listing the artisans who won it, year by year, leading to the artisan's page and to commissioning a work.

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| AWD-01 | Admins shall maintain an **award register**: award name (English, Hindi, Maithili), awarding body, level (international, national, state, district, other), the craft(s) it covers, a short description, and an emblem image where use is permitted. | M | T |
| AWD-02 | The register shall be seeded at launch with at least: Padma Vibhushan, Padma Bhushan, Padma Shri, Shilp Guru, National Award for Master Craftspersons (handicrafts), Sant Kabir Award and National Handloom Award (weavers), Nari Shakti Puraskar, Bihar State Award for handicrafts, and UNESCO / World Crafts Council recognitions (**TBD**: final list with partners). | M | I |
| AWD-03 | An artisan (or their collective) shall add an award won, with the year, category, citation (optional), and proof (certificate photo, gazette or official list link). The award goes live only after admin verification. | M | T |
| AWD-04 | Verified awards shall show on the artisan's brand page in an **Awards** section (newest first, highest level first), and the top award as a badge on the artisan card, search results and piece pages ("Padma Shri 2017"). | M | T |
| AWD-05 | Each award shall have a public **award page** (for example `/awards/padma-shri`) showing what the award is, then the artisans who won it **grouped by year**, newest year first. Each artisan entry shows portrait, name, craft, village, year and category, artisan rating and a link to the brand page. | M | T |
| AWD-06 | Award pages shall be filterable by craft and district, and switchable between "by year" (default) and "by craft". | S | T |
| AWD-07 | Every artisan entry on an award page, and the Awards section of the brand page, shall have a **Commission a work** button that opens a commission request to that artisan (CR), when the artisan accepts commissions. If they do not, the button shall be "See available work". | M | T |
| AWD-08 | An **Awards hall** page (`/awards`) shall list all awards, highest level first, with the number of Priyadarshini artisans who hold each, and link to each award page. | M | T |
| AWD-09 | The home page (haat) shall have an "Award-winning masters" slot curated by admin. | S | D |
| AWD-10 | Search shall find artisans by award ("padma shri madhubani", "shilp guru") and offer "award winners" as a filter on directory, craft and search pages. | S | T |
| AWD-11 | Award claims shall never be shown unverified; a revoked or disproved award shall be removed and logged (ADM-03). Awards held by an artisan's family members or teacher may be shown only in the lineage text, never as a badge. | M | T |
| AWD-12 | Award pages shall have share links and Open Graph cards, and structured data (schema.org `Person` with `award`) for search engines. | S | T |

#### 3.2.4 Catalogue and listing (CAT)

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| CAT-01 | An artisan shall create a listing with: buying format (BUY-01), title, photos (minimum 3, maximum 12), description, craft type, materials, size, weight, price, stock or made-to-order lead time, care instructions, and whether it is one-of-a-kind. | M | T |
| CAT-02 | At least one listing photo or video shall show the maker or the making ("made-by" proof). | S | T |
| CAT-03 | The artisan shall be able to dictate the title and description by voice; the system transcribes it for the artisan to confirm. | S | D |
| CAT-04 | The system shall guide photo capture (plain background, daylight, front, back, detail, scale reference) with on-screen hints. | S | D |
| CAT-05 | The system shall suggest a price band for the craft and size from the reference data in [R3] (for example price per square inch for paintings), without forcing it. | C | D |
| CAT-06 | Every listing shall show the legally required seller details (artisan name, address region, country of origin, grievance contact) [R7]. | M | I |
| CAT-07 | Pieces of a GI-registered craft made in the GI region by a verified artisan shall carry a **GI badge** after admin approval. | S | T |
| CAT-08 | Each sold piece shall ship with a printable **"Made by" certificate** with the artisan's name, photo, signature and a QR code to the brand page. | S | D |
| CAT-09 | New listings and edits to price or photos shall pass admin review before going live in the first 90 days of an artisan's account; after that, only flagged listings are reviewed. | M | T |
| CAT-10 | Listings shall support variants only where meaningful (for example size of a print, colour of a dupatta). One-of-a-kind pieces have no variants. | S | T |
| CAT-11 | Categories at launch shall include Madhubani painting, Madhubani textiles, Madhubani decor, Sikki, Sujani, Manjusha, Tikuli, Bhagalpuri silk, Bawan Buti, Khatwa applique, papier-mâché and lac. | M | I |
| CAT-12 | A collective manager may bulk-upload listings for member artisans from a spreadsheet template; each listing is still assigned to one artisan. | S | T |

#### 3.2.5 Discovery: browse, search and home (DIS)

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| DIS-01 | The home page shall present the **haat**: featured artisans first, then crafts, then collections and pieces. | M | I |
| DIS-02 | Buyers shall browse by craft, region (district), price band, occasion and availability (in stock, made-to-order). | M | T |
| DIS-03 | Search shall return **artisans and pieces** in separate, clearly labelled groups, and work in English, Hindi and transliterated Hindi (for example "madhubani", "मधुबनी"). | M | T |
| DIS-04 | Search shall tolerate common misspellings (for example "madubani", "sikky"). | S | T |
| DIS-05 | An artisan directory page shall list all artisans as illustrated cards, filterable by craft and district. | M | T |
| DIS-06 | Craft pages (for example "Sikki grass craft") shall explain the craft, its region and GI status, and list its artisans. | S | I |
| DIS-07 | Admins shall curate collections (for example "Chhath gifts", "Under ₹1,000") and home page slots. | M | D |
| DIS-08 | Logged-in buyers who follow artisans shall see a **"New from artisans you follow"** feed. | S | T |
| DIS-09 | Recommendations ("artisans like this") may be shown based on craft and buyer behaviour. | C | D |

#### 3.2.6 Follow and notifications (FOL)

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| FOL-01 | A buyer shall be able to follow and unfollow an artisan from the brand page, artisan cards and order pages. | M | T |
| FOL-02 | A buyer shall be able to save (wishlist) a piece. | M | T |
| FOL-03 | Followers shall be notified (in-app, and email or push if opted in) when a followed artisan lists new work, at most once per day per artisan. | S | T |
| FOL-04 | Artisans shall see their follower count and a monthly summary, but not followers' personal details. | M | T |
| FOL-05 | Buyers shall manage notification preferences per channel and type. | M | T |

#### 3.2.7 Cart, checkout and payment (CHK)

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| CHK-01 | A cart may hold pieces from several artisans; the cart shall group lines by artisan and show each artisan's shipping and dispatch time. | M | T |
| CHK-02 | One-of-a-kind pieces shall be reserved for 15 minutes once checkout starts, and released if payment fails or times out. | M | T |
| CHK-03 | Checkout shall collect address (with PIN-code lookup), contact, delivery option and payment method in at most three steps. | M | T |
| CHK-04 | Prices shall be shown inclusive of GST, with shipping shown before payment. No hidden charges at the last step [R7]. | M | I |
| CHK-05 | Payment methods: UPI, cards, net banking (M); wallets (S); COD with cap (S); EMI for high-value art (C). | M/S/C | T |
| CHK-06 | A buyer may add a gift note and choose gift wrapping where the artisan offers it. | S | T |
| CHK-07 | On success the system shall keep one WooCommerce order under one buyer payment and create one **order allocation** per artisan on it (see DB-08 to DB-10), and send confirmation by SMS/WhatsApp and email. | M | T |
| CHK-08 | The system shall issue **one tax invoice per order** (owner decision 2026-11-07), numbered from one platform series, issued on behalf of the artisan(s) as seller of record, with lines grouped by artisan and each artisan named, and, where applicable, handle TCS reporting [R10]. Whether one invoice satisfies GST rules for several sellers is for the tax adviser to confirm (Appendix B 16). | M | I |
| CHK-09 | Coupons and platform-funded discounts shall never reduce the artisan's share. | S | T |

#### 3.2.7a Buying formats: Buy now and Commission now (BUY)

Purpose: a handmade piece can be bought two ways. **Buy now** gets the finished piece that is in the photos. **Commission now** asks the artisan to make a new work for you, in their style, starting from that piece or from scratch. Each has its own flow, price display and order type.

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| BUY-01 | Each listing shall have a **buying format** set by the artisan: *Buy now only* (ready piece), *Buy now + Commission now*, or *Commission now only* (showcase pieces, sold one-of-a-kinds, large works like murals). | M | T |
| BUY-02 | The piece page buy box shall show **Buy now** and **Commission now** side by side when both apply. To keep one primary action per screen (UI-04), Buy now is the primary sindoor button when the piece is in stock, and Commission now is the outlined secondary; when the piece is sold or commission-only, Commission now becomes primary. | M | I |
| BUY-03 | **Buy now** shall show price (GST inclusive), stock ("1 of 1, only this piece"), dispatch time and delivery estimate for the buyer's PIN code. It shall take the buyer straight to checkout for that piece; "Add to cart" is offered as a secondary text link for buying several pieces. | M | T |
| BUY-04 | **Commission now** shall show "from ₹X", the artisan's typical lead time, the sizes or options the artisan offers, and a short "how commissioning works" (request, quote, advance, progress photos, approve, balance, delivery). | M | T |
| BUY-05 | Commission now from a piece shall open a commission request (CR) pre-filled with that piece as the reference (photo, size, craft), which the buyer can change (size, colours, theme, deadline, budget, note, extra reference images). | M | T |
| BUY-06 | Commission now shall also be available without a piece, from the artisan's brand page, award pages (AWD-07) and artisan cards, opening a blank request to that artisan. | M | T |
| BUY-07 | An artisan may set **commission presets** on a listing (for example "same design, 12×18 in, ₹4,500, 3 weeks"). Choosing a preset makes the quote instant, and the buyer pays the advance straight away without waiting for a quote. | S | T |
| BUY-08 | Commissions shall not go through the cart. Each commission is its own order type, paid in milestones (CR-04), shown in a separate "My commissions" list with stage and progress photos. | M | T |
| BUY-09 | Listing cards in grids shall show a small label for the formats available ("Ready to ship", "Made on order", "Commission", or a pair of them), and listing filters shall include buying format. A piece made after the order is placed is labelled "Made on order" (owner decision 2026-11-07). | M | T |
| BUY-10 | When an artisan is away (DSH-07) or has reached their open-commission limit (set by the artisan), Commission now shall show the next available date and allow joining a waiting list instead. | S | T |
| BUY-11 | Return rules shall differ by format and be shown in the buy box: Buy now follows ORD-07; commissioned works are returnable only for damage or not matching the approved final photo. | M | I |

#### 3.2.8 Orders, fulfilment and returns (ORD)

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| ORD-01 | The artisan shall be notified of a new order by SMS/WhatsApp and in-app within 1 minute of payment. | M | T |
| ORD-02 | The artisan shall accept the order within 24 hours (configurable); unaccepted orders shall be escalated to the collective or admin, then auto-cancelled and refunded after 48 hours. | M | T |
| ORD-03 | The artisan shall mark the order packed, book pickup (or drop at a partner point), and print or share the label. | M | T |
| ORD-04 | The system shall show packing guidance per craft (for example framed art, silk sarees). | S | I |
| ORD-05 | Order statuses: placed, accepted, in making (made-to-order), packed, shipped, out for delivery, delivered, cancelled, return requested, returned, refunded. Buyer and artisan see the same status. | M | T |
| ORD-06 | Buyers may cancel before the order is shipped (or before making starts, for made-to-order). | M | T |
| ORD-07 | Return policy shall be shown on every listing. Default: 7-day return for damage or not-as-described, with photo evidence; one-of-a-kind and custom pieces only for damage or not-as-described (**TBD** owner policy). | M | I |
| ORD-08 | Refunds shall go back to the original payment method (COD to UPI/bank) within the time limits of [R7]. | M | T |
| ORD-09 | Disputes shall be handled in an admin queue with chat between buyer, artisan and admin, and an audit trail. | M | T |
| ORD-10 | Buyers shall have a grievance contact and ticket number, acknowledged within 48 hours [R7]. | M | I |

#### 3.2.9 Product and artisan reviews (REV)

Purpose: the main differentiator. Reviews exist at two levels, kept separate:

- **Product review**: about the piece. Shown on the piece page and product cards. Builds the **product rating**.
- **Artisan review**: about the maker. Shown on the brand page, artisan cards and search. Builds the **artisan rating**, which no Indian competitor offers [R3].

Both are written in one short flow after delivery, so the buyer is asked once.

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| REV-01 | Only a buyer with a **delivered** order shall be able to review; both reviews are marked "verified purchase". | M | T |
| REV-02 | The review request shall be sent 2 days after delivery, with one reminder after 7 days. The review window closes 60 days after delivery. | M | T |
| REV-03 | **Product review:** one per order line. It shall have a rating of the piece (1–5 haldi stars), optional sub-ratings (quality, as described, value), text, and up to 5 photos. | M | T |
| REV-04 | **Artisan review:** one per order allocation (one per artisan per order, however many pieces were bought). It shall have a rating of the artisan (1–5 haldi stars), optional sub-ratings (craftsmanship, communication, packaging, on-time dispatch), and text. | M | T |
| REV-05 | The review flow shall show the two parts as separate steps on one screen ("Rate the piece", then "Rate the artisan"). The buyer may submit either part alone. | M | D |
| REV-06 | **Product rating** shall be the mean of that piece's product reviews, shown on the piece page and product cards with the review count. | M | T |
| REV-07 | **Artisan rating** shall be the mean of that artisan's artisan reviews only (product ratings do not feed it), shown to one decimal with the count on the brand page, artisan cards, search results, and as a line on every piece page ("by Sita Devi ★ 4.8"). | M | T |
| REV-08 | A piece or artisan with fewer than 3 reviews shall show "New" instead of a number. | M | T |
| REV-09 | Pieces that the artisan makes again (not one-of-a-kind) shall keep their product reviews across repeat sales. For one-of-a-kind pieces, product reviews stay visible on the sold piece and in the artisan's portfolio. | M | T |
| REV-10 | The brand page shall show artisan reviews first, with a tab for all product reviews of the artisan's pieces. The piece page shall show that piece's product reviews first, then a short block of the artisan's latest artisan reviews linking to the brand page. | M | T |
| REV-11 | The artisan shall be able to post **one public reply** to each product review and each artisan review, editable for 7 days, shown in Kalam under the review with the artisan's portrait. | M | T |
| REV-12 | Artisans shall be able to reply by typing or by voice; replies in Hindi or Maithili shall be shown in the original, with an optional labelled translation. | S | D |
| REV-13 | A buyer may edit each review once, within 30 days, and may update it after an artisan's reply. Edits are marked "edited". | S | T |
| REV-14 | Artisans shall **not** be able to delete, hide or reorder reviews. They may report a review for moderation. | M | T |
| REV-15 | Review sort: most recent (default), highest, lowest, with photos. Product reviews on the brand page can be filtered by piece or craft. | M | T |
| REV-16 | Moderation: reviews shall be checked automatically for abuse, personal data and spam, then queued for admin if flagged. Removal reasons are limited to the published review policy, and the buyer is told why. | M | T |
| REV-17 | The system shall not offer incentives conditioned on positive reviews, and shall not suppress negative reviews, as required by [R7]. Incentives for *any* review, if ever offered, shall be disclosed on the review. | M | I |
| REV-18 | Other buyers may mark a review "helpful". | C | T |
| REV-19 | The artisan dashboard shall show both ratings with trends, unreplied reviews, and a gentle prompt to reply. | S | D |
| REV-20 | Commission (custom) orders shall get an artisan review labelled "custom piece" and a product review on the custom piece. Workshop reviews (later phase) are artisan reviews labelled "workshop". | S | T |

#### 3.2.10 Custom pieces: commission requests (CR)

Purpose: bring WhatsApp-style custom orders onto the platform, with payment protection.

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| CR-00 | Commission requests are part of Phase 1, because Commission now (BUY) and award pages (AWD-07) depend on them. | M | I |
| CR-01 | Artisans shall choose whether they accept commission requests, and set minimum price and typical lead time. | M | T |
| CR-02 | A buyer shall send a request with: description, size, budget range, deadline, reference images. | M | T |
| CR-03 | The artisan shall accept with a quote (price, lead time, milestones), ask a question, or decline with a reason. | M | T |
| CR-04 | Milestones: default two (advance at start, balance on approval of the finished piece photo), up to four. The buyer pays each milestone in-app; the platform holds funds until the milestone is approved. | M | T |
| CR-05 | Buyer and artisan shall message inside the request thread, with image sharing and voice notes; phone numbers and outside payment links shall be masked to keep the work protected. | M | T |
| CR-06 | If the buyer does not respond to a milestone approval within 7 days, it shall auto-approve. | M | T |
| CR-07 | Cancellation rules (who keeps the advance) shall be shown before the buyer pays the advance. | M | I |

#### 3.2.11 Artisan dashboard (DSH)

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| DSH-01 | The artisan home shall show, in this order: new orders to act on, commission requests, unreplied reviews, earnings this month. | M | D |
| DSH-02 | Every artisan task shall be completable on a phone, in the artisan's language. | M | D |
| DSH-03 | The artisan shall see each sale with the **price, platform commission, gateway fee, shipping, tax and their share** in plain numbers. | M | T |
| DSH-04 | The artisan shall see payouts (scheduled, paid, failed) and download statements. | M | T |
| DSH-05 | Simple insights: page visits, followers, top pieces, rating trend. | S | D |
| DSH-06 | A help section with short videos in Hindi and Maithili, and a call-back request to support. | S | D |
| DSH-07 | Artisans shall be able to set "away" (for example during harvest or festivals), pausing new orders and showing a note on the brand page. | S | T |

#### 3.2.12 Collectives (COL)

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| COL-01 | A collective manager shall manage member artisans' listings and orders only for artisans who have granted permission, and the artisan may revoke it at any time. | S | T |
| COL-02 | Every action a manager takes for an artisan shall be logged and visible to the artisan. | S | T |
| COL-03 | Payouts shall always go to the artisan's own account unless the artisan explicitly opts into collective payout with a recorded agreement. | S | T |
| COL-04 | Collectives may consolidate pickup for several artisans' orders. | C | D |

#### 3.2.13 Payouts and commission (PAY)

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| PAY-01 | The platform commission rate shall be configurable per craft category, and the artisan share shall be **at least 50% of the sale price** (excluding shipping and taxes). Exact rate **TBD** by owner. | M | T |
| PAY-02 | The commission split shall be published on a public "How artisans are paid" page. | M | I |
| PAY-03 | Payouts shall be released T + N days after delivery (N configurable, default 7, to cover returns), weekly at minimum. | M | T |
| PAY-04 | Failed payouts shall be retried and the artisan told why in their language. | M | T |
| PAY-05 | The system shall produce monthly statements and tax reports for artisans and for the platform (GST, TCS). | M | T |
| PAY-06 | Admins shall be able to hold a payout during a dispute, with a reason shown to the artisan. | M | T |

#### 3.2.14 Admin console (ADM)

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| ADM-01 | Queues: artisan verification, listing review, review moderation, disputes, payouts, grievances. | M | D |
| ADM-02 | Role-based admin permissions (super admin, verifier, moderator, support, finance). | M | T |
| ADM-03 | Every admin action shall be logged with who, what, when and why. | M | T |
| ADM-04 | Content management for home slots, collections, craft pages and policy pages. | M | D |
| ADM-05 | Admins shall manage GI badges, craft categories, accent colours and the commission table. | M | D |
| ADM-06 | Reports: GMV, orders, artisans active, reviews and reply rate, payouts, returns. | S | D |

#### 3.2.15 Workshops and classes (WRK), later phase

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| WRK-01 | Artisans may offer paid online or in-person workshops, listed on their brand page. | C | D |
| WRK-02 | Buyers book and pay for a session; reviews of workshops count toward the artisan rating, labelled "workshop". | C | T |

#### 3.2.16 International (INT), later phase

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| INT-01 | Prices shown in the buyer's currency (USD, GBP, AED, EUR) with INR settlement. | C | T |
| INT-02 | International shipping with duties shown before payment. | C | T |

#### 3.2.17 Continuity, interaction and state (CNT)

The experience shall behave as one continuous application while staying server-rendered, indexable and usable without JavaScript. These requirements restate the continuity layer in `design/design-system.html` (sections `continuity` to `journeys`).

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| CNT-01 | Every page shall be a complete server-rendered document reachable by a normal link. All journeys (browse, artisan, work, bag, checkout, confirmation, review link, award link) shall work with JavaScript disabled. | M | T |
| CNT-02 | With JavaScript, navigation between pages on the same site shall use the Interactivity API Router. The application shell shall stay mounted; only the routed main region shall be replaced. | M | T |
| CNT-03 | The shell (logo, search, bag count, menu, context row, progress line, live regions) shall have fixed dimensions and shall never be destroyed or re-created during routed navigation. | M | T |
| CNT-04 | State shall be kept in four categories: URL (shareable), UI (local, reset on navigation unless noted), session (browser tab), and server (business truth). The Interactivity API shall coordinate server state but never be its source of truth. | M | I |
| CNT-05 | Anything shareable or expected to survive reload (filters, sort, page, search query, checkout step, review target, award and year selection) shall live in the URL. | M | T |
| CNT-06 | Prices, stock, bag, orders, payments, follows, reviews, replies, commissions, verification and payouts shall be read from the server. The bag and checkout shall use the WooCommerce cart and Store API as the only truth; `pd/bag` and `pd/checkout` are presentation adapters. | M | T |
| CNT-07 | Every transitionable entity shall expose `data-pd-entity`, `data-pd-entity-id`, `data-pd-transition` and `data-pd-focus-id`, built from immutable ids (`artisan-184`, `work-3014`, `review-product-912`, `award-12`). Identity shall never come from titles, translated text, list position or DOM index. | M | I |
| CNT-08 | The destination element of a transition shall carry the same entity and transition identity as the source. | M | T |
| CNT-09 | On activating a link the system shall record entity, id, origin URL, focus key, scroll position and URL state; keep the old content visible while loading; replace the routed region; animate the source visual to the destination when both exist; focus the destination heading or contextual target; announce the page; remove temporary state. | M | T |
| CNT-10 | Shared-element motion shall use browser view-transition snapshots or a non-semantic visual clone. The real accessible element shall never be moved between documents. | M | I |
| CNT-11 | Motion shall be limited to four kinds: grow and settle, shrink and dock, move and regroup, reveal and focus. Motion shall explain a relationship between two screens and shall not be applied to every link, heading or control. | M | I |
| CNT-12 | Back and Forward shall restore the previous screen, its filters, scroll position and the control that triggered the navigation, from recent session state first, then reconcile with the server. | M | T |
| CNT-13 | Feedback ladder: 0 to 100 ms pressed state; 100 to 300 ms optimistic feedback only when the action is reversible; after about 100 ms a route progress line if navigation is unresolved; after about 300 ms a destination-shaped skeleton; on failure, keep old content or input, roll back unsafe optimism and offer Try again; on success reconcile with the server. | M | T |
| CNT-14 | The page shall never show a blank canvas during navigation. | M | T |
| CNT-15 | Prefetch shall happen only after strong intent (pointer hover with delay, keyboard focus, deliberate touch) and only for the target page. The whole catalogue shall never be preloaded. | M | T |
| CNT-16 | Checkout and payment shall never be shown as successful before the server confirms. | M | T |
| CNT-17 | Under `prefers-reduced-motion`: no movement, scale, fly-to-bag or morph; instant or opacity-only change; identical URL, history, focus and announcements; the destination marked by a double rule, outline or brief static highlight; static header logo instead of the splash. | M | T |
| CNT-18 | If shared transitions are unsupported or fail, normal routed navigation shall proceed with identical focus and scroll behaviour. Animation shall never block navigation or be required to understand a relationship. | M | T |
| CNT-19 | After each routed navigation focus shall move to the page heading (or the contextual target, such as the exact review) and a polite live region shall announce the new page. Validation errors shall move focus to one error summary. | M | T |
| CNT-20 | A review link (`?review=review-product-912`) shall open the page on the correct scope and page of the thread, scroll to the entry, highlight it and focus it. | M | T |
| CNT-21 | Public Interactivity API state and inline JSON shall contain no customer addresses, phone numbers, private order notes, payment data, payout account data, verification documents, internal moderation notes or other artisans' private data. | M | T |
| CNT-22 | State needed on load shall be seeded on the server with `wp_interactivity_state()` and `data-wp-context`; it shall not be fetched after load. | M | T |
| CNT-23 | Each shared key (bag, follows, review counts, PIN, filters, role flags and so on) shall have exactly one owning Interactivity API store and one writer. Other blocks shall read it and change it only through the owner's actions or published events. | M | I |
| CNT-24 | Blocks shall bind to the owner's state or getters. A block shall not keep its own copy of a shared value. Per-instance identifiers (work id, artisan post id, review id) shall be block context, not store state. | M | I |
| CNT-25 | Each store shall be seeded once per request by one PHP state provider. Blocks shall not seed the same keys, so rendering order cannot change the result. | M | T |
| CNT-26 | Every shared key shall be declared reseed (server value replaces it on each navigation), persist (kept across navigation) or reset (cleared when the route settles). Stale values shall not survive a route change. | M | T |
| CNT-27 | Optimistic changes shall be applied and rolled back in the owning store, so every block that shows the value changes together. Server responses shall overwrite the owner's value. | M | T |
| CNT-28 | An automated check shall compare every on-page mirror of each shared key with its owner after each interaction in the continuity journeys, with zero mismatches. | M | T |
| CNT-29 | Shared state shall contain only public values and the signed-in user's own flags (SEC-10). | M | T |
| CNT-30 | **Normalised review repository.** One service shall return a common review DTO for product reviews (WooCommerce comments) and artisan reviews (`pd_artisan_reviews`): typed id, type, artisan id, product id (product reviews), rating, public author name, verified-purchase flag, content, language code, created date, moderation state (authorised callers only), optional artisan reply. | M | T |
| CNT-31 | `pd/reputation-thread` shall be a dynamic server-rendered block that reads only from this repository. The WordPress comment blocks shall not be used to combine the two sources. | M | I |
| CNT-32 | Scopes: artisan reviews (default) and product reviews are separate lists, as REV-10 requires. Filter by rating, with photos, and (product scope) by work or craft. Sort: most recent (default), highest, lowest, with photos (REV-15). Filters and sort live in the URL. | M | T |
| CNT-33 | Pagination: server-rendered numbered pages (`?rp=2`) over `pd_review_index`; with JavaScript the Router swaps only the thread region. A keyset cursor shall replace offset paging if any artisan exceeds 10,000 reviews. | M | T |
| CNT-34 | Deep link: `?review={typed-id}` resolves the entry in the current scope and sort, opens the correct page, and marks the entry. Unknown or removed ids show the thread with a polite notice. | M | T |
| CNT-35 | Cache: review creation, edit, reply and moderation shall purge the artisan page, the work page and any home snippet that shows them, update `pd_review_index`, recalculate the rating cache in `pd_artisans`, and bump a version key in the object cache. | M | T |
| CNT-36 | Permissions: only a buyer with a delivered order may create or edit a review (REV-01, REV-13). Only the owning artisan, or a collective manager with consent, may reply, once, within the edit window (REV-11). Only moderators may change moderation state. Artisans cannot delete, hide or reorder (REV-14). | M | T |
| CNT-37 | Moderation states shall be pending, published, flagged, removed; non-public states are never returned to public callers and never appear in hydrated state. | M | T |
| CNT-38 | Reply and report actions shall work as plain forms (post, redirect, get) without JavaScript, and as REST calls with JavaScript. | M | T |


### 3.3 Performance requirements

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| PRF-01 | On a mid-range Android phone over a throttled 4G connection, Largest Contentful Paint for home, brand page and piece page shall be ≤ 2.5 s at the 75th percentile. | M | T |
| PRF-02 | Interaction to Next Paint ≤ 200 ms and Cumulative Layout Shift ≤ 0.1 at the 75th percentile. | M | T |
| PRF-03 | The splash shall not delay LCP: its assets shall be inline SVG under 15 KB, and content shall load behind it. | M | T |
| PRF-04 | Initial JavaScript for buyer pages ≤ 170 KB compressed. Fonts subset and preloaded, with `font-display: swap`. | S | T |
| PRF-05 | Search results shall return in ≤ 500 ms (server time) at the 95th percentile. | M | T |
| PRF-06 | The artisan app shall upload a listing with 6 photos on a 3G connection; photos shall be compressed on device and uploads resumable. | M | T |
| PRF-07 | The system shall support 500 concurrent users and 50 orders per minute at launch, scaling to 10x without redesign. | M | A, T |
| PRF-08 | API p95 latency ≤ 300 ms for reads and ≤ 800 ms for writes, excluding third-party calls. | S | T |

### 3.4 Usability requirements

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| USE-01 | The buyer app shall meet **WCAG 2.2 level AA** [R11]. | M | T, I |
| USE-02 | Body text ≥ 16 px (17 px default), tap targets ≥ 44 × 44 px, visible 3 px neel focus ring. | M | I |
| USE-03 | Text contrast ≥ 4.5:1 (body) and ≥ 3:1 (large text, UI outlines), using the ratios in [R1]. | M | T |
| USE-04 | All motion, including the splash, shall respect `prefers-reduced-motion`. | M | T |
| USE-05 | All images of pieces shall have alt text; the artisan app shall propose alt text from the listing title for the artisan to confirm. | M | I |
| USE-06 | An artisan who has never used an online store shall be able to create a listing in under 10 minutes after a 5-minute guided intro, in usability testing with at least 8 artisans in Bihar. | M | D |
| USE-07 | A first-time buyer shall find a named artisan's page and buy a piece in under 3 minutes in usability testing. | M | D |
| USE-08 | Artisan-facing text shall be written at a simple reading level and reviewed by a native Maithili speaker. | M | I |
| USE-09 | Error messages shall say what happened and what to do next, in the user's language, never only a code. | M | I |

### 3.5 Logical database requirements

#### 3.5.1 Main entities (logical)

| Entity | Key attributes | Notes |
|---|---|---|
| **User** | id, phone, email, name, language, roles, consent flags, created_at | One person, many roles |
| **Artisan (public identity)** | post id, slug, name_latin (title), name_deva, story (content), portrait (featured image), workshop media, craft narrative (excerpt), village, lineage, crafts[], district, languages[], translations, SEO fields, publication state, revisions | WordPress post of type `pd_artisan`. Everything buyers read about the person. |
| **ArtisanOps (operations)** | id, post_id, user_id, verification_status, verified_at, accent, motif, taking_orders, lead_time_days, away_until, rating_avg, rating_count, reply_rate, sales_count, follower_count, created_at, updated_at | Table `pd_artisans`. Never holds names or story text. Public and private columns are listed in DB-12 |
| **Collective** | id, name, type (SHG, coop, NGO), district, manager_user_ids[] | |
| **ArtisanCollectiveLink** | artisan_id, collective_id, permissions, granted_at, revoked_at | Consent trail |
| **Award** | id, slug, name (en/hi/mai), awarding_body, level, crafts[], description, emblem | Award register |
| **ArtisanAward** | id, artisan_id, award_id, year, category, citation, proof_files[], status (pending, verified, rejected, revoked), verified_by, verified_at | Shown only when verified |
| **Craft** | id, name (en/hi/mai), region, gi_registered, description | |
| **Piece** (listing) | id, artisan_id, craft_id, title, description, photos[], video, materials, size, weight, price, stock, one_of_a_kind, lead_time_days, gi_badge, status, review_state, rating_avg, rating_count | |
| **Order** | WooCommerce order id, buyer, payment, totals, tax, coupons, status | The customer order. WooCommerce is authoritative |
| **OrderAllocation** | id, wc_order_id, artisan_id, status, items_subtotal, commission, artisan_payable, shipping_amount, tax_amount, est_ready_date, accepted_at, ready_at, shipped_at, completed_at | One per artisan on an order. Owns the artisan work queue |
| **OrderAllocationItem** | allocation_id, wc_order_item_id, quantity, unit_amount, commission_amount, artisan_amount | Splits each WooCommerce order item |
| **Shipment** | id, wc_order_id, allocation_id, carrier, awb, tracking_url, status, shipped_at, delivered_at | One or more per allocation |
| **ProductReview** | id, piece_id, artisan_id, wc_order_item_id, buyer_id, rating, sub_ratings, text, photos[], status, edited_at | Belongs to the piece |
| **ArtisanReview** | id, artisan_id, allocation_id (or commission_request_id), buyer_id, rating, sub_ratings, text, kind (purchase, custom, workshop), status, edited_at | Belongs to the artisan |
| **ArtisanReply** | id, review_type (product, artisan), review_id, artisan_id, text, original_language, audio, created_at, edited_at | One per review |
| **Follow** | buyer_id, artisan_id, created_at | |
| **CommissionRequest** | id, buyer_id, artisan_id, brief, budget, deadline, status, milestones[] | |
| **Payout** | id, artisan_id, period, gross, commission, fees, tax, net, status, utr | |
| **AuditLog** | id, actor_id, action, target, reason, at | Append only |

#### 3.5.2 Rules

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| DB-01 | Each ProductReview shall reference exactly one Piece and one order line; each ArtisanReview exactly one Artisan and one OrderAllocation (or CommissionRequest). A Piece's rating shall be derived only from its ProductReviews and an Artisan's rating only from its ArtisanReviews, recalculated on every create, edit or removal. | M | T |
| DB-02 | Each ProductReview and each ArtisanReview shall have at most one ArtisanReply. | M | T |
| DB-03 | Money shall be stored as integer paise with currency code. | M | I |
| DB-04 | Text fields shall be Unicode (UTF-8) and store the original language code. | M | I |
| DB-05 | Personal data shall be separable from transactional records so it can be erased while keeping legally required invoices (see SEC-07). | M | A |
| DB-06 | AuditLog shall be append-only and retained for at least 8 years (**TBD** with legal). | M | I |
| DB-07 | Daily backups with point-in-time recovery for 7 days; backups encrypted and restore tested quarterly. | M | T |
| DB-08 | **One canonical order.** A customer purchase shall be exactly one WooCommerce order. WooCommerce remains authoritative for customer order, payment, tax, coupons, stock reduction, refunds, customer totals and checkout status. No child WooCommerce orders are created for artisans. | M | I |
| DB-09 | **Allocations.** On payment, `pd-marketplace` shall create one OrderAllocation per artisan on the order, with OrderAllocationItems that split every order item. Allocation amounts shall sum to the order amounts: items, commission, artisan payable, shipping and tax, to the paisa. A reconciliation check shall run on every create and on every refund. | M | T |
| DB-10 | **What allocations own.** Allocations own artisan work queues, making status, estimated ready date, artisan payout calculation, shipment status per artisan and artisan timelines. They shall never change WooCommerce totals, stock or payment status. A WooCommerce refund shall be mapped back to allocation items and recalculate commission and payable. | M | T |
| DB-11 | **Hybrid artisan identity.** An artisan shall be a `pd_artisan` post (editorial identity) linked one-to-one to a `pd_artisans` row (operations) by `post_id` and to one WordPress user by `user_id`. A public name or story shall exist in exactly one place, the post. Operational fields shall exist in exactly one place, the table. The handle is the post slug. | M | I |
| DB-12 | **Public and private operational columns.** Public (may appear in page output and public REST): accent, motif, taking_orders, lead_time_days, away_until, rating_avg, rating_count, reply_rate, sales_count, follower_count, verification badge state. Private (capability-gated, never in public output): user_id, verified_at, verifier, internal notes, payout and identity-document references. | M | T |
| DB-13 | **Publication rule.** A `pd_artisan` post shall be `publish` only while verification_status is verified; withdrawal of verification returns it to draft. Revisions, translations and SEO fields follow WordPress behaviour. | M | T |
| DB-14 | **Review read model.** Product reviews (WooCommerce comments of type `review`) and artisan reviews (`pd_artisan_reviews`) keep separate storage. A derived, rebuildable table `pd_review_index` shall hold one row per public review (typed id, type, artisan_id, product_id, rating, has_photos, language, state, created_at) so lists can be filtered, sorted and paged without cross-table queries. Source rows remain authoritative. | M | T |
| DB-15 | **Bilingual public text from day one.** Every public text of an artisan and a work (name, title, story, excerpt, materials note, craft and village labels) shall have an English field and a Devanagari field, stored with the post (post meta with a `_hi` suffix or a translation plugin, decided in spike M-1 item 1). English is required; Devanagari is required for artisan names and optional elsewhere, and a page shows Devanagari beneath English when present. Fields are public and appear in REST as separate keys. | M | T |

#### 3.5.3 Physical mapping on WordPress / WooCommerce

| Logical entity | Stored as |
|---|---|
| User | WordPress user (`wp_users`, `wp_usermeta`) with roles `customer`, `artisan` (custom role from `pd-marketplace`), `collective_manager`, and admin roles |
| Artisan (public identity) | Custom post type `pd_artisan` (`public`, `show_in_rest`, rewrite `/a/{slug}`, supports title, editor, thumbnail, excerpt, revisions, custom-fields). Taxonomies: craft (shared product category terms), `pd_district`, `pd_language`. Post meta for Devanagari name, village, lineage and workshop media. Translations through the multilingual plugin (MNT-02). Each product carries `_pd_artisan_post_id` |
| ArtisanOps | Custom table `pd_artisans` (post_id unique, user_id unique, verification_status, verified_at, accent, motif, taking_orders, lead_time_days, away_until, rating cache, review count, reply_rate, sales_count, follower_count, created_at, updated_at). Joined to the post for directory sorts and filters (`posts_clauses`) |
| Collective, ArtisanCollectiveLink | Custom tables `pd_collectives`, `pd_collective_links` (custom plugin) |
| Award / ArtisanAward | Custom taxonomy `pd_award` (with term meta) and custom table `pd_artisan_awards` in `pd-marketplace` |
| Craft | WooCommerce product category taxonomy + term meta (GI status, region, Hindi/Maithili names) |
| Piece | WooCommerce product (`product` post type); one-of-a-kind = stock 1 with "sold individually" |
| Order | WooCommerce order (HPOS). One per purchase. Authoritative for payment, tax, coupons, stock, refunds, totals and status |
| OrderAllocation, OrderAllocationItem | Custom tables `pd_order_allocations` and `pd_order_allocation_items` (HPOS-compatible, keyed to the WooCommerce order and order-item ids, no foreign keys into WooCommerce tables). Created on `woocommerce_payment_complete` / status transition, idempotent |
| Shipment | Custom table `pd_shipments` (order, allocation, carrier, AWB, tracking URL, status, timestamps), fed by the shipping aggregator plugin. WooCommerce order notes mirror the customer-visible status |
| ProductReview | WooCommerce product review (comment type `review`, `rating` meta, "verified owner" flag) |
| ArtisanReview | Custom table `pd_artisan_reviews` in `pd-marketplace`, keyed to the order allocation |
| ArtisanReply | WooCommerce review reply (comment child by the artisan) for product reviews; `pd_artisan_review_replies` for artisan reviews |
| Review (normalised) | PHP repository `PD\Reviews\Repository` returns one review DTO for both sources, reading `pd_review_index` and hydrating from source rows (see 3.2.17 CNT-30 to CNT-38) |
| Follow | Custom table `pd_follows` |
| CommissionRequest | Custom post type `pd_commission` + custom table for milestones |
| Payout | Custom tables `pd_commissions` and `pd_payouts`, reconciled with Razorpay Route transfers |
| AuditLog | Activity-log plugin plus a custom append-only table for verification, payout and moderation actions |


#### 3.5.4 Why one order with allocations, not child orders (decision, 2026-11-07)

Child WooCommerce orders per artisan were considered and rejected. Allocations keep WooCommerce semantics intact:

| Concern | Child orders | One order plus allocations |
|---|---|---|
| Refunds | A refund is tied to the order that took the payment. Child orders have no payment, so partial refunds must be mirrored and can drift | One refund object on the real order; allocation items recalculate from it (DB-10) |
| Tax | Tax and rounding would be recomputed per child and can differ from the parent total | WooCommerce computes tax once; allocations store the split and are reconciled to the paisa (DB-09) |
| Stock | Stock is reduced on payment; children risk double reduction or restore on cancellation | Reduced once on the one order |
| Emails | Every child sends customer and admin emails unless suppressed | One set of customer emails; artisan notifications are our own |
| Analytics | WooCommerce Analytics counts revenue and orders twice unless children are excluded | One order, one revenue figure |
| Payment reconciliation | The gateway sees one payment; children cannot be matched to it | One payment to one order; payouts are computed from allocations (PAY) |
| HPOS | Supported, but many extensions mishandle parent and child orders | Standard orders only; custom tables are independent of HPOS |

Costs accepted: artisan queues, statuses and shipments live in our tables; one tax invoice per order is issued by our code (CHK-08) from a single number series; WooCommerce shipping packages are split by artisan so shipping lines map to allocations. These are proven in spike M-1 (see `BLOCKS.md`).

#### 3.5.5 REST surface (summary)

Public routes return only public data (DB-12, SEC-10). Every route checks capability and ownership (SEC-08, SEC-09). Nonces are sent with cookie-authenticated calls but are not authorisation.

| Route | Who | Returns or does |
|---|---|---|
| `GET wp/v2/pd_artisan` | Public | Editorial identity of published artisans (title, Devanagari name meta, story, media, taxonomies) |
| `GET pd/v1/artisans` and `/artisans/{post_id}` | Public | Editorial fields joined with the public operational columns (accent, motif, taking orders, lead time, rating, review count, follower count) |
| `GET pd/v1/artisans/{post_id}/reviews?scope=artisan|product&sort=&rating=&photos=&rp=` | Public | Review DTOs from the normalised repository, one page |
| `POST pd/v1/reviews` | Buyer with delivered order | Creates a product or artisan review |
| `POST pd/v1/reviews/{typed-id}/reply` | Owning artisan or consented collective manager | One reply; `PATCH` within 7 days |
| `POST pd/v1/reviews/{typed-id}/report` | Any signed-in user | Moderation queue |
| `GET pd/v1/studio/allocations` | Artisan (own rows only) | Allocation queue with items, status and shipments |
| `POST pd/v1/studio/allocations/{id}/accept|ready|ship` | Owning artisan | Moves allocation status; writes shipment; never touches WooCommerce totals |
| `POST pd/v1/follow/{post_id}` | Signed-in buyer | Follow or unfollow |
| `POST pd/v1/commissions` and thread routes | Buyer and artisan of that request, admins | Commission request, messages, milestone approval |
| `GET/POST wc/store/v1/cart...` | Buyer | WooCommerce Store API, extended with artisan grouping data under the `pd` extension namespace (public fields only) |
| Plain `admin-post.php` handlers | Same as above | No-JavaScript fallbacks for follow, review, reply, report, commission (post, redirect, get) |

### 3.6 Design constraints

#### 3.6.1 Standards compliance

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| CON-01 | Comply with the Consumer Protection (E-Commerce) Rules 2020 [R7]: seller details, grievance officer, country of origin, return and refund policy, no manipulated reviews or prices. | M | I |
| CON-02 | Comply with the DPDP Act 2023 [R8]: notice and consent, purpose limitation, data principal rights, breach notification. | M | I |
| CON-03 | Comply with GST invoicing and TCS rules for e-commerce operators [R10]. | M | I |
| CON-04 | Payment handling shall stay within the gateway's PCI-DSS scope; card data never touches Priyadarshini servers. | M | I |
| CON-05 | GI badges only for crafts and regions registered under [R12]. | M | I |

#### 3.6.2 Brand constraints
- The palette, fonts and component rules in [R1] and [R2] are fixed for this release. Changes need owner approval.
- The splash motif shall be original Madhubani art commissioned from a Mithila artist, credited on the About page.
- The design must not look like a generic e-commerce template [R4]: the artisan, not the product grid, leads every page.

#### 3.6.3 Platform constraints

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| PLT-01 | Supported browsers: last 2 versions of Chrome, Edge, Firefox, Safari; Chrome for Android and Samsung Internet on Android 9+; Safari on iOS 15+. | M | T |
| PLT-02 | The buyer site should be an installable PWA (web app manifest and service worker in the theme). | C | T |
| PLT-03 | Public pages are server-rendered by WordPress and shall be served from full-page cache; logged-in and cart pages bypass the cache. | M | I |
| PLT-04 | Managed WordPress hosting in an Indian region with PHP 8.2+, MySQL 8 / MariaDB 10.6+, Redis object cache, server page cache and a CDN (for example Cloudflare). | M | I |
| PLT-05 | Plugins shall come from a vetted list (actively maintained, compatible with HPOS and the current WooCommerce); no page builders on buyer pages; every added plugin needs a performance check against 3.3. | M | I |
| PLT-06 | The theme shall be a custom block theme (or a lean child theme) whose `theme.json` holds the design tokens in [R1]. | M | I |
| PLT-07 | Customisation shall be done in custom plugins and the theme using WordPress hooks, never by editing WordPress, WooCommerce or plugin core files. | M | I |
| PLT-08 | Marketplace behaviour (artisans, order allocations, commissions, payouts, artisan reviews, follow, commission requests, collectives) shall live in our own `pd-marketplace` plugin built on WooCommerce hooks, data stores and the REST API; no third-party multi-vendor plugin. | M | I |
| PLT-09 | Separate staging and production sites; updates tested on staging before production. | M | I |
| PLT-10 | **Gutenberg blocks:** every buyer page shall be assembled in the block editor from core blocks, WooCommerce blocks (Product Collection, Single Product, Cart, Checkout, Mini-Cart) and Priyadarshini custom blocks, using block templates, template parts and patterns in the block theme. No page builders or shortcode layouts. | M | I |
| PLT-11 | **Custom blocks** shall be registered with `block.json` (API version 3), built with `@wordpress/scripts`, and rendered server-side (dynamic blocks, `render.php`) so content is cached and indexable. Each block shall expose only the design-token styles allowed in `theme.json`. | M | I |
| PLT-12 | **Interactivity API:** front-end interactivity in custom blocks shall use the WordPress Interactivity API (`@wordpress/interactivity`, `data-wp-*` directives, shared stores, server-side state via `wp_interactivity_state()`), loaded as script modules. No jQuery and no separate React or Vue app on buyer pages. | M | I |
| PLT-13 | Interactive blocks shall render fully on the server first and work as plain links or forms without JavaScript where possible (progressive enhancement); state needed on load shall be hydrated from the server, not fetched after load. | M | T |
| PLT-14 | Interactive blocks shall call WordPress REST endpoints (custom `pd/v1` namespace and the WooCommerce Store API) with nonces, and update the page without a full reload. Client-side navigation (Interactivity Router) shall be used for filters, sorting and pagination on directory, craft and search pages. | S | T |
| PLT-15 | Editors shall be able to place and configure custom blocks (for example choose featured artisans for the haat) from the block editor sidebar without code; editor previews shall match the front end. | M | D |

#### 3.6.4 Artisan app (fork of the official WooCommerce mobile app)

Artisans manage their shop from their phone. Instead of building an app from scratch, we fork the official open-source WooCommerce mobile app and extend it for artisans.

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| APP-01 | The artisan app shall be a fork of the official WooCommerce Android app (`woocommerce/woocommerce-android`); an iOS fork of `woocommerce/woocommerce-ios` may follow. | M (Android) / C (iOS) | I |
| APP-02 | The fork shall be fully rebranded as "Priyadarshini Artisan": own app name, icon, package ID and signing key, palette A colours and Mukta/Yatra One fonts; no WooCommerce or WordPress trademarks in the app or store listing. | M | I |
| APP-03 | The fork shall comply with the upstream licence (GPL): keep licence and copyright notices, and make our modified source available as the licence requires (**TBD**: legal check). | M | I |
| APP-04 | Sign-in shall be mobile number + OTP against the Priyadarshini site, replacing WordPress.com/Jetpack and site-address login; the app is locked to the Priyadarshini site. | M | T |
| APP-05 | The app shall act only as the signed-in artisan: it calls artisan-scoped endpoints in `pd-marketplace` (`pd/v1/artisan/...`) so an artisan sees and edits only their own pieces, orders, reviews, earnings and customers' shipping details. | M | T |
| APP-06 | Keep from upstream and adapt: product list and editor, photo upload, order list and detail, order status updates, notifications, offline caching. | M | D |
| APP-07 | Remove or hide store-owner features artisans must not use: store settings, payments setup, coupons, taxes, shipping zones, plugins, store-wide analytics, other users. | M | I |
| APP-08 | Add: onboarding and verification status (ONB), brand page editor with story, portrait and accent (BRD), product reviews and artisan reviews with replies by text or voice (REV), commission requests and milestone thread (CR), earnings breakdown and payouts (DSH-03, PAY), "away" mode (DSH-07), help videos and call-back (DSH-06). | M / S | D |
| APP-09 | Simplified mode (default for new artisans): home shows only "new orders, requests, reviews, earnings"; large icons with labels; one task per screen; "speak instead" on every text field; photo capture guide (CAT-04). | M | D |
| APP-10 | The app shall be fully translated into Hindi and Maithili, reviewed by native speakers, and switchable from the first screen. | M | I |
| APP-11 | Push notifications through Firebase Cloud Messaging for new order, order to accept within 24 hours, new review, commission request, payout sent. | M | T |
| APP-12 | Run well on Android 9+ phones with 2 GB RAM; APK/AAB download under 30 MB; photos compressed on device; uploads resumable on 3G (PRF-06). | M | T |
| APP-13 | Upstream tracking: merge upstream WooCommerce app releases at least quarterly, keeping our changes in separate modules or clearly marked layers to keep merges small; security fixes from upstream within 2 weeks. | S | I |
| APP-14 | Distribution through Google Play, plus a direct APK link for field partners helping artisans without Play access. | M | D |
| APP-15 | Collective managers shall be able to switch between the artisans who granted them permission (COL-01), with every action logged. | S | T |

### 3.7 Software system attributes

#### 3.7.1 Reliability and availability

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| REL-01 | Availability ≥ 99.5% per month for buyer browsing and checkout. | M | A |
| REL-02 | Payment and order creation shall be idempotent; a retried payment never creates two orders. | M | T |
| REL-03 | If a third-party service fails, the system shall degrade gracefully (for example SMS falls back to WhatsApp or email; shipping booking queued and retried). | M | T |
| REL-04 | Recovery point objective ≤ 15 minutes; recovery time objective ≤ 4 hours. | S | T |

#### 3.7.2 Security

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| SEC-01 | Role-based access control; artisans see only their own orders, buyers and payouts; collective managers only consented artisans. | M | T |
| SEC-02 | OTP limited to 5 attempts per 15 minutes per number, with bot protection on sign-up. | M | T |
| SEC-03 | Personal data encrypted in transit and at rest; bank details and identity documents encrypted with separate keys and visible only to finance and verifier roles. | M | I |
| SEC-04 | Protect against OWASP Top 10; independent security test before launch. | M | T |
| SEC-05 | Buyer addresses and phone numbers shown to the artisan only as needed to ship, and masked after delivery. | M | T |
| SEC-06 | Review-fraud controls: one product review per order line and one artisan review per order allocation, detection of review rings (same device or payment instrument), and no reviews by the artisan's own account. | M | T |
| SEC-07 | Data retention schedule: erase personal data on request or when no longer needed, keep invoices and tax records for the legal period. | M | I |
| SEC-08 | Every REST endpoint and form handler shall check capability and ownership on the server. Nonces prove intent only; they are not authorisation. | M | T |
| SEC-09 | Authorisation matrix, enforced server-side: an artisan edits only their own works and pd_artisan post, replies only to their own reviews, sees only their own allocations, shipments and payouts; a buyer sees only their own orders and commission threads; a collective manager acts only for artisans who granted consent; award verification and moderation need admin capabilities; payout and bank data need finance capability; commission threads are visible only to the buyer, the artisan and admins. | M | T |
| SEC-10 | Public page state, Interactivity API state and JSON in page source shall never carry customer addresses, phone numbers, private order notes, payment information, payout account details, verification documents, internal moderation notes or other artisans' private data. An automated test shall scan page output and public REST for these. | M | T |
| SEC-11 | The artisan's public data shall come from the published `pd_artisan` post and the public columns of `pd_artisans` (DB-12) only. | M | T |
| SEC-12 | Profile changes by an artisan shall go through revisions and admin review for fields that affect trust (name, awards, verification documents), and be logged in the AuditLog. | M | T |
| SEC-13 | Allocation, shipment and payout endpoints shall return only rows owned by the caller's artisan id, derived from the logged-in user on the server and never from a request parameter. | M | T |
| SEC-14 | Review-reply, follow and commission actions shall be rate-limited per user and per IP. | S | T |

#### 3.7.3 Privacy

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| PRV-01 | A privacy notice in English, Hindi and Maithili, shown at sign-up, with separate consent for marketing messages. | M | I |
| PRV-02 | Users can view, correct, download and erase their data from settings, and withdraw consent. | M | T |
| PRV-03 | A grievance officer and data protection contact published on the site. | M | I |
| PRV-04 | Artisans control what personal details appear on their brand page (for example village shown, exact address never shown). | M | T |

#### 3.7.4 Maintainability and portability

| ID | Requirement | Pri | Ver |
|---|---|---|---|
| MNT-01 | Design tokens defined once in `theme.json` and shared by the buyer site, artisan dashboard and branded admin screens. | M | I |
| MNT-02 | All user-facing strings use WordPress i18n functions and `.po/.mo` files (Hindi `hi_IN`, Maithili `mai`); content translated with a multilingual plugin (WPML or Polylang for WooCommerce, **TBD**). | M | I |
| MNT-03 | Automated tests for checkout, payouts, review rating calculation, and permissions, run on every change. | M | I |
| MNT-04 | Third-party providers (payment, shipping, messaging) used through their WooCommerce plugins or a thin custom adapter, so they can be swapped. | S | I |
| MNT-06 | Code in version control (theme + custom plugins); plugin and core versions pinned and updated monthly; security updates within 72 hours. | M | I |
| MNT-05 | Structured logs, error tracking and uptime alerts. | M | I |

### 3.8 Supporting information

- **Screen list for the blueprint** (next design step): see Appendix C.
- **Competitive reasoning** for each differentiator: see [R3], tab "Gaps for us", and the traceability in Appendix A.

---

## 4. Verification

| Requirement type | How it is verified | When |
|---|---|---|
| Functional (ACC to INT) | Automated unit and integration tests; end-to-end tests for buyer purchase, artisan listing, review and reply, payout | Every build; full run before each release |
| Payments and payouts | Gateway sandbox tests including failure, retry, refund and split; reconciliation report matches gateway settlement | Before launch and monthly |
| Performance (PRF) | Lighthouse and WebPageTest on throttled 4G mid-range Android; load test to PRF-07 levels | Each release |
| Usability (USE) | Moderated tests with at least 8 artisans in Bihar (in Maithili and Hindi) and 8 buyers; accessibility audit with axe plus manual screen-reader check | Before launch, then each major release |
| Design (UI) | Design review against [R1], [R2], [R4]; visual regression snapshots | Each release |
| Security (SEC) | Automated scanning; independent penetration test | Before launch, then yearly |
| Compliance (CON, PRV) | Legal review checklist for [R7], [R8], [R10] | Before launch |
| Reliability (REL) | Chaos tests on third-party outages; restore drill | Quarterly |
| Continuity and interaction (CNT) | Playwright journeys with JavaScript on and off; Back, Forward and reload tests; reduced-motion run; keyboard-only run; screen-reader announcement check; automated scan of public page state for private data (SEC-10) | Every build |
| Architecture (spike M-1) | Fourteen feasibility spikes listed in `BLOCKS.md` section 9, each with a pass or fail result recorded before M0 starts | Before foundation work |

Acceptance for launch: every **M** requirement passes its verification method, and no open critical defect.

---

## 5. Appendices

### Appendix A. Traceability: goals and market gaps to requirements

| Goal / gap [R3] | Requirements |
|---|---|
| G1. Artisan is the brand / gap 2 (artisans hidden) | BRD-01 to BRD-12, DIS-01, DIS-05, UI-05, UI-10 |
| G2. Product and artisan reviews / gap 1 (no per-artisan reviews in India) | REV-01 to REV-20, DB-01, DB-02, SEC-06 |
| Two ways to buy (ready piece or commissioned work) / gap 8 | BUY-01 to BUY-11, CR-00 to CR-07 |
| Master artisans as brands (award winners) | AWD-01 to AWD-12, CR-00 |
| Gap 3 (cannot follow an artisan) | FOL-01 to FOL-05, DIS-08 |
| G3. Authenticity / gap 4 | ONB-05, CAT-02, CAT-07, CAT-08, CON-05 |
| Gap 5 (Bihar crafts beyond Madhubani) | CAT-11, DIS-06 |
| G4. Fair, visible pay / gap 6 | ONB-07, DSH-03, PAY-01, PAY-02 |
| G5. Artisan language / gap 7 | APP-09, APP-10, ACC-07, ONB-02, CAT-03, REV-12, USE-06, USE-08, MNT-02 |
| G6. Custom work on-platform / gap 8 | CR-01 to CR-07 |
| Brand feel (Madhubani, kagaz, Pigment palette) | UI-01 to UI-09, PRF-03, 3.6.2 |
| Continuous application feel (shell, shared elements, Back, feedback) | CNT-01 to CNT-29, UI-08, UI-09, PRF-03 |
| Trustworthy reviews across two sources | CNT-30 to CNT-38, REV-01 to REV-20, DB-14, SEC-06 |
| Safe order handling (one order, artisan allocations) | DB-08 to DB-10, CHK-07, CHK-08, ORD-01 to ORD-10, PAY-01 to PAY-04 |

### Appendix B. Open issues (TBD)

| # | Question | Needed by |
|---|---|---|
| 1 | Exact platform commission per category (≥ 50% to artisan agreed in principle?) | Before seller agreement |
| 2 | Return policy for one-of-a-kind and custom pieces | Before launch |
| 3 | GST position for unregistered small artisans; PAN requirement thresholds | Before onboarding |
| 4 | Which payment gateway and shipping aggregator | Before build of checkout |
| 5 | Is COD offered at launch, and the cap | Phase 2 planning |
| 6 | Final splash motif and the commissioning artist | Before visual design freeze |
| 7 | Launch districts and number of founding artisans | Before onboarding |
| 8 | Success targets in 1.2 | Before launch plan |
| 9 | Audit log retention period | Before build |
| 13 | Final seed list of awards, and permission to show award emblems | Before launch |
| 14 | ~~Move commission requests into Phase 1?~~ Decided: yes, with Buy now / Commission now (v0.7) | Done |
| 10 | Licence obligations of forking the WooCommerce app (GPL source release) | Before app release |
| 11 | Multilingual plugin: WPML or Polylang for WooCommerce | Before build |
| 12 | Hosting provider in India | Before build |
| 15 | Confirm the hybrid artisan model (`pd_artisan` post plus `pd_artisans` table) after spike M-1 item 1 | Before M0 |
| 16 | Owner chose one tax invoice per order (not per allocation). Confirm with the tax adviser that one invoice listing several artisans as sellers meets GST and TCS rules (CHK-08) | Before checkout build |
| 17 | WooCommerce extension points (cart grouping, checkout steps, confirmation, Store API extension) are assumed; proven or replaced by spike M-1 | Before M0 |
| 18 | Review read model (`pd_review_index`): confirm rebuild job and 10,000-review keyset threshold | Before M3 |
| 19 | Define the "pro artisan" tier that unlocks the restricted story editor (BRD-13): by sales, by tenure, by verification, or by invitation | Before M4 |

### Appendix C. Screen inventory (input to the blueprint)

**Buyer web app**
1. Splash (Madhubani motif to header logo)
2. Home (the haat)
3. Artisan directory
4. Artisan brand page (story, pieces, artisan reviews and product reviews with replies, follow, request custom)
5. Craft page
5a. Awards hall (all awards)
5b. Award page (winners by year, Commission a work)
6. Search results (artisans, pieces)
7. Piece page (buy box with Buy now and Commission now)
7a. Commission now form (pre-filled from a piece, or blank) and quote
7b. My commissions (stages, progress photos, milestone payments)
8. Cart (grouped by artisan)
9. Checkout (address, delivery, payment)
10. Order confirmation
11. Orders and tracking
12. Write a review (rate the piece, rate the artisan)
13. Following feed and saved pieces
14. Commission request and thread (Phase 2)
15. Account, language, notifications, privacy
16. Policy pages: How artisans are paid, returns, review policy, grievance

**Artisan app**
1. Language choice and sign-in
2. Onboarding steps and verification status
3. Home (orders, requests, reviews, earnings)
4. Brand page editor (story, portrait, accent, awards with proof)
5. New listing (camera guide, voice description)
6. Listings
7. Order detail and dispatch
8. Reviews and reply
9. Commission requests (Phase 2)
10. Earnings and payouts
11. Help

**Admin console**
1. Dashboard
2. Verification queue (artisans, awards)
3. Listing review queue
4. Review moderation queue
5. Orders and disputes
6. Payouts
7. Content (home, collections, crafts)
8. Settings (commission table, award register, GI badges, accents, roles)


### Appendix D. Requirement areas on WordPress / WooCommerce

**Core** = WordPress or WooCommerce out of the box. **Plugin** = an existing third-party plugin, configured. **Custom** = our block theme or our `pd-marketplace` plugin. **Fork** = the artisan app forked from the official WooCommerce mobile app. Plugin names are recommendations to confirm (Appendix B).

| Area | How it is delivered | Build |
|---|---|---|
| UI-01 to UI-12 (design, splash, haat layout) | Custom block theme (`theme.json`, templates, patterns); splash as a custom block with inline SVG and an Interactivity API store | Custom |
| Custom blocks (26 registered) | `pd/app-shell`, `pd/routed-main`, `pd/artisan-card`, `pd/catalogue-filters`, `pd/work-card`, `pd/artisan-masthead`, `pd/reputation-summary`, `pd/artisan-facts`, `pd/award-badges`, `pd/reputation-thread`, `pd/review-form`, `pd/award-index`, `pd/award-winners`, `pd/work-gallery`, `pd/buy-box`, `pd/cart-groups`, `pd/checkout-flow`, `pd/order-groups`, `pd/commission-request`, `pd/my-commissions`, `pd/studio`, `pd/featured-artisan`, `pd/craft-ledger`, `pd/follow-feed`, `pd/search-results`, `pd/follow-button`. Everything smaller (seal, plate, stars, catalogue line, review head and so on) is an internal PHP view component. See `BLOCKS.md` | Custom |
| Interactive behaviour (Interactivity API) | Award page year/craft switch and filters, Splash to header logo, follow/unfollow, wishlist, review flow and photo upload, review sort and filter, artisan reply, directory and search filters (with router), one-of-a-kind reservation timer, language switch, commission request thread | Custom |
| UI-13, APP (artisan app) | Fork of the official WooCommerce Android app, rebranded and extended; talks to `pd-marketplace` artisan endpoints | Fork + Custom |
| ACC (OTP sign-in, roles) | WooCommerce accounts; mobile OTP login plugin (for example Digits) over SMS/WhatsApp; admin 2FA plugin | Core + Plugin |
| ONB (onboarding, verification) | Onboarding flow in the artisan app; verification queue in WP admin, both from `pd-marketplace` | Custom |
| BRD (brand page) | `pd_artisan` post type with block template `single-pd_artisan`, permalinks `/a/{slug}`, revisions and translations; operational data in `pd_artisans`; rendered with `pd/artisan-masthead` and other custom blocks | Core + Custom |
| AWD (awards) | Award register and verification in `pd-marketplace`; award pages as a block template using `pd/award-winners`; awards added from the artisan app | Custom |
| CAT (listing) | WooCommerce products created from the artisan app; voice input, photo guide, GI badge, "Made by" certificate in custom plugin | Core + Custom |
| DIS (browse, search) | WooCommerce catalogue and filters; search plugin with Hindi/transliteration and typo tolerance (for example FiboSearch or a hosted search) | Core + Plugin |
| FOL (follow, wishlist) | `pd-marketplace` follow; wishlist plugin; follower notifications | Plugin + Custom |
| CHK (cart, checkout, payment) | WooCommerce Cart, Checkout and Mini-Cart blocks, Store API and shipping packages split by artisan; Priyadarshini inner blocks `pd/cart-groups`, `pd/checkout-flow`, `pd/order-groups` added through WooCommerce's supported extension points (proven in spike M-1); Razorpay for WooCommerce; COD core; GST invoice per allocation | Core + Plugin + Custom |
| ORD (orders, returns) | WooCommerce orders (authoritative) + `pd-marketplace` order allocations and shipments, handled in the artisan app; returns flow in `pd-marketplace`; Shiprocket plugin for labels and tracking | Core + Plugin + Custom |
| REV product reviews | WooCommerce product reviews with "verified owners only" on; replies customised; read through the normalised review repository | Core + Custom |
| REV artisan reviews | `pd-marketplace` (`pd_artisan_reviews`, replies, moderation, review index, normalised repository, artisan rating); shown by `pd/reputation-thread` | Custom |
| BUY (Buy now / Commission now) | WooCommerce product with `_pd_buying_format` meta; `pd/buy-box` block (Interactivity API); Buy now uses WooCommerce direct-to-checkout; Commission now creates a `pd_commission` request | Core + Custom |
| CR (commission requests) | `pd-marketplace`; milestones paid as linked WooCommerce orders | Custom |
| DSH (artisan dashboard) | Artisan app home and earnings screens (fork), data from `pd-marketplace` | Fork + Custom |
| COL (collectives) | `pd-marketplace` + account switcher in the artisan app | Custom |
| PAY (commission, payouts) | `pd-marketplace` commission table + Razorpay Route split payouts; public "How artisans are paid" page | Custom + Plugin |
| ADM (admin) | WordPress admin, custom roles and capabilities, activity-log plugin, custom queues | Core + Plugin + Custom |
| NET-03 (web push) | Web push plugin or custom service worker | Plugin |
| PRF (performance) | Page and object cache, CDN, image optimisation (WebP/AVIF), font subsetting, plugin budget | Plugin + Hosting |
| SEC / PRV | Security plugin (firewall, login limits), managed host backups, DPDP consent and data export/erase via WordPress privacy tools | Core + Plugin |
| WRK, INT (later) | Booking plugin; multi-currency plugin; WooCommerce international shipping | Plugin |

**Risks to watch on this stack:** building our own marketplace plugin means we own order allocations, commissions and payouts, so these need the strongest test coverage (MNT-03). The WooCommerce app expects one store owner with full access; scoping it to one artisan is the main fork change and must be enforced on the server (APP-05), never only in the app. Keeping the fork close to upstream (APP-13) needs discipline. The LCP and JavaScript budgets in 3.3 (PRF-01, PRF-04) are tight for WooCommerce and need a lean block theme and few plugins; keep all customisation in hooks (PLT-07).
