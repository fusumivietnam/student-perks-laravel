<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaunchSeoAndNotFoundTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_public_page_returns_branded_noindex_404_with_recovery_links(): void
    {
        $this->get('/offers/missing-student-offer')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('content="noindex,nofollow"', false)
            ->assertSee('href="'.route('offers.index').'"', false)
            ->assertSee('href="'.route('search').'"', false);
    }

    public function test_brand_page_with_empty_description_uses_nonempty_meta_fallback(): void
    {
        Brand::create(['name' => 'Empty Description Brand', 'slug' => 'empty-description-brand', 'is_published' => true, 'description' => '']);

        $this->get(route('brands.show', 'empty-description-brand'))
            ->assertOk()
            ->assertSee('Explore current education benefits and official partner offers.')
            ->assertDontSee('<meta name="description" content="">', false);
    }

    public function test_brand_and_article_pagination_do_not_create_indexable_duplicate_listing_pages(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Brand::create([
                'name' => sprintf('Published Brand %02d', $i),
                'slug' => sprintf('published-brand-%02d', $i),
                'is_published' => true,
            ]);
        }

        for ($i = 1; $i <= 13; $i++) {
            Article::create([
                'title' => sprintf('Published Guide %02d', $i),
                'slug' => sprintf('published-guide-%02d', $i),
                'summary' => 'A practical guide',
                'body' => 'Student learning information',
                'is_published' => true,
            ]);
        }

        $this->get(route('brands.index'))->assertOk()
            ->assertSee('content="index,follow"', false);

        $this->get(route('brands.index', ['page' => 2]))->assertOk()
            ->assertSee('content="noindex,follow"', false);

        $this->get(route('articles.index'))->assertOk()
            ->assertSee('content="index,follow"', false);

        $this->get(route('articles.index', ['page' => 2]))->assertOk()
            ->assertSee('content="noindex,follow"', false);
    }

    public function test_sitemap_excludes_unpublished_and_future_content(): void
    {
        $brand = Brand::create(['name' => 'Published Partner', 'slug' => 'published-partner', 'is_published' => true]);
        $hiddenBrand = Brand::create(['name' => 'Hidden Partner', 'slug' => 'hidden-partner', 'is_published' => false]);
        $category = Category::create(['name' => 'Software', 'slug' => 'software', 'is_published' => true]);
        Category::create(['name' => 'Hidden Category', 'slug' => 'hidden-category', 'is_published' => false]);

        foreach ([
            ['slug' => 'live-offer', 'is_published' => true],
            ['slug' => 'draft-offer', 'is_published' => false],
            ['slug' => 'scheduled-offer', 'is_published' => true, 'published_at' => now()->addDay()],
            ['slug' => 'hidden-brand-offer', 'is_published' => true, 'brand_id' => $hiddenBrand->id],
        ] as $attributes) {
            Offer::create(array_merge([
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'title' => 'Student Offer',
                'summary' => 'Student savings',
                'description' => 'Terms',
                'partner_url' => 'https://example.org/offer',
            ], $attributes));
        }

        Article::create(['title' => 'Public Guide', 'slug' => 'public-guide', 'summary' => 'Published guide summary', 'body' => 'Public content', 'is_published' => true]);
        Article::create(['title' => 'Future Guide', 'slug' => 'future-guide', 'summary' => 'Future guide summary', 'body' => 'Future content', 'is_published' => true, 'published_at' => now()->addDay()]);
        Page::create(['title' => 'Terms', 'slug' => 'terms', 'body' => 'Terms', 'is_published' => true]);
        Page::create(['title' => 'Hidden Legal Page', 'slug' => 'hidden-legal', 'body' => 'Draft', 'is_published' => false]);

        $this->get(route('sitemap'))->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('/offers/live-offer')
            ->assertSee('/articles/public-guide')
            ->assertSee('/pages/terms')
            ->assertDontSee('/offers/draft-offer')
            ->assertDontSee('/offers/scheduled-offer')
            ->assertDontSee('/offers/hidden-brand-offer')
            ->assertDontSee('/brands/hidden-partner')
            ->assertDontSee('/categories/hidden-category')
            ->assertDontSee('/articles/future-guide')
            ->assertDontSee('/pages/hidden-legal');
    }
}
