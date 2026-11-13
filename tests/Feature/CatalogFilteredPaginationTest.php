<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogFilteredPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_retains_combined_filters_and_stable_results_across_pages(): void
    {
        $brand = Brand::create([
            'name' => 'Learning Partner',
            'slug' => 'learning-partner',
            'is_published' => true,
        ]);
        $category = Category::create([
            'name' => 'Education',
            'slug' => 'education',
            'is_published' => true,
        ]);

        for ($i = 1; $i <= 14; $i++) {
            Offer::create([
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'title' => sprintf('Learning benefit %02d', $i),
                'slug' => sprintf('learning-benefit-%02d', $i),
                'summary' => 'Student learning access',
                'description' => 'Student eligibility terms',
                'partner_url' => 'https://example.org/students',
                'offer_type' => 'discount',
                'is_published' => true,
                'ends_at' => now()->addDays($i),
            ]);
        }

        Offer::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Learning benefit hidden',
            'slug' => 'learning-benefit-hidden',
            'summary' => 'Not yet released',
            'description' => 'Draft terms',
            'partner_url' => 'https://example.org/draft',
            'offer_type' => 'discount',
            'is_published' => false,
        ]);

        $query = [
            'q' => 'Learning',
            'brand' => $brand->slug,
            'category' => $category->slug,
            'type' => 'discount',
            'sort' => 'ending',
        ];

        $first = $this->get(route('search', $query));

        $first->assertOk()
            ->assertSee('14 offers found')
            ->assertSee('Learning benefit 01')
            ->assertSee('Learning benefit 12')
            ->assertDontSee('Learning benefit 13')
            ->assertDontSee('Learning benefit hidden')
            ->assertSee('page=2', false)
            ->assertSee('brand=learning-partner', false)
            ->assertSee('category=education', false)
            ->assertSee('type=discount', false)
            ->assertSee('sort=ending', false)
            ->assertSee('action="'.route('search').'"', false);

        $second = $this->get(route('search', array_merge($query, ['page' => 2])));

        $second->assertOk()
            ->assertSee('14 offers found')
            ->assertSee('Learning benefit 13')
            ->assertSee('Learning benefit 14')
            ->assertDontSee('Learning benefit 01')
            ->assertDontSee('Learning benefit hidden')
            ->assertSee('Search: Learning')
            ->assertSee('Ending soon');

        $this->get(route('offers.index', array_merge($query, ['page' => 2])))
            ->assertOk()
            ->assertSee('Learning benefit 13')
            ->assertSee('Learning benefit 14')
            ->assertSee('action="'.route('offers.index').'"', false);
    }
}
