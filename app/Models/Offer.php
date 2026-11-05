<?php

namespace App\Models;

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
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->whereHas('brand', fn (Builder $q) => $q->where('is_published', true))
            ->whereHas('category', fn (Builder $q) => $q->where('is_published', true))
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }
}
