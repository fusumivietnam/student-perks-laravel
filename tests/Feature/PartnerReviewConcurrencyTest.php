<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerReviewConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_destination_cannot_receive_a_review(): void
    {
        $offer = $this->createOffer();
        $reviewer = $offer->fresh();

        $offer->update(['partner_url' => 'https://example.org/new']);

        $this->assertFalse($reviewer->confirmPartnerReview());
        $this->assertNull($offer->fresh()->partner_reviewed_at);
        $this->assertTrue($offer->fresh()->confirmPartnerReview());
        $this->assertTrue($offer->fresh()->hasReviewedPartnerDestination());
    }

    public function test_a_second_stale_reviewer_cannot_extend_a_fresh_review(): void
    {
        $offer = $this->createOffer();
        $first = $offer->fresh();
        $second = $offer->fresh();

        $this->assertTrue($first->confirmPartnerReview());
        $reviewedAt = $offer->fresh()->partner_reviewed_at;

        $this->travel(1)->hours();

        $this->assertFalse($second->confirmPartnerReview());
        $this->assertTrue($reviewedAt->equalTo($offer->fresh()->partner_reviewed_at));
    }

    private function createOffer(): Offer
    {
        $brand = Brand::create([
            'name' => 'Review brand',
            'slug' => 'review-brand',
            'is_published' => true,
        ]);
        $category = Category::create([
            'name' => 'Review category',
            'slug' => 'review-category',
            'is_published' => true,
        ]);

        return Offer::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Review offer',
            'slug' => 'review-offer',
            'summary' => 'Summary',
            'description' => 'Description',
            'offer_type' => 'discount',
            'partner_url' => 'https://example.org/original',
            'is_published' => true,
        ]);
    }
}
