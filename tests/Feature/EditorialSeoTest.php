<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorialSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_articles_and_pages_are_public_only_when_published(): void
    {
        Article::create([
            'title' => 'Student guide', 'slug' => 'student-guide', 'summary' => 'Learn about discounts',
            'body' => '<script>alert(1)</script> Safe guidance', 'is_published' => true,
        ]);
        Article::create([
            'title' => 'Embargoed', 'slug' => 'embargoed', 'summary' => 'Later',
            'body' => 'Not public', 'is_published' => true, 'published_at' => now()->addDay(),
        ]);
        Article::create([
            'title' => 'Draft', 'slug' => 'draft', 'summary' => 'Not ready',
            'body' => 'Not public', 'is_published' => false,
        ]);
        Page::create(['title' => 'About', 'slug' => 'about', 'body' => 'Our mission', 'is_published' => true]);
        Page::create(['title' => 'Internal', 'slug' => 'internal', 'body' => 'Private', 'is_published' => false]);

        $this->get('/articles')->assertOk()->assertSee('Student guide')->assertDontSee('Embargoed')->assertDontSee('Draft');
        $this->get('/articles/student-guide')->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('rel="canonical"', false);
        $this->get('/articles/embargoed')->assertNotFound();
        $this->get('/articles/draft')->assertNotFound();
        $this->get('/pages/about')->assertOk()->assertSee('Our mission');
        $this->get('/pages/internal')->assertNotFound();
    }

    public function test_sitemap_only_includes_visible_content_and_never_drafts_or_expired_offers(): void
    {
        $brand = Brand::create(['name' => 'Acme', 'slug' => 'acme', 'is_published' => true]);
        $category = Category::create(['name' => 'Software', 'slug' => 'software', 'is_published' => true]);
        foreach ([
            ['slug' => 'active', 'is_published' => true],
            ['slug' => 'draft', 'is_published' => false],
            ['slug' => 'expired', 'is_published' => true, 'ends_at' => now()->subDay()],
        ] as $data) {
            Offer::create(array_merge([
                'brand_id' => $brand->id, 'category_id' => $category->id,
                'title' => 'Offer', 'summary' => 'Summary', 'description' => 'Terms',
                'partner_url' => 'https://example.org/redeem',
            ], $data));
        }
        Article::create([
            'title' => 'Guide', 'slug' => 'guide', 'summary' => 'Summary',
            'body' => 'Body', 'is_published' => true,
        ]);
        Page::create(['title' => 'Terms', 'slug' => 'terms', 'body' => 'Terms', 'is_published' => true]);
        Page::create(['title' => 'Draft', 'slug' => 'draft', 'body' => 'Hidden', 'is_published' => false]);

        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('/offers/active')->assertDontSee('/offers/draft')->assertDontSee('/offers/expired')
            ->assertSee('/articles/guide')->assertSee('/pages/terms')->assertDontSee('/pages/draft');
        $this->get('/search?q=Acme')->assertOk()->assertSee('noindex,follow');
        $this->assertStringContainsString('Sitemap: /sitemap.xml', file_get_contents(public_path('robots.txt')));
    }

    public function test_editorial_admin_resources_are_registered(): void
    {
        $panel = \Filament\Facades\Filament::getPanel('admin');
        $this->assertContains(\App\Filament\Resources\Articles\ArticleResource::class, $panel->getResources());
        $this->assertContains(\App\Filament\Resources\Pages\PageResource::class, $panel->getResources());
    }
}
