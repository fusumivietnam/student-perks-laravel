<?php

use App\Models\Article;
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
        if (! Page::published()->where('slug', $slug)->exists()) {
            $this->error("[BLOCK] Published legal/information page missing: /pages/{$slug}");
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
