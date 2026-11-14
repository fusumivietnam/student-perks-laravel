<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorialPublicationStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_admin_status_matches_actual_public_visibility(): void
    {
        $article = Article::create([
            'title' => 'Editorial publication lifecycle',
            'slug' => 'editorial-publication-lifecycle',
            'summary' => 'Published, scheduled and draft checks',
            'body' => 'A guide for students',
            'is_published' => false,
        ]);

        $this->assertSame('Draft', $article->publicationStatus());
        $this->get(route('articles.show', $article->slug))->assertNotFound();

        $article->update(['is_published' => true, 'published_at' => now()->addDay()]);
        $this->assertSame('Scheduled', $article->fresh()->publicationStatus());
        $this->get(route('articles.show', $article->slug))->assertNotFound();
        $this->get(route('articles.index'))->assertDontSee($article->title);

        $article->update(['published_at' => now()->subMinute()]);
        $this->assertSame('Live', $article->fresh()->publicationStatus());
        $this->get(route('articles.show', $article->slug))->assertOk();
        $this->get(route('articles.index'))->assertSee($article->title);

        $article->update(['is_published' => false]);
        $this->assertSame('Draft', $article->fresh()->publicationStatus());
        $this->get(route('articles.show', $article->slug))->assertNotFound();
    }

    public function test_page_publication_status_matches_public_routes(): void
    {
        $page = Page::create([
            'title' => 'Student support',
            'slug' => 'student-support',
            'body' => 'Support content',
            'is_published' => false,
        ]);

        $this->assertSame('Draft', $page->publicationStatus());
        $this->get(route('pages.show', $page->slug))->assertNotFound();

        $page->update(['is_published' => true]);
        $this->assertSame('Live', $page->fresh()->publicationStatus());
        $this->get(route('pages.show', $page->slug))->assertOk();

        $page->update(['is_published' => false]);
        $this->assertSame('Draft', $page->fresh()->publicationStatus());
        $this->get(route('pages.show', $page->slug))->assertNotFound();
    }
}
