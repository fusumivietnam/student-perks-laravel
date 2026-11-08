<?php

namespace Tests\Feature;

use App\Filament\Resources\Articles\Pages\ManageArticles;
use App\Filament\Resources\Pages\Pages\ManagePages;
use App\Models\Article;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditorialPrivatePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_and_scheduled_articles_are_admin_previewable_but_never_public(): void
    {
        $draft = Article::create([
            'title' => 'Internal editorial guide',
            'slug' => 'internal-guide',
            'summary' => 'Not for publishing yet',
            'body' => 'Editor-only draft text',
            'is_published' => false,
        ]);
        $scheduled = Article::create([
            'title' => 'Tomorrow publication',
            'slug' => 'tomorrow-publication',
            'summary' => 'Future publication',
            'body' => 'Scheduled story',
            'is_published' => true,
            'published_at' => now()->addDay(),
        ]);

        $url = route('articles.preview', $draft);
        $this->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get($url)->assertForbidden();

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get($url)->assertOk()
            ->assertSee('Editor-only draft text')
            ->assertSee('Private admin preview of saved content')
            ->assertSee('content="noindex,nofollow,noarchive"', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');

        $this->get(route('articles.preview', $scheduled))->assertOk()
            ->assertSee('Scheduled story');
        $this->get('/articles/internal-guide')->assertNotFound();
        $this->get('/articles/tomorrow-publication')->assertNotFound();
        $this->get('/articles')->assertDontSee('Internal editorial guide')->assertDontSee('Tomorrow publication');
        $this->get('/sitemap.xml')->assertDontSee('/articles/internal-guide')->assertDontSee('/articles/tomorrow-publication');
    }

    public function test_unpublished_pages_have_private_preview_and_public_routes_stay_protected(): void
    {
        $draft = Page::create([
            'title' => 'Draft privacy terms',
            'slug' => 'draft-privacy-terms',
            'body' => 'Confidential policy draft',
            'is_published' => false,
        ]);

        $url = route('pages.preview', $draft);
        $this->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['is_admin' => false]))->get($url)->assertForbidden();

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get($url)->assertOk()
            ->assertSee('Confidential policy draft')
            ->assertSee('Private admin preview of saved content')
            ->assertSee('content="noindex,nofollow,noarchive"', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');

        $this->get('/pages/draft-privacy-terms')->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee('/pages/draft-privacy-terms');
    }

    public function test_editors_can_access_preview_actions_in_filament_tables(): void
    {
        $article = Article::create([
            'title' => 'Article preview',
            'slug' => 'article-preview',
            'summary' => 'Summary',
            'body' => 'Article copy',
            'is_published' => false,
        ]);
        $page = Page::create([
            'title' => 'Page preview',
            'slug' => 'page-preview',
            'body' => 'Page copy',
            'is_published' => false,
        ]);

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ManageArticles::class)
            ->assertTableActionExists('preview', record: $article);
        Livewire::test(ManagePages::class)
            ->assertTableActionExists('preview', record: $page);
    }
}
