<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EditorialSlugRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_article_renames_redirect_old_urls_but_drafts_are_not_exposed(): void
    {
        $article = Article::create([
            'title' => 'Student guide',
            'slug' => 'student-guide',
            'summary' => 'Practical guidance',
            'body' => 'Content',
            'is_published' => true,
        ]);

        $article->update(['slug' => 'updated-student-guide']);
        $article->update(['slug' => 'latest-student-guide']);

        foreach (['student-guide', 'updated-student-guide'] as $oldSlug) {
            $this->get(route('articles.show', $oldSlug))
                ->assertStatus(301)
                ->assertRedirect(route('articles.show', 'latest-student-guide'));
        }

        $this->get(route('articles.show', $article->slug))->assertOk();
        $this->assertSame(2, DB::table('article_slug_redirects')->where('article_id', $article->id)->count());

        $article->update(['is_published' => false]);
        $this->get(route('articles.show', 'student-guide'))->assertNotFound();
        $this->get(route('articles.show', 'latest-student-guide'))->assertNotFound();
    }

    public function test_published_page_redirects_do_not_leak_unpublished_information(): void
    {
        $page = Page::create([
            'title' => 'Information',
            'slug' => 'original-information',
            'body' => 'Original text',
            'is_published' => true,
        ]);

        $page->update(['slug' => 'updated-information']);

        $this->get(route('pages.show', 'original-information'))
            ->assertStatus(301)
            ->assertRedirect(route('pages.show', 'updated-information'));
        $this->get(route('pages.show', 'updated-information'))->assertOk();

        $page->update(['is_published' => false]);
        $this->get(route('pages.show', 'original-information'))->assertNotFound();
        $this->get(route('pages.show', 'updated-information'))->assertNotFound();
    }

    public function test_draft_article_and_page_slug_changes_do_not_create_redirect_history(): void
    {
        $article = Article::create([
            'title' => 'Draft article',
            'slug' => 'draft-article',
            'summary' => 'Draft summary',
            'body' => 'Draft content',
            'is_published' => false,
        ]);
        $page = Page::create([
            'title' => 'Draft page',
            'slug' => 'draft-page',
            'body' => 'Draft copy',
            'is_published' => false,
        ]);

        $article->update(['slug' => 'draft-article-renamed']);
        $page->update(['slug' => 'draft-page-renamed']);

        $this->assertDatabaseCount('article_slug_redirects', 0);
        $this->assertDatabaseCount('page_slug_redirects', 0);
        $this->get(route('articles.show', 'draft-article'))->assertNotFound();
        $this->get(route('pages.show', 'draft-page'))->assertNotFound();
    }

    public function test_historic_redirects_are_removed_when_editorial_record_is_deleted(): void
    {
        $article = Article::create([
            'title' => 'Temporary article',
            'slug' => 'old-article',
            'summary' => 'Summary',
            'body' => 'Details',
            'is_published' => true,
        ]);
        $page = Page::create([
            'title' => 'Temporary page',
            'slug' => 'old-page',
            'body' => 'Details',
            'is_published' => true,
        ]);

        $article->update(['slug' => 'new-article']);
        $page->update(['slug' => 'new-page']);
        $article->delete();
        $page->delete();

        $this->assertDatabaseCount('article_slug_redirects', 0);
        $this->assertDatabaseCount('page_slug_redirects', 0);
        $this->get(route('articles.show', 'old-article'))->assertNotFound();
        $this->get(route('pages.show', 'old-page'))->assertNotFound();
    }
}
