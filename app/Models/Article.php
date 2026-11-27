<?php

namespace App\Models;

use DomainException;
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
                && $record->getConnection()->table('article_slug_redirects')->where('slug', $record->slug)->exists()) {
                throw new DomainException('This URL slug is reserved for a previously published article.');
            }
        });

        static::updated(function (self $record): void {
            if (! $record->wasChanged('slug') || ! $record->getOriginal('is_published')) {
                return;
            }

            $record->getConnection()->table('article_slug_redirects')->insert([
                'article_id' => $record->id,
                'slug' => $record->getOriginal('slug'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
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
