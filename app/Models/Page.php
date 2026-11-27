<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Page extends Model
{
    protected $fillable = ['title', 'slug', 'body', 'is_published', 'seo_title', 'seo_description'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'slug_was_published' => 'boolean'];
    }

    public function save(array $options = [])
    {
        if ($this->exists && $this->isDirty(['slug', 'is_published'])) {
            return $this->getConnection()->transaction(function () use ($options) {
                $current = $this->newQuery()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

                foreach (['slug', 'is_published', 'slug_was_published'] as $attribute) {
                    if ($current->getAttribute($attribute) !== $this->getOriginal($attribute)) {
                        throw ValidationException::withMessages([
                            'slug' => 'Publication or URL changed since this record was loaded. Reload before saving.',
                        ]);
                    }
                }

                return parent::save($options);
            });
        }

        return parent::save($options);
    }

    protected static function booted(): void
    {
        static::saving(function (self $record): void {
            // Filament form validation is not enough: imports, jobs and direct
            // Eloquent saves must not take over previously published URLs.
            if ($record->isDirty('slug')
                && $record->getConnection()->table('page_slug_redirects')->where('slug', $record->slug)->exists()) {
                throw new DomainException('This URL slug is reserved for a previously published page.');
            }

            // Track the current slug, not the lifetime publication status of the record.
            if ($record->isDirty('slug')) {
                $record->slug_was_published = (bool) $record->is_published;
            } elseif ($record->isDirty('is_published') && $record->is_published) {
                $record->slug_was_published = true;
            }
        });

        static::updated(function (self $record): void {
            if (! $record->wasChanged('slug') || ! ($record->getOriginal('slug_was_published') || $record->getOriginal('is_published'))) {
                return;
            }

            $record->getConnection()->table('page_slug_redirects')->insert([
                'page_id' => $record->id,
                'slug' => $record->getOriginal('slug'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function publicationStatus(): string
    {
        return $this->is_published ? 'Live' : 'Draft';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
