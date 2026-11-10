<?php

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Page;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Read-only content gate. Browser, TLS, media durability and backup checks are separate.
 */
Artisan::command('launch:check', function (): int {
    $blockers = 0;
    $warnings = 0;

    $this->info('Student Perks Hub — content readiness (read-only)');

    foreach (['about', 'privacy', 'terms', 'contact'] as $slug) {
        $page = Page::published()->where('slug', $slug)->first(['slug', 'body']);

        if (! $page) {
            $this->error("[BLOCK] Published legal/information page missing: /pages/{$slug}");
            $blockers++;
        } elseif (trim((string) $page->body) === '') {
            $this->error("[BLOCK] Published legal/information page has no content: /pages/{$slug}");
            $blockers++;
        }
    }

    // Legacy/seeded records may predate the route-safe Filament slug validation.
    foreach ([
        'brands' => Brand::where('is_published', true),
        'categories' => Category::where('is_published', true),
        'offers' => Offer::current(),
        'articles' => Article::published(),
        'pages' => Page::published(),
    ] as $type => $query) {
        $invalidSlugs = $query->pluck('slug')->filter(
            fn ($slug): bool => ! is_string($slug)
                || preg_match('/\\A[a-z0-9]+(?:-[a-z0-9]+)*\\z/D', $slug) !== 1
        )->count();

        if ($invalidSlugs > 0) {
            $this->error("[BLOCK] {$invalidSlugs} published {$type} have invalid public URL slugs");
            $blockers++;
        }
    }

    $liveOffers = Offer::current()->count();

    if ($liveOffers === 0) {
        $this->error('[BLOCK] No currently visible offers');
        $blockers++;
    } else {
        $this->info("[OK] {$liveOffers} publicly visible offers");
    }

    $missingOfferCopy = Offer::current()->get(['title', 'summary', 'description'])->filter(
        fn (Offer $offer): bool => trim((string) $offer->title) === ''
            || trim((string) $offer->summary) === ''
            || trim((string) $offer->description) === ''
    )->count();
    if ($missingOfferCopy > 0) {
        $this->error("[BLOCK] {$missingOfferCopy} visible offers have empty title, summary or description");
        $blockers++;
    }

    $incomplete = Offer::current()->needsEditorialReview()->count();
    if ($incomplete > 0) {
        $this->error("[BLOCK] {$incomplete} visible offers lack eligibility or redemption steps");
        $blockers++;
    }

    $unreviewed = Offer::partnerReviewDue()->count();
    if ($unreviewed > 0) {
        $this->error("[BLOCK] {$unreviewed} visible partner links need manual review (never reviewed or older than 90 days)");
        $blockers++;
    }

    $invalidLinks = Offer::current()->get(['id', 'partner_url'])->filter(
        fn (Offer $offer): bool => ! filter_var($offer->partner_url, FILTER_VALIDATE_URL)
            || parse_url($offer->partner_url, PHP_URL_SCHEME) !== 'https'
    )->count();
    if ($invalidLinks > 0) {
        $this->error("[BLOCK] {$invalidLinks} visible offers have invalid/non-HTTPS partner destinations");
        $blockers++;
    }

    if (Article::published()->count() === 0) {
        $this->warn('[WARN] No published guides yet (optional for initial launch)');
        $warnings++;
    }

    if ($blockers > 0) {
        $this->error("CONTENT GATE FAILED: {$blockers} blocker groups, {$warnings} warnings.");
        return 1;
    }

    $this->info("CONTENT GATE PASSED ({$warnings} warnings).");
    $this->warn('NOT a launch authorization: browser/mobile/keyboard, real media uploads, HTTPS/TLS, backups and restore rehearsal must be verified separately.');

    return 0;
})->purpose('Read-only audit of published CMS content before launch');
