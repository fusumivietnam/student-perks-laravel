<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorialMobileAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_article_list_and_detail_wrap_long_content_and_offer_keyboard_targets(): void
    {
        $article = Article::create([
            'title' => 'Accessible student resources',
            'slug' => 'accessible-student-resources',
            'summary' => str_repeat('longword', 20),
            'body' => str_repeat('unbrokenword', 30),
            'is_published' => true,
        ]);

        $this->get(route('articles.index'))->assertOk()
            ->assertSee($article->title)
            ->assertSee('focus-visible:outline-2 focus-visible:outline-indigo-700', false)
            ->assertSee('min-h-11', false)
            ->assertSee('break-words', false);

        $this->get(route('articles.show', $article->slug))->assertOk()
            ->assertSee('data-ui="public-reading-panel"', false)
            ->assertSee('data-ui="public-page-heading"', false)
            ->assertSee('break-words text-3xl', false)
            ->assertSee('whitespace-pre-line break-words', false)
            ->assertSee('focus-visible:outline-2', false);
    }

    public function test_public_index_pages_share_the_same_heading_component(): void
    {
        foreach (['brands.index', 'articles.index'] as $route) {
            $this->get(route($route))->assertOk()
                ->assertSee('data-ui="public-page-heading"', false)
                ->assertSee('break-words text-3xl font-extrabold', false);
        }
        $this->get(route('articles.index'))->assertOk()
            ->assertSee('Browse offers')
            ->assertSee('href="'.route('offers.index').'"', false);
    }

    public function test_published_pages_preserve_whitespace_and_wrap_long_text(): void
    {
        $page = Page::create([
            'title' => 'Accessible information page',
            'slug' => 'accessible-information-page',
            'body' => str_repeat('unbrokenword', 30),
            'is_published' => true,
        ]);

        $this->get(route('pages.show', $page->slug))->assertOk()
            ->assertSee($page->title)
            ->assertSee('data-ui="public-reading-panel"', false)
            ->assertSee('data-ui="public-page-heading"', false)
            ->assertSee('break-words text-3xl', false)
            ->assertSee('whitespace-pre-line break-words', false);
    }
}
