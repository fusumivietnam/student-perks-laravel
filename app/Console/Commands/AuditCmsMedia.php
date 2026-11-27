<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Brand;
use App\Support\PublicCmsImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AuditCmsMedia extends Command
{
    protected $signature = 'media:audit';

    protected $description = 'Read-only inventory of CMS image references and stored media';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $references = [];
        $invalid = 0;
        $missing = 0;
        $shared = 0;
        $unreferenced = 0;
        $bytes = 0;
        $files = 0;

        $this->info('CMS media inventory (read-only)');

        foreach ([
            ['brands', Brand::query(), 'logo_path'],
            ['articles', Article::query(), 'cover_path'],
        ] as [$directory, $query, $attribute]) {
            foreach ($query->whereNotNull($attribute)->select(['id', $attribute])->lazyById() as $record) {
                $path = $record->{$attribute};
                // Keep even invalid references in the inventory, so a file
                // referenced by a draft or legacy record is not called orphaned.
                $references[$path] = ($references[$path] ?? 0) + 1;

                if (! PublicCmsImage::validPath($path, $directory)) {
                    $this->warn("[INVALID] {$directory} #{$record->id}");
                    $invalid++;

                    continue;
                }

                if (! $disk->fileExists($path)) {
                    $this->warn("[MISSING] {$directory} #{$record->id}");
                    $missing++;
                }
            }
        }

        foreach ($references as $count) {
            if ($count > 1) {
                $shared++;
            }
        }

        foreach (['brands', 'articles'] as $directory) {
            foreach ($disk->allFiles($directory) as $path) {
                $files++;
                $bytes += $disk->size($path);

                if (! isset($references[$path])) {
                    $unreferenced++;
                }
            }
        }

        $this->table(['Metric', 'Count'], [
            ['CMS references', array_sum($references)],
            ['Distinct referenced paths', count($references)],
            ['Invalid references', $invalid],
            ['Missing files (references)', $missing],
            ['Shared paths', $shared],
            ['Stored files', $files],
            ['Unreferenced files (candidates only)', $unreferenced],
            ['Stored bytes', $bytes],
        ]);

        $this->line('Includes drafts and published records. Scope: public disk brands/ and articles/.');
        $this->warn('Unreferenced files may be retained intentionally. No files or records were changed.');

        return self::SUCCESS;
    }
}
