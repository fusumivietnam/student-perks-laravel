<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'title', 'slug', 'summary', 'body', 'cover_path', 'is_published',
        'published_at', 'seo_title', 'seo_description',
    ];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'published_at' => 'datetime'];
    }

    public function publicationStatus(): string
    {
        if (! $this->is_published) {
            return 'Draft';
        }

        return $this->published_at?->isFuture() ? 'Scheduled' : 'Live';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(fn (Builder $q) => $q->whereNull('published_at')
                ->orWhere('published_at', '<=', now()));
    }
}
