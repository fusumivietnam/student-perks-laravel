<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StablePaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_offer_pagination_has_a_unique_tie_breaker_for_latest_and_ending_soon(): void
    {
        $this->travelTo(now()->startOfSecond());

        $brand = Brand::create(['name' => 'Campus Store', 'slug' => 'campus-store', 'is_published' => true]);
        $category = Category::create(['name' => 'Software', 'slug' => 'software', 'is_published' => true]);

        $offers = collect(range(1, 14))->map(fn (int $i): Offer => Offer::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Student deal '.sprintf('%02d', $i),
            'slug' => 'student-deal-'.$i,
            'summary' => 'Student learning benefit',
            'description' => 'Terms apply',
            'partner_url' => 'https://example.org/student',
            'is_published' => true,
            'ends_at' => now()->addWeek(),
        ]));

        $latestFirst = $offers->reverse()->take(12);
        $latestSecond = $offers->reverse()->skip(12);
        $endingFirst = $offers->take(12);
        $endingSecond = $offers->skip(12);

        foreach ([
            ['/offers', $latestFirst, $latestSecond],
            ['/search?q=Student', $latestFirst, $latestSecond],
            ['/brands/campus-store', $latestFirst, $latestSecond],
            ['/categories/software', $latestFirst, $latestSecond],
            ['/offers?sort=ending', $endingFirst, $endingSecond],
        ] as [$base, $first, $second]) {
            $joiner = str_contains($base, '?') ? '&' : '?';

            $this->get($base)->assertOk()
                ->assertSeeInOrder($first->pluck('title')->all())
                ->assertDontSee($second->first()->title);

            $this->get($base.$joiner.'page=2')->assertOk()
                ->assertSeeInOrder($second->pluck('title')->all())
                ->assertDontSee($first->first()->title);
        }
    }

    public function test_article_listing_has_stable_pagination_for_identical_publish_dates(): void
    {
        $this->travelTo(now()->startOfSecond());

        $articles = collect(range(1, 14))->map(fn (int $i): Article => Article::create([
            'title' => 'Campus guide '.sprintf('%02d', $i),
            'slug' => 'campus-guide-'.$i,
            'summary' => 'Helpful student guide',
            'body' => 'Editorial information',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]));

        $this->get('/articles')->assertOk()
            ->assertSeeInOrder($articles->reverse()->take(12)->pluck('title')->all())
            ->assertDontSee($articles->first()->title);

        $this->get('/articles?page=2')->assertOk()
            ->assertSeeInOrder($articles->reverse()->skip(12)->pluck('title')->all())
            ->assertDontSee($articles->last()->title);
    }
}
