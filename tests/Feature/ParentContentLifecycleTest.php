<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentContentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function makeLinkedOffer(): array
    {
        $brand = Brand::create(['name' => 'Partner', 'slug' => 'partner', 'is_published' => true]);
        $category = Category::create(['name' => 'Education', 'slug' => 'education', 'is_published' => true]);
        $offer = Offer::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Student discount',
            'slug' => 'student-discount',
            'summary' => 'Access to educational services',
            'description' => 'Eligibility details',
            'partner_url' => 'https://example.org/students',
            'is_published' => true,
        ]);

        return [$brand, $category, $offer];
    }

    public function test_brand_with_offers_cannot_be_deleted_but_can_be_unpublished(): void
    {
        [$brand, $category, $offer] = $this->makeLinkedOffer();

        try {
            $brand->delete();
            $this->fail('Deleting a brand with linked offers should be rejected.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('reassign its offers', $exception->getMessage());
        }

        $this->assertDatabaseHas('brands', ['id' => $brand->id]);
        $this->assertDatabaseHas('offers', ['id' => $offer->id]);
        $brand->update(['is_published' => false]);

        $this->get(route('offers.show', $offer->slug))->assertNotFound();
        $this->assertDatabaseHas('offers', ['id' => $offer->id]);
    }

    public function test_category_with_offers_cannot_be_deleted_but_can_be_unpublished(): void
    {
        [$brand, $category, $offer] = $this->makeLinkedOffer();

        try {
            $category->delete();
            $this->fail('Deleting a category with linked offers should be rejected.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('reassign its offers', $exception->getMessage());
        }

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('offers', ['id' => $offer->id]);
        $category->update(['is_published' => false]);

        $this->get(route('offers.show', $offer->slug))->assertNotFound();
        $this->assertDatabaseHas('offers', ['id' => $offer->id]);
    }

    public function test_unlinked_brand_and_category_can_be_deleted(): void
    {
        $brand = Brand::create(['name' => 'Unused Brand', 'slug' => 'unused-brand', 'is_published' => false]);
        $category = Category::create(['name' => 'Unused Category', 'slug' => 'unused-category', 'is_published' => false]);

        $this->assertTrue((bool) $brand->delete());
        $this->assertTrue((bool) $category->delete());
        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
