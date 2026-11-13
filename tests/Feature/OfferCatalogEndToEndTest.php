<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferCatalogEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private function createOffer(array $attributes = []): Offer
    {
        $brand = Brand::create([
            'name' => 'E2E Partner',
            'slug' => 'e2e-partner',
            'is_published' => true,
        ]);
        $category = Category::create([
            'name' => 'E2E Learning',
            'slug' => 'e2e-learning',
            'is_published' => true,
        ]);

        return Offer::create(array_merge([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Unique E2E Student Discount',
            'slug' => 'unique-e2e-discount',
            'summary' => 'A student-only offer for end-to-end verification.',
            'description' => 'Student terms apply.',
            'eligibility' => 'Verified students.',
            'redemption_steps' => 'Follow the partner instructions.',
            'offer_type' => 'discount',
            'partner_url' => 'https://example.com/e2e-offer',
            'is_published' => false,
        ], $attributes));
    }

    public function test_admin_preview_and_public_offer_follow_publication_lifecycle(): void
    {
        $offer = $this->createOffer();

        $this->get(route('offers.show', $offer->slug))->assertNotFound();
        $this->get(route('offers.index'))->assertDontSee($offer->title);
        $this->get(route('offers.preview', $offer))->assertForbidden();

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)
            ->get(route('offers.preview', $offer))
            ->assertOk()
            ->assertSee('Private admin preview')
            ->assertSee($offer->title)
            ->assertDontSee('Visit official partner');

        $offer->update(['is_published' => true]);

        $this->get(route('offers.index'))->assertOk()->assertSee($offer->title);
        $this->get(route('offers.show', $offer->slug))->assertOk()
            ->assertSee($offer->title)
            ->assertSee('awaiting editorial review')
            ->assertDontSee('Visit official partner');

        $offer->forceFill(['partner_reviewed_at' => now()])->save();
        $this->get(route('offers.show', $offer->slug))->assertOk()
            ->assertSee('Visit official partner')
            ->assertSee('https://example.com/e2e-offer');

        $offer->update(['partner_url' => 'https://example.org/new-terms']);
        $this->assertNull($offer->fresh()->partner_reviewed_at);
        $this->get(route('offers.show', $offer->slug))->assertOk()
            ->assertSee('awaiting editorial review')
            ->assertDontSee('Visit official partner');

        $offer->update(['is_published' => false]);
        $this->get(route('offers.show', $offer->slug))->assertNotFound();
        $this->get(route('offers.index'))->assertDontSee($offer->title);
    }

    public function test_public_catalog_respects_scheduling_and_parent_visibility(): void
    {
        $offer = $this->createOffer(['is_published' => true]);

        $this->get(route('offers.show', $offer->slug))->assertOk();

        $offer->update(['published_at' => now()->addDay()]);
        $this->get(route('offers.show', $offer->slug))->assertNotFound();

        $offer->update(['published_at' => now()->subDay(), 'starts_at' => now()->addHour()]);
        $this->get(route('offers.show', $offer->slug))->assertNotFound();

        $offer->update(['starts_at' => now()->subHour(), 'ends_at' => now()->subMinute()]);
        $this->get(route('offers.show', $offer->slug))->assertNotFound();

        $offer->update(['ends_at' => now()->addDay()]);
        $this->get(route('offers.show', $offer->slug))->assertOk();

        $offer->brand->update(['is_published' => false]);
        $this->get(route('offers.show', $offer->slug))->assertNotFound();
        $this->get(route('offers.index'))->assertDontSee($offer->title);

        $offer->brand->update(['is_published' => true]);
        $offer->category->update(['is_published' => false]);
        $this->get(route('offers.show', $offer->slug))->assertNotFound();

        $offer->category->update(['is_published' => true]);
        $this->get(route('offers.show', $offer->slug))->assertOk();
    }

    public function test_published_offer_slug_redirect_only_resolves_to_current_offers(): void
    {
        $offer = $this->createOffer(['is_published' => true]);
        $oldSlug = $offer->slug;

        $offer->update(['slug' => 'renamed-e2e-discount']);

        $this->get(route('offers.show', $oldSlug))
            ->assertRedirect(route('offers.show', $offer->slug))
            ->assertStatus(301);
        $this->get(route('offers.show', $offer->slug))->assertOk();

        $offer->update(['is_published' => false]);

        $this->get(route('offers.show', $oldSlug))->assertNotFound();
        $this->get(route('offers.show', $offer->slug))->assertNotFound();
    }
}
