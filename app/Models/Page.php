<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Page extends Model
{
    protected $fillable = ['title', 'slug', 'body', 'is_published', 'seo_title', 'seo_description'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::updated(function (self $record): void {
            if (! $record->wasChanged('slug') || ! $record->getOriginal('is_published')) {
                return;
            }

            DB::table('page_slug_redirects')->insert([
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
