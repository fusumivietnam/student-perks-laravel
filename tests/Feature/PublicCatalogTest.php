<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function catalog(bool $published = true): array
    {
        $brand = Brand::create(['name' => 'Acme Learning', 'slug' => 'acme', 'is_published' => $published]);
        $category = Category::create(['name' => 'Software', 'slug' => 'software', 'is_published' => true]);

        return [$brand, $category];
    }

    private function offer(Brand $brand, Category $category, string $slug, array $extra = []): Offer
    {
        return Offer::create(array_merge([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Acme student discount',
            'slug' => $slug,
            'summary' => 'A student learning deal.',
            'description' => 'The official education benefit.',
            'partner_url' => 'https://partner.example/offer',
            'is_published' => true,
        ], $extra));
    }

    public function test_homepage_and_listing_show_only_current_published_offers(): void
    {
        [$brand, $category] = $this->catalog();
        $this->offer($brand, $category, 'active');
        $this->offer($brand, $category, 'draft', ['is_published' => false, 'title' => 'Draft offer']);
        $this->offer($brand, $category, 'expired', ['ends_at' => now()->subDay(), 'title' => 'Expired offer']);

        $this->get('/')->assertOk()->assertSee('Acme student discount')->assertDontSee('Draft offer')->assertDontSee('Expired offer');
        $this->get('/offers')->assertOk()->assertSee('Acme student discount')->assertDontSee('Draft offer')->assertDontSee('Expired offer');
        $this->get('/offers/draft')->assertNotFound();
        $this->get('/offers/expired')->assertNotFound();
    }

    public function test_search_and_brand_category_filtering_use_database_content(): void
    {
        [$brand, $category] = $this->catalog();
        $this->offer($brand, $category, 'active');

        $this->get('/search?q=Acme')->assertOk()->assertSee('Acme student discount');
        $this->get('/offers?brand=unknown')->assertOk()->assertSee('No offers found');
        $this->get('/offers?category=software')->assertOk()->assertSee('Acme student discount');
        $this->get('/brands/acme')->assertOk()->assertSee('Acme student discount');
        $this->get('/categories/software')->assertOk()->assertSee('Acme student discount');
        $this->get('/offers/active')->assertOk()->assertSee('https://partner.example/offer');
    }

    public function test_footer_links_only_to_published_pages(): void
    {
        Page::create(['title' => 'Privacy policy', 'slug' => 'privacy', 'body' => 'Reviewed policy', 'is_published' => true]);
        Page::create(['title' => 'Terms', 'slug' => 'terms', 'body' => 'Unreviewed', 'is_published' => false]);

        $this->get('/')->assertOk()
            ->assertSee('href="'.route('pages.show', 'privacy').'"', false)
            ->assertDontSee('href="'.route('pages.show', 'terms').'"', false)
            ->assertDontSee('href="'.route('pages.show', 'about').'"', false);

        $this->get('/pages/privacy')->assertOk()->assertSee('Reviewed policy');
        $this->get('/pages/terms')->assertNotFound();
    }

    public function test_malformed_search_parameters_do_not_trigger_server_errors(): void
    {
        [$brand, $category] = $this->catalog();
        $this->offer($brand, $category, 'active');

        $this->get('/search?q[]=wrong&brand[]=acme&category[]=software&sort[]=ending')
            ->assertOk()
            ->assertSee('Acme student discount');
    }

    public function test_hidden_brands_never_publish_their_offers(): void
    {
        [$brand, $category] = $this->catalog(false);
        $this->offer($brand, $category, 'hidden');

        $this->get('/')->assertOk()->assertDontSee('Acme student discount');
        $this->get('/brands/acme')->assertNotFound();
        $this->get('/offers/hidden')->assertNotFound();
    }
}
