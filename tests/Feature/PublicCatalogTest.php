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
        $offer = Offer::create(array_merge([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Acme student discount',
            'slug' => $slug,
            'summary' => 'A student learning deal.',
            'description' => 'The official education benefit.',
            'partner_url' => 'https://partner.example/offer',
            'is_published' => true,
        ], $extra));

        // Public CTA expectations use an explicitly reviewed fixture.
        $offer->forceFill(['partner_reviewed_at' => now()])->save();

        return $offer;
    }

    public function test_codespaces_https_preview_uses_forwarded_origin_for_links_and_assets(): void
    {
        config()->set('app.env', 'local');
        config()->set('app.url', 'https://sample-8080.app.github.dev');

        (new \App\Providers\AppServiceProvider($this->app))->boot();

        $this->get('/')->assertOk()
            ->assertSee('href="https://sample-8080.app.github.dev/offers"', false)
            ->assertSee('action="https://sample-8080.app.github.dev/search"', false)
            ->assertDontSee('http://localhost:8080/offers', false);

        $this->assertSame(
            'https://sample-8080.app.github.dev/build/assets/demo.css',
            asset('build/assets/demo.css'),
        );
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

    public function test_future_published_at_offer_is_hidden_from_all_public_routes(): void
    {
        [$brand, $category] = $this->catalog();
        $this->offer($brand, $category, 'scheduled', [
            'title' => 'Scheduled promotion',
            'published_at' => now()->addDay(),
        ]);

        $this->get('/')->assertOk()->assertDontSee('Scheduled promotion');
        $this->get('/offers')->assertOk()->assertDontSee('Scheduled promotion');
        $this->get('/brands/acme')->assertOk()->assertDontSee('Scheduled promotion');
        $this->get('/categories/software')->assertOk()->assertDontSee('Scheduled promotion');
        $this->get('/search?q=Scheduled')->assertOk()->assertDontSee('Scheduled promotion');
        $this->get('/offers/scheduled')->assertNotFound();
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('/offers/scheduled');

        Offer::where('slug', 'scheduled')->update(['published_at' => now()->subMinute()]);
        $this->get('/offers/scheduled')->assertOk()->assertSee('Scheduled promotion');
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

    public function test_public_cards_have_accessible_offer_links_and_home_search(): void
    {
        [$brand, $category] = $this->catalog();
        $this->offer($brand, $category, 'active');

        $this->get('/')->assertOk()
            ->assertSee('Your student status deserves')
            ->assertSee('aria-label="Main navigation"', false)
            ->assertSee('aria-labelledby="latest-offers-title"', false)
            ->assertSee('action="'.route('search').'"', false)
            ->assertSee('href="'.route('offers.show', 'active').'"', false)
            ->assertSee('Acme student discount');

        $this->get('/offers')->assertOk()
            ->assertSee('View offer details')
            ->assertSee('href="'.route('offers.show', 'active').'"', false);

        $this->get('/brands')->assertOk()
            ->assertSee('Discover brands')
            ->assertSee('href="'.route('brands.show', 'acme').'"', false);
    }

    public function test_offer_detail_renders_structured_eligibility_and_partner_cta(): void
    {
        [$brand, $category] = $this->catalog();
        $this->offer($brand, $category, 'active', [
            'offer_value' => 'Save 20%',
            'eligibility' => 'Current students only',
            'redemption_steps' => 'Verify on the provider website',
            'ends_at' => now()->addWeek(),
        ]);

        $this->get('/offers/active')->assertOk()
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('aria-label="Offer details and partner link"', false)
            ->assertSee('About this offer')
            ->assertSee('Who is eligible?')
            ->assertSee('Current students only')
            ->assertSee('How to redeem')
            ->assertSee('Verify on the provider website')
            ->assertSee('Save 20%')
            ->assertSee('rel="noopener noreferrer sponsored"', false)
            ->assertSee('href="'.route('offers.index').'"', false);
    }

    public function test_search_route_retains_its_context_during_filtering_and_reset(): void
    {
        [$brand, $category] = $this->catalog();
        $this->offer($brand, $category, 'search-context');

        $search = $this->get('/search?q=Acme&brand=acme&category=software&sort=ending');
        $search->assertOk()
            ->assertSee('<h1', false)
            ->assertSee('Search student offers')
            ->assertSee('Acme student discount')
            ->assertSee('content="noindex,follow"', false)
            ->assertSee('action="'.route('search').'"', false)
            ->assertSee('href="'.route('search').'"', false)
            ->assertSee('Search: Acme')
            ->assertSee('Brand: Acme Learning');

        $this->get('/search?q=not-found')
            ->assertOk()
            ->assertSee('No offers found')
            ->assertSee('href="'.route('search').'"', false);

        $directory = $this->get('/offers?brand=acme');
        $directory->assertOk()
            ->assertSee('action="'.route('offers.index').'"', false)
            ->assertSee('href="'.route('offers.index').'"', false)
            ->assertDontSee('Search student offers');
    }

    public function test_offer_search_shows_active_filters_and_can_reset_them(): void
    {
        [$brand, $category] = $this->catalog();
        $this->offer($brand, $category, 'active');

        $this->get('/offers?q=Acme&brand=acme&category=software&sort=ending')
            ->assertOk()
            ->assertSee('Active filters:')
            ->assertSee('Search: Acme')
            ->assertSee('Brand: Acme Learning')
            ->assertSee('Category: Software')
            ->assertSee('Ending soon')
            ->assertSee('Reset all')
            ->assertSee('Acme student discount')
            ->assertSee('href="'.route('offers.index').'"', false);

        $this->get('/offers?q=nomatch')
            ->assertOk()
            ->assertSee('No offers found')
            ->assertSee('See all offers');
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

    public function test_offer_type_filter_returns_only_matching_current_offers(): void
    {
        [$brand, $category] = $this->catalog();
        $this->offer($brand, $category, 'discount', [
            'title' => 'Discount plan',
            'offer_type' => 'discount',
        ]);
        $this->offer($brand, $category, 'free', [
            'title' => 'Free plan',
            'offer_type' => 'free',
        ]);
        $this->offer($brand, $category, 'hidden-type', [
            'title' => 'Hidden trial',
            'offer_type' => 'trial',
            'is_published' => false,
        ]);

        $this->get('/offers?type=free')
            ->assertOk()
            ->assertSee('Free plan')
            ->assertDontSee('Discount plan')
            ->assertDontSee('Hidden trial')
            ->assertSee('Type: Free')
            ->assertSee('value="free" selected', false)
            ->assertDontSee('value="trial"', false)
            ->assertSee('name="type"', false);

        $this->get('/search?type=discount&q=Discount')
            ->assertOk()
            ->assertSee('Discount plan')
            ->assertDontSee('Free plan');

        $this->get('/offers?type=unknown')
            ->assertOk()
            ->assertSee('No offers found');
    }

    public function test_offer_type_filter_is_safe_for_invalid_query_shapes_and_combines_with_category(): void
    {
        [$brand, $category] = $this->catalog();
        $this->offer($brand, $category, 'active', [
            'title' => 'Active grant',
            'offer_type' => 'grant',
        ]);

        $this->get('/offers?type[]=grant')
            ->assertOk()
            ->assertSee('Active grant');

        $this->get('/offers?type=grant&category=software')
            ->assertOk()
            ->assertSee('Active grant');

        $this->get('/offers?type=grant&category=unknown')
            ->assertOk()
            ->assertSee('No offers found');
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
