<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_drafts_and_expired_offers_are_excluded_from_current_offers(): void
    {
        $brand = Brand::create(['name' => 'Example', 'slug' => 'example', 'is_published' => true]);
        $category = Category::create(['name' => 'Software', 'slug' => 'software', 'is_published' => true]);

        foreach ([
            ['slug' => 'active', 'is_published' => true, 'ends_at' => now()->addDay()],
            ['slug' => 'draft', 'is_published' => false, 'ends_at' => null],
            ['slug' => 'expired', 'is_published' => true, 'ends_at' => now()->subDay()],
            ['slug' => 'future', 'is_published' => true, 'starts_at' => now()->addDay()],
        ] as $attributes) {
            Offer::create(array_merge([
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'title' => 'Student offer',
                'summary' => 'A genuine education offer.',
                'description' => 'Terms and eligibility.',
                'partner_url' => 'https://example.com/offer',
            ], $attributes));
        }

        $this->assertSame(['active'], Offer::current()->pluck('slug')->all());
        $this->assertCount(4, $brand->offers);
        $this->assertCount(4, $category->offers);
        $brand->update(['is_published' => false]);
        $this->assertSame([], Offer::current()->pluck('slug')->all());
    }

    public function test_only_explicit_administrators_can_access_filament(): void
    {
        $user = User::factory()->create();
        $panel = \Filament\Facades\Filament::getPanel('admin');

        $this->assertFalse($user->canAccessPanel($panel));
        $user->forceFill(['is_admin' => true])->save();
        $this->assertTrue($user->fresh()->canAccessPanel($panel));
    }

    public function test_brand_and_offer_slugs_are_unique_in_postgresql(): void
    {
        Brand::create(['name' => 'One', 'slug' => 'same']);
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        Brand::create(['name' => 'Two', 'slug' => 'same']);
    }
}
