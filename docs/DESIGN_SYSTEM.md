# Public UI conventions

Student Perks Hub is an editorial directory, not a storefront. Use the existing Tailwind palette and server-rendered Blade components; no second CSS framework.

| Role | Existing Tailwind token |
|---|---|
| Primary action / links | indigo-700 |
| Homepage hero | indigo-950 |
| Accent | amber-400 |
| Page background | slate-50 |
| Heading / primary ink | slate-950 |
| Supporting text | slate-600 |
| Card border | slate-200 |
| Reading surface | white |

- Public shell: shared `layouts/public.blade.php`, max-w-6xl, mobile-first padding.
- Page headings: use `x-public-page-heading` for list and reading views, with an optional eyebrow and description. Heading scale is text-3xl on mobile, text-4xl from sm.
- Reading surface: `x-public-reading-panel` maintains max-w-3xl, responsive padding and rounded-3xl.
- Cards: white surface, subtle border/shadow, rounded-3xl, responsive padding; media fallbacks are decorative, not fabricated logos.
- Main navigation and CTAs: use visible keyboard focus, meaningful link text, min-h-11 where practical, and motion-safe hover transforms.
- Keep preview notices, publication gates, partner-link review, SEO metadata and real CMS content unchanged. Filament admin retains native administrative components.

Use these patterns when revising other public views; do not fork a new design system for each route. Complete real mobile and desktop visual review separately from automated tests.
