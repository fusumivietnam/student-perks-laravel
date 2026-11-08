<?php

namespace Tests\Feature;

use App\Filament\Resources\Offers\Pages\ListOffers;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerLinkReviewTest extends TestCase
{
    use RefreshDatabase;

    private function offer(string $slug, array $extra = []): Offer
    {
        $brand = Brand::firstOrCreate(
            ['slug' => 'partner-review-brand'],
            ['name' => 'Partner Review Brand', 'is_published' => true],
        );
        $category = Category::firstOrCreate(
            ['slug' => 'partner-review-category'],
            ['name' => 'Partner Review Category', 'is_published' => true],
        );

        return Offer::create(array_merge([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Example offer '.$slug,
            'slug' => $slug,
            'summary' => 'Benefit summary',
            'description' => 'Benefit details',
            'partner_url' => 'https://partner.example/student',
            'is_published' => true,
            'offer_type' => 'discount',
        ], $extra));
    }

    public function test_link_review_queue_only_contains_live_unreviewed_or_stale_offers(): void
    {
        $never = $this->offer('never');
        $stale = $this->offer('stale');
        $recent = $this->offer('recent');
        $draft = $this->offer('draft', ['is_published' => false]);
        $expired = $this->offer('expired', ['ends_at' => now()->subDay()]);

        $stale->forceFill(['partner_reviewed_at' => now()->subDays(91)])->save();
        $recent->forceFill(['partner_reviewed_at' => now()->subDay()])->save();

        $this->assertTrue($never->needsPartnerReview());
        $this->assertTrue($stale->fresh()->needsPartnerReview());
        $this->assertFalse($recent->fresh()->needsPartnerReview());

        $this->assertEqualsCanonicalizing(
            [$never->id, $stale->id],
            Offer::partnerReviewDue()->pluck('id')->all(),
        );

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ListOffers::class)
            ->filterTable('partner_review_due', true)
            ->assertCanSeeTableRecords([$never, $stale])
            ->assertCanNotSeeTableRecords([$recent, $draft, $expired]);
    }

    public function test_admin_can_mark_partner_url_manually_reviewed_and_url_changes_clear_timestamp(): void
    {
        $offer = $this->offer('reviewed');
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ListOffers::class)
            ->callTableAction('confirmPartnerReview', $offer)
            ->assertHasNoTableActionErrors();

        $this->assertNotNull($offer->fresh()->partner_reviewed_at);
        $this->assertFalse($offer->fresh()->needsPartnerReview());
        $this->assertSame(0, Offer::partnerReviewDue()->count());
        $this->get('/offers/reviewed')->assertOk();

        $offer->update(['title' => 'Editorial revision']);
        $this->assertNotNull($offer->fresh()->partner_reviewed_at);

        $offer->update(['partner_url' => 'https://another-partner.example/education']);
        $this->assertNull($offer->fresh()->partner_reviewed_at);
        $this->assertSame([$offer->id], Offer::partnerReviewDue()->pluck('id')->all());
        $this->get('/offers/reviewed')->assertOk();
    }

    public function test_non_admin_cannot_access_partner_review_workflow(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->get('/admin/offers')->assertForbidden();
    }
}
