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
 * Explicit, non-destructive bootstrap of editorial drafts. Never runs during
 * app startup, preview updates or migrations.
 */
Artisan::command('launch:prepare-pages', function (): int {
    $created = 0;
    $skipped = 0;

    foreach (config('launch-pages', []) as $slug => $template) {
        if (Page::where('slug', $slug)->exists()) {
            $this->line("[SKIP] /pages/{$slug} already exists; existing content and publication state are unchanged.");
            $skipped++;

            continue;
        }

        Page::create([
            'title' => $template['title'],
            'slug' => $slug,
            'body' => $template['body'],
            'seo_description' => $template['seo_description'],
            'is_published' => false,
        ]);

        $this->info("[DRAFT] /pages/{$slug} created (unpublished).");
        $created++;
    }

    $this->info("Editorial templates: {$created} created, {$skipped} preserved.");
    $this->warn('Review and replace [BRACKETED FIELDS], verify legal/business facts, and approve content in Filament → Pages before publishing.');
    $this->warn('Partner URLs are not modified or marked reviewed.');

    return 0;
})->purpose('Create only missing unpublished editorial/legal page drafts; preserve all existing content');

/**
 * Read-only content gate. Browser, TLS, media durability and backup checks are separate.
 */
Artisan::command('launch:check', function (): int {
    $blockers = 0;
    $warnings = 0;

    $this->info('Student Perks Hub — content readiness (read-only)');

    foreach (['about', 'privacy', 'terms', 'contact', 'disclaimer'] as $slug) {
        $page = Page::published()->where('slug', $slug)->first(['slug', 'body']);

        if (! $page) {
            $draftExists = Page::where('slug', $slug)->exists();
            $this->error("[BLOCK] Published legal/information page missing: /pages/{$slug}");
            $this->line($draftExists
                ? "       Action: review the existing draft in Filament → Pages, then publish when approved."
                : "       Action: create /pages/{$slug} in Filament → Pages, review its content, then publish.");
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
        foreach (Offer::partnerReviewDue()->with('brand')->orderBy('id')->get() as $offer) {
            $this->line(sprintf(
                '       Offer #%d | %s | %s | %s',
                $offer->id,
                $offer->brand?->name ?? 'Unknown brand',
                $offer->title,
                route('offers.show', $offer->slug),
            ));
        }
        $this->line('       Action: open each official partner URL, verify current terms, then use "Mark link reviewed" in Filament → Offers. Never confirm unverified links.');
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
