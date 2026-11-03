# Product specification — MVP

## Goal / audience

An editorial catalog enabling students to discover genuine education discounts, free plans, trials, credits and promotional offers from external partner sites. The site is an information service and is NOT an issuer, verifier or reseller of benefits.

## Public routes

| Route | Required behavior |
|---|---|
| `/` | Featured/latest offers, featured brands and categories, informative CTA |
| `/offers` | Published offers, full text search, category/brand/type filters, sort, pagination |
| `/offers/{slug}` | Description, eligibility, validity, steps, update date, official partner CTA |
| `/brands`, `/brands/{slug}` | Brand index/profile, published brand offers |
| `/categories/{slug}` | Category intro and offers |
| `/search` | Query results, meaningful empty state |
| `/articles`, `/articles/{slug}` | News/guides and editorial content |
| `/{page-slug}` or fixed content routes | About, contact, privacy, terms, disclaimer; ensure no collision with system routes |
| `/admin` | Authenticated Filament CMS ONLY |

## Offer editorial contract

Minimum fields: title, unique slug, brand, category, summary, full detail, offer type/value, eligibility statement, redemption steps, official HTTPS partner destination, verification/update timestamp, publish status, validity start/end (nullable), SEO title/description. Display expired status and avoid presenting expired offers as current. Correct affiliate/sponsored disclosure where applicable.

Publish only after admin review; hide drafts and disabled items. Slugs stable where practical; add 301 redirects when changing live URLs. Search result pages and canonical URLs must avoid duplicate indexing.

## Essential admin

CRUD and publish/unpublish for offers, brands, categories, articles and pages; image upload and previews; clear validation; basic dashboard (published/draft/expired counts); admin login and authorization. Avoid building a custom dashboard framework.

## Launch acceptance

Content-driven homepage and core browse/search/detail routes; responsive navigation/footer; pagination and accessible controls; good meta titles/descriptions, OG tags, sitemap and robots; privacy/terms/contact; links to partner; meaningful empty/404 states; admin can publish without code changes; tests and VPS deploy verified.

## Explicit exclusions

No student verification, school ID, student signup, saved offers, account portal, on-site checkout, coupon issuance, user comments/submissions, email marketing or scraped offer automation. Do not recreate omitted Next.js routes just to match the previous project.

## Product decision rules

Choose the simplest maintainable implementation. One source of truth for publication state and eligibility copy. Partners remain responsible for availability, conditions and verification. Never promise a deal without editorial qualification.
