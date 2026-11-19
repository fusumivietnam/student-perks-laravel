<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicListingEfficiencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_offer_cards_load_only_listing_fields_with_eager_loaded_relationships(): void
    {
        $brand = Brand::create(['name' => 'Campus Partner', 'slug' => 'campus-partner', 'is_published' => true]);
        $category = Category::create(['name' => 'Software', 'slug' => 'software', 'is_published' => true]);
        $offer = Offer::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Student plan',
            'slug' => 'student-plan',
            'summary' => 'Public card summary',
            'description' => 'Full detail content',
            'partner_url' => 'https://example.org/student',
            'is_published' => true,
        ]);

        foreach (['/', '/offers', '/search?q=Student', '/brands/campus-partner', '/categories/software'] as $path) {
            $response = $this->get($path)->assertOk()
                ->assertSee('Public card summary')
                ->assertSee('View offer details<span class="sr-only">: Student plan</span>', false);

            $card = $response->viewData('offers')->first();
            $this->assertSame($offer->id, $card->id);
            $this->assertArrayNotHasKey('description', $card->getAttributes());
            $this->assertArrayNotHasKey('partner_url', $card->getAttributes());
            $this->assertArrayNotHasKey('eligibility', $card->getAttributes());
            $this->assertArrayNotHasKey('redemption_steps', $card->getAttributes());
            $this->assertTrue($card->relationLoaded('brand'));
            $this->assertTrue($card->relationLoaded('category'));
        }

        $this->get('/offers/student-plan')->assertOk()->assertSee('Full detail content');
        $this->get('/brands')->assertOk()
            ->assertSee('Explore offers<span class="sr-only">: Campus Partner</span>', false);
    }

    public function test_guide_listing_does_not_fetch_full_article_body(): void
    {
        Article::create([
            'title' => 'Campus guide',
            'slug' => 'campus-guide',
            'summary' => 'Public guide summary',
            'body' => 'Full editorial body',
            'is_published' => true,
        ]);

        $response = $this->get('/articles')->assertOk()
            ->assertSee('Public guide summary')
            ->assertSee('Read guide<span class="sr-only">: Campus guide</span>', false);

        $this->assertArrayNotHasKey('body', $response->viewData('articles')->first()->getAttributes());
        $this->get('/articles/campus-guide')->assertOk()->assertSee('Full editorial body');
    }
}
