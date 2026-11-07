<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferDirectorySeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_unfiltered_offer_directory_is_indexable(): void
    {
        $this->get(route('offers.index'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="index,follow">', false);
    }

    public function test_search_and_directory_variants_are_not_indexable(): void
    {
        $urls = [
            route('search'),
            route('search', ['q' => 'student']),
            route('offers.index', ['q' => 'student']),
            route('offers.index', ['brand' => 'example']),
            route('offers.index', ['category' => 'example']),
            route('offers.index', ['type' => 'discount']),
            route('offers.index', ['sort' => 'ending']),
            route('offers.index', ['page' => 2]),
        ];

        foreach ($urls as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('<meta name="robots" content="noindex,follow">', false);
        }
    }
}
