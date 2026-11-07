<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\User;
use Database\Seeders\PreviewDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PreviewDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_seeder_populates_public_and_draft_content_without_creating_admin(): void
    {
        config()->set('app.env', 'local');
        config()->set('app.url', 'https://sample-8080.app.github.dev');

        $this->seed(PreviewDemoSeeder::class);

        $this->assertSame(3, Brand::where('slug', 'like', 'demo-%')->count());
        $this->assertSame(3, Category::where('slug', 'like', 'demo-%')->count());
        $this->assertSame(5, Offer::where('slug', 'like', 'demo-%')->count());
        $this->assertSame(2, Article::where('slug', 'like', 'demo-%')->count());
        $this->assertSame(0, User::count());

        $this->get('/')->assertOk()->assertSee('Student Design Kit');
        $this->get('/offers/demo-student-design-kit')->assertOk();
        $this->get('/offers/demo-unpublished-offer')->assertNotFound();
        $this->get('/articles/demo-how-to-compare-offers')->assertOk();
        $this->get('/articles/demo-editorial-draft')->assertNotFound();

        Offer::where('slug', 'demo-student-design-kit')->update(['title' => 'Edited by admin']);
        $this->seed(PreviewDemoSeeder::class);

        $this->assertSame('Edited by admin', Offer::where('slug', 'demo-student-design-kit')->value('title'));
        $this->assertSame(5, Offer::where('slug', 'like', 'demo-%')->count());
    }

    public function test_preview_seeder_refuses_nonlocal_environment_before_writing_any_records(): void
    {
        config()->set('app.env', 'production');
        config()->set('app.url', 'https://real.example.org');

        try {
            $this->seed(PreviewDemoSeeder::class);
            $this->fail('Demo seeder should reject production.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('restricted', $exception->getMessage());
        }

        $this->assertSame(0, Brand::count());
        $this->assertSame(0, Offer::count());
    }
}
