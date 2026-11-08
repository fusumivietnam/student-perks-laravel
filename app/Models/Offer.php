<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

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
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (self $offer): void {
            if (! $offer->wasChanged('slug') || ! $offer->getOriginal('is_published')) {
                return;
            }

            DB::table('offer_slug_redirects')->insert([
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

        if ($this->ends_at && $this->ends_at->isPast()) {
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
