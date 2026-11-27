<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends Model
{
    protected $fillable = [
        'brand_id', 'category_id', 'title', 'slug', 'summary', 'description',
        'offer_type', 'offer_value', 'eligibility', 'redemption_steps', 'partner_url',
        'is_published', 'starts_at', 'ends_at', 'published_at', 'seo_title', 'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
            'partner_reviewed_at' => 'datetime',
        ];
    }

    public function save(array $options = [])
    {
        if ($this->exists && $this->isDirty('slug')) {
            return $this->getConnection()->transaction(fn () => parent::save($options));
        }

        return parent::save($options);
    }

    protected static function booted(): void
    {
        static::saving(function (self $record): void {
            // Filament form validation is not enough: imports, jobs and direct
            // Eloquent saves must not take over previously published URLs.
            if ($record->isDirty('slug')
                && $record->getConnection()->table('offer_slug_redirects')->where('slug', $record->slug)->exists()) {
                throw new DomainException('This URL slug is reserved for a previously published offer.');
            }
        });

        static::updating(function (self $offer): void {
            if ($offer->isDirty('partner_url')) {
                $offer->partner_reviewed_at = null;
            }
        });

        static::updated(function (self $offer): void {
            if (! $offer->wasChanged('slug') || ! $offer->getOriginal('is_published')) {
                return;
            }

            $offer->getConnection()->table('offer_slug_redirects')->insert([
                'offer_id' => $offer->id,
                'slug' => $offer->getOriginal('slug'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function publicationStatus(): string
    {
        if (! $this->is_published) {
            return 'Draft';
        }

        if ($this->ends_at && $this->ends_at->lte(now())) {
            return 'Expired';
        }

        if (($this->published_at && $this->published_at->isFuture())
            || ($this->starts_at && $this->starts_at->isFuture())) {
            return 'Scheduled';
        }

        if (! $this->brand?->is_published || ! $this->category?->is_published) {
            return 'Hidden by brand/category';
        }

        return 'Live';
    }

    public function canPublishNow(): bool
    {
        return ! $this->is_published
            && (! $this->ends_at || $this->ends_at->isFuture())
            && (! $this->starts_at || ! $this->starts_at->isFuture())
            && (! $this->published_at || ! $this->published_at->isFuture())
            && (bool) $this->brand?->is_published
            && (bool) $this->category?->is_published;
    }

    public function scopeWithPublicationStatus(Builder $query, string $status): Builder
    {
        return match ($status) {
            'draft' => $query->where('is_published', false),
            'live' => $query->current(),
            'expired' => $query->where('is_published', true)
                ->whereNotNull('ends_at')->where('ends_at', '<=', now()),
            'scheduled' => $query->where('is_published', true)
                ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->where(fn (Builder $q) => $q->where('published_at', '>', now())
                    ->orWhere('starts_at', '>', now())),
            'hidden' => $query->where('is_published', true)
                ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn (Builder $q) => $q->whereDoesntHave('brand', fn (Builder $b) => $b->where('is_published', true))
                    ->orWhereDoesntHave('category', fn (Builder $c) => $c->where('is_published', true))),
            default => $query,
        };
    }

    public function hasMissingEditorialDetails(): bool
    {
        return trim((string) $this->eligibility) === ''
            || trim((string) $this->redemption_steps) === '';
    }

    public function scopeNeedsEditorialReview(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNull('eligibility')
                ->orWhereRaw("BTRIM(eligibility, CHR(32) || CHR(9) || CHR(10) || CHR(13) || CHR(11)) = ''")
                ->orWhereNull('redemption_steps')
                ->orWhereRaw("BTRIM(redemption_steps, CHR(32) || CHR(9) || CHR(10) || CHR(13) || CHR(11)) = ''");
        });
    }

    public function needsPartnerReview(): bool
    {
        return ! $this->partner_reviewed_at
            || $this->partner_reviewed_at->lte(now()->subDays(90));
    }

    public function hasValidPartnerDestination(): bool
    {
        return (bool) filter_var($this->partner_url, FILTER_VALIDATE_URL)
            && parse_url($this->partner_url, PHP_URL_SCHEME) === 'https';
    }

    public function hasReviewedPartnerDestination(): bool
    {
        return $this->hasValidPartnerDestination() && ! $this->needsPartnerReview();
    }

    public function partnerCtaStatus(): string
    {
        if (! $this->hasValidPartnerDestination()) {
            return 'Invalid URL';
        }

        return $this->needsPartnerReview() ? 'Review due' : 'Ready';
    }

    public function scopePartnerReviewDue(Builder $query): Builder
    {
        return $query->current()->where(function (Builder $q): void {
            $q->whereNull('partner_reviewed_at')
                ->orWhere('partner_reviewed_at', '<=', now()->subDays(90));
        });
    }

    public function scopeExpiringSoon(Builder $query): Builder
    {
        return $query->current()
            ->where('ends_at', '>', now())
            ->where('ends_at', '<=', now()->addDays(7));
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->whereHas('brand', fn (Builder $q) => $q->where('is_published', true))
            ->whereHas('category', fn (Builder $q) => $q->where('is_published', true))
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }
}
