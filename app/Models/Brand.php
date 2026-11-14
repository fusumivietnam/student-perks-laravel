<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DomainException;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'website_url', 'logo_path', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::deleting(function (self $record): void {
            if ($record->offers()->exists()) {
                throw new DomainException('Cannot delete a brand while offers are linked. Unpublish it or reassign its offers first.');
            }
        });
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
