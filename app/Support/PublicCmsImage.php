<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class PublicCmsImage
{
    /**
     * Resolve only verified paths within the expected public media directory.
     * CMS image paths are untrusted if content was imported or edited outside
     * Filament; never produce a URL for a sibling folder or path traversal.
     */
    public static function url(?string $path, string $directory): ?string
    {
        if (! self::validPath($path, $directory)) {
            return null;
        }

        $disk = Storage::disk('public');

        return $disk->exists($path) ? $disk->url($path) : null;
    }

    public static function validPath(?string $path, string $directory): bool
    {
        return in_array($directory, ['brands', 'articles'], true)
            && is_string($path)
            && preg_match('/\A(?:brands|articles)\/[A-Za-z0-9_\/-]+\.(?:jpe?g|png|webp)\z/iD', $path)
            && str_starts_with($path, $directory.'/')
            && ! str_contains($path, '//')
            && ! str_contains($path, '..');
    }
}
