<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicMediaDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_brand_logos_are_reused_across_public_catalog_and_missing_files_fall_back_to_initials(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('brands/published-logo.png', 'example logo');
        $brand = Brand::create([
            'name' => 'Demo Academy', 'slug' => 'demo-academy',
            'logo_path' => 'brands/published-logo.png', 'is_published' => true,
        ]);
        $missingBrand = Brand::create([
            'name' => 'Missing Media', 'slug' => 'missing-media',
            'logo_path' => 'brands/does-not-exist.png', 'is_published' => true,
        ]);
        $category = Category::create([
            'name' => 'Learning', 'slug' => 'learning', 'is_published' => true,
        ]);
        Offer::create([
            'brand_id' => $brand->id, 'category_id' => $category->id,
            'title' => 'Student learning benefit', 'slug' => 'student-learning-benefit',
            'summary' => 'Student offer', 'description' => 'Education benefit terms',
            'partner_url' => 'https://example.org/student', 'is_published' => true,
        ]);

        foreach (['/', '/brands', '/offers', '/offers/student-learning-benefit'] as $path) {
            $this->get($path)->assertOk()
                ->assertSee('brands/published-logo.png', false);
        }

        $this->get('/brands')->assertOk()
            ->assertSee('Missing Media')
            ->assertSee('MI')
            ->assertDontSee('brands/does-not-exist.png', false);

        $missingBrand->update(['is_published' => false]);
        $this->get('/brands')->assertDontSee('Missing Media');
    }

    public function test_published_article_cover_appears_in_listing_and_detail_with_missing_media_fallback(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('articles/sample-cover.png', 'example cover');
        Article::create([
            'title' => 'Covered guide', 'slug' => 'covered-guide',
            'summary' => 'Useful information', 'body' => 'Guide content',
            'cover_path' => 'articles/sample-cover.png', 'is_published' => true,
        ]);
        Article::create([
            'title' => 'Missing cover guide', 'slug' => 'missing-cover-guide',
            'summary' => 'Useful information', 'body' => 'Guide content',
            'cover_path' => 'articles/unavailable.png', 'is_published' => true,
        ]);
        Article::create([
            'title' => 'Draft guide', 'slug' => 'draft-guide',
            'summary' => 'Unpublished', 'body' => 'Draft',
            'cover_path' => 'articles/draft-image.png', 'is_published' => false,
        ]);

        $this->get('/articles')->assertOk()
            ->assertSee('articles/sample-cover.png', false)
            ->assertSee('Covered guide')
            ->assertSee('Missing cover guide')
            ->assertDontSee('articles/unavailable.png', false)
            ->assertDontSee('articles/draft-image.png', false);

        $this->get('/articles/covered-guide')->assertOk()
            ->assertSee('articles/sample-cover.png', false);
        $this->get('/articles/missing-cover-guide')->assertOk()
            ->assertDontSee('articles/unavailable.png', false);
        $this->get('/articles/draft-guide')->assertNotFound();
    }
}
