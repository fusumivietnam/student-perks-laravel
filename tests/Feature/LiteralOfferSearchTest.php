<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiteralOfferSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_treats_percent_underscore_and_backslash_as_literal_characters(): void
    {
        $brand = Brand::create(['name' => 'Acme Learning', 'slug' => 'acme-learning', 'is_published' => true]);
        $category = Category::create(['name' => 'Software', 'slug' => 'software', 'is_published' => true]);

        foreach ([
            ['slug' => 'percent', 'title' => 'Save 20% on courses'],
            ['slug' => 'underscore', 'title' => 'L_earning student offer'],
            ['slug' => 'plain', 'title' => 'Learning student offer'],
            ['slug' => 'backslash', 'title' => 'Windows C:\\Users guide'],
        ] as $item) {
            Offer::create(array_merge($item, [
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'summary' => 'Student benefit',
                'description' => 'Education terms',
                'partner_url' => 'https://example.org/education',
                'is_published' => true,
            ]));
        }

        $this->get('/search?q=%25')->assertOk()
            ->assertSee('Save 20% on courses')
            ->assertDontSee('L_earning student offer')
            ->assertDontSee('Learning student offer');

        $this->get('/offers?q=L_earning')->assertOk()
            ->assertSee('L_earning student offer')
            ->assertDontSee('Learning student offer');

        $this->get('/search?q=Learning')->assertOk()
            ->assertSee('Learning student offer')
            ->assertDontSee('L_earning student offer');

        $this->get('/search?q=C%3A%5CUsers')->assertOk()
            ->assertSee('Windows C:\\Users guide')
            ->assertDontSee('Save 20% on courses');

        $this->get('/search?q=nonexistent')->assertOk()
            ->assertSee('No offers found');
    }
}
