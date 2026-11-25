<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogFilteredPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_and_search_queries_do_not_grow_with_offer_card_count(): void
    {
        $category = Category::create([
            'name' => 'Student resources',
            'slug' => 'student-resources',
            'is_published' => true,
        ]);

        for ($i = 1; $i <= 14; $i++) {
            $brand = Brand::create([
                'name' => sprintf('Student Partner %02d', $i),
                'slug' => sprintf('student-partner-%02d', $i),
                'is_published' => true,
            ]);

            Offer::create([
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'title' => sprintf('Student Resource %02d', $i),
                'slug' => sprintf('student-resource-%02d', $i),
                'summary' => 'Student resource access',
                'description' => 'Student eligibility terms',
                'partner_url' => 'https://example.org/student',
                'is_published' => true,
            ]);
        }

        $selectCount = 0;
        DB::listen(function ($query) use (&$selectCount): void {
            if (str_starts_with(strtolower(ltrim($query->sql)), 'select')) {
                $selectCount++;
            }
        });

        $countSelects = function (string $url) use (&$selectCount): int {
            $before = $selectCount;
            $this->get($url)->assertOk();

            return $selectCount - $before;
        };

        $firstPage = $countSelects('/offers');
        $secondPage = $countSelects('/offers?page=2');
        $searchPage = $countSelects('/search?q=Student');

        // A request with twelve cards must not trigger one query per brand.
        // Give the extra search predicate and rendered filters room without
        // encoding an unstable millisecond timing threshold.
        $this->assertLessThanOrEqual(12, $firstPage);
        $this->assertLessThanOrEqual(12, $secondPage);
        $this->assertLessThanOrEqual(12, $searchPage);
    }

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

        // Each active chip removes one dimension and retains the other normalized
        // filters, rather than resetting search or preserving a stale page=2.
        foreach (['q', 'brand', 'category', 'type', 'sort'] as $removed) {
            $expected = route('search', array_diff_key($query, [$removed => true]));
            $first->assertSee('href="'.e($expected).'"', false);
        }
        $first->assertSee('aria-label="Remove brand filter"', false)
            ->assertSee('aria-label="Remove category filter"', false)
            ->assertSee('aria-label="Remove search filter"', false)
            ->assertSee('aria-label="Remove offer type filter"', false)
            ->assertSee('aria-label="Remove ending soon sort"', false);

        $second = $this->get(route('search', array_merge($query, ['page' => 2])));

        $second->assertOk()
            ->assertSee('14 offers found')
            ->assertSee('Learning benefit 13')
            ->assertSee('Learning benefit 14')
            ->assertDontSee('Learning benefit 01')
            ->assertDontSee('Learning benefit hidden')
            ->assertSee('Search: Learning')
            ->assertSee('Ending soon');

        $recovery = $this->get(route('search', array_merge($query, ['page' => 99])));
        $recovery->assertOk()
            ->assertSee('14 offers found')
            ->assertSee('Back to first page')
            ->assertDontSee('No offers found')
            ->assertSee('href="'.e(route('search', array_merge($query, ['page' => 1]))).'"', false);

        $this->get(route('offers.index', array_merge($query, ['page' => 2])))
            ->assertOk()
            ->assertSee('Learning benefit 13')
            ->assertSee('Learning benefit 14')
            ->assertSee('action="'.route('offers.index').'"', false);
    }
}
