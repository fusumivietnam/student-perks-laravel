<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DomainException;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::deleting(function (self $record): void {
            if ($record->offers()->exists()) {
                throw new DomainException('Cannot delete a category while offers are linked. Unpublish it or reassign its offers first.');
            }
        });
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
