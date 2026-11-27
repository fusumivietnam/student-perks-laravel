<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CmsMediaAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_includes_drafts_shared_missing_invalid_and_retained_files_without_mutation(): void
    {
        Storage::fake('public');
        $disk = Storage::disk('public');
        $disk->put('brands/shared.png', '123');
        $disk->put('articles/retained.webp', '4567');
        $disk->put('articles/draft.jpg', '89');

        foreach ([
            ['published-brand', 'brands/shared.png', true],
            ['draft-brand', 'brands/shared.png', false],
            ['missing-brand', 'brands/missing.png', true],
            ['invalid-brand', 'articles/draft.jpg', false],
        ] as [$slug, $path, $published]) {
            Brand::create([
                'name' => $slug,
                'slug' => $slug,
                'logo_path' => $path,
                'is_published' => $published,
            ]);
        }

        Article::create([
            'title' => 'Draft guide',
            'slug' => 'draft-guide',
            'summary' => 'Summary',
            'body' => 'Body',
            'cover_path' => 'articles/draft.jpg',
            'is_published' => false,
        ]);

        $brands = Brand::orderBy('id')->get()->toArray();
        $articles = Article::orderBy('id')->get()->toArray();

        $this->artisan('media:audit')
            ->expectsOutputToContain('[INVALID]')
            ->expectsOutputToContain('[MISSING]')
            ->expectsTable(['Metric', 'Count'], [
                ['CMS references', 5],
                ['Distinct referenced paths', 3],
                ['Invalid references', 1],
                ['Missing files (references)', 1],
                ['Shared paths', 2],
                ['Stored files', 3],
                ['Unreferenced files (candidates only)', 1],
                ['Stored bytes', 9],
            ])
            ->assertSuccessful();

        $this->assertSame($brands, Brand::orderBy('id')->get()->toArray());
        $this->assertSame($articles, Article::orderBy('id')->get()->toArray());
        $this->assertSame('123', $disk->get('brands/shared.png'));
        $this->assertSame('4567', $disk->get('articles/retained.webp'));
        $this->assertSame('89', $disk->get('articles/draft.jpg'));
        $disk->assertMissing('brands/missing.png');
    }
}
