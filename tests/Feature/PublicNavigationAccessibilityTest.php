<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicNavigationAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_navigation_marks_only_the_current_destination(): void
    {
        $destinations = [
            'home' => null,
            'offers.index' => 'All offers',
            'brands.index' => 'Brands',
            'search' => 'Search',
            'articles.index' => 'Guides',
        ];

        foreach ($destinations as $route => $activeLabel) {
            $response = $this->get(route($route));

            $response->assertOk();
            $response->assertSee('href="#main"', false);
            $response->assertSee('id="main"', false);

            $html = $response->getContent();
            preg_match_all('/<a\\b[^>]*\\baria-current="page"[^>]*>(.*?)<\\/a>/s', $html, $matches);

            if ($activeLabel === null) {
                $this->assertCount(0, $matches[1], 'Homepage must not mark a different menu item current.');

                continue;
            }

            $this->assertCount(1, $matches[1], "Exactly one current-page link expected on {$route}.");
            $this->assertSame($activeLabel, trim(strip_tags($matches[1][0])));
        }
    }
}
