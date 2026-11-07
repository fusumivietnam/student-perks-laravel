<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivateOfferPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_is_private_and_draft_stays_hidden_from_public_routes(): void
    {
        $brand = Brand::create(['name' => 'Preview Brand', 'slug' => 'preview-brand', 'is_published' => false]);
        $category = Category::create(['name' => 'Preview Category', 'slug' => 'preview-category', 'is_published' => false]);
        $offer = Offer::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Unpublished scholarship',
            'slug' => 'unpublished-scholarship',
            'summary' => 'Unpublished summary',
            'description' => 'Confidential editorial draft',
            'offer_type' => 'free',
            'partner_url' => 'https://example.org/claim',
            'is_published' => false,
        ]);

        $url = route('offers.preview', $offer);

        $this->get($url)->assertRedirect();
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get($url)->assertForbidden();

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get($url)->assertOk()
            ->assertSee('Unpublished scholarship')
            ->assertSee('Confidential editorial draft')
            ->assertSee('Private admin preview of saved content')
            ->assertSee('content="noindex,nofollow,noarchive"', false)
            ->assertSee('External redemption links are disabled in preview.')
            ->assertDontSee('Visit official partner')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertHeader('Cache-Control', 'private, no-store, no-cache, must-revalidate');

        $this->get('/offers/unpublished-scholarship')->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee('/offers/unpublished-scholarship');
    }

    public function test_preview_does_not_persist_or_publish_any_changes(): void
    {
        $brand = Brand::create(['name' => 'Visible Brand', 'slug' => 'visible-brand', 'is_published' => true]);
        $category = Category::create(['name' => 'Visible Category', 'slug' => 'visible-category', 'is_published' => true]);
        $offer = Offer::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Scheduled private offer',
            'slug' => 'scheduled-private-offer',
            'summary' => 'Preview summary',
            'description' => 'Preview description',
            'offer_type' => 'discount',
            'partner_url' => 'https://example.org/claim',
            'is_published' => true,
            'published_at' => now()->addDay(),
        ]);

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get(route('offers.preview', $offer))->assertOk()->assertSee('Scheduled private offer');
        $this->get('/offers/scheduled-private-offer')->assertNotFound();
        $this->assertTrue($offer->fresh()->is_published);
        $this->assertTrue($offer->fresh()->published_at->isFuture());
    }
}
