<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PrepareLaunchPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepares_five_unpublished_editable_pages_without_exposing_them_publicly(): void
    {
        $this->assertSame(0, Artisan::call('launch:prepare-pages'));
        $this->assertDatabaseCount('pages', 5);

        foreach (['about', 'privacy', 'terms', 'contact', 'disclaimer'] as $slug) {
            $page = Page::where('slug', $slug)->firstOrFail();
            $this->assertFalse($page->is_published);
            $this->assertNotEmpty($page->seo_description);
            $this->assertNotEmpty($page->body);
            $this->get(route('pages.show', $slug))->assertNotFound();
        }

        $this->assertStringContainsString('5 created, 0 preserved', Artisan::output());
        $this->assertSame(1, Artisan::call('launch:check'));
    }

    public function test_repeated_runs_preserve_existing_pages_and_published_state(): void
    {
        $existing = Page::create([
            'title' => 'Approved privacy copy',
            'slug' => 'privacy',
            'body' => 'Reviewed and approved privacy policy',
            'seo_description' => 'Existing metadata',
            'is_published' => true,
        ]);
        $draft = Page::create([
            'title' => 'Existing About draft',
            'slug' => 'about',
            'body' => 'Work in progress',
            'is_published' => false,
        ]);

        $this->assertSame(0, Artisan::call('launch:prepare-pages'));
        $this->assertStringContainsString('3 created, 2 preserved', Artisan::output());
        $this->assertSame(0, Artisan::call('launch:prepare-pages'));
        $this->assertStringContainsString('0 created, 5 preserved', Artisan::output());

        $this->assertDatabaseCount('pages', 5);
        $this->assertSame('Reviewed and approved privacy policy', $existing->fresh()->body);
        $this->assertSame('Existing metadata', $existing->fresh()->seo_description);
        $this->assertTrue($existing->fresh()->is_published);
        $this->assertSame('Work in progress', $draft->fresh()->body);
        $this->assertFalse($draft->fresh()->is_published);
    }

    public function test_draft_preparation_never_marks_live_partner_links_as_reviewed(): void
    {
        $brand = Brand::create(['name' => 'Partner', 'slug' => 'partner', 'is_published' => true]);
        $category = Category::create(['name' => 'Learning', 'slug' => 'learning', 'is_published' => true]);
        $offer = Offer::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Student Plan',
            'slug' => 'student-plan',
            'summary' => 'Educational benefit',
            'description' => 'Terms of offer',
            'partner_url' => 'https://example.org/offer',
            'is_published' => true,
        ]);

        $this->assertSame(0, Artisan::call('launch:prepare-pages'));
        $this->assertNull($offer->fresh()->partner_reviewed_at);
        $this->assertTrue($offer->fresh()->is_published);
        $this->assertSame(1, Artisan::call('launch:check'));
        $this->assertStringContainsString('need manual review', Artisan::output());
    }
}
