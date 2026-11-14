<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferMobileLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_offer_partner_cta_appears_before_long_form_details_in_document_order(): void
    {
        $brand = Brand::create(['name' => 'Very Long Student Learning Partner', 'slug' => 'learning-partner', 'is_published' => true]);
        $category = Category::create(['name' => 'Learning', 'slug' => 'learning', 'is_published' => true]);
        $offer = Offer::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Mobile Learning Discount',
            'slug' => 'mobile-learning-discount',
            'summary' => 'Affordable learning for students',
            'description' => str_repeat('Long eligibility information. ', 30),
            'offer_value' => 'Extra long discount label for narrow screens',
            'partner_url' => 'https://example.org/learning',
            'is_published' => true,
        ]);
        $offer->forceFill(['partner_reviewed_at' => now()])->save();

        $this->get(route('offers.show', $offer->slug))
            ->assertOk()
            ->assertSeeInOrder([
                'Mobile Learning Discount',
                'aria-label="Offer details and partner link"',
                'Visit official partner',
                'About this offer',
            ], false)
            ->assertSee('lg:col-start-2 lg:row-start-1 lg:row-span-2', false)
            ->assertSee('break-words text-3xl', false);

        $this->get(route('offers.index'))
            ->assertOk()
            ->assertSee('break-words text-xl', false)
            ->assertSee('Extra long discount label for narrow screens');
    }
}
