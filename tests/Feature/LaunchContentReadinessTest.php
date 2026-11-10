<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class LaunchContentReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_database_blocks_launch_with_explicit_content_gaps(): void
    {
        $this->assertSame(1, Artisan::call('launch:check'));
        $output = Artisan::output();

        foreach (['/pages/about', '/pages/privacy', '/pages/terms', '/pages/contact'] as $page) {
            $this->assertStringContainsString($page, $output);
        }

        $this->assertStringContainsString('No currently visible offers', $output);
        $this->assertStringContainsString('CONTENT GATE FAILED', $output);
        $this->assertDatabaseCount('offers', 0);
    }

    public function test_reviewed_live_offer_and_all_published_pages_pass_content_gate(): void
    {
        $this->publishLegalPages();
        $offer = $this->createOffer();
        $offer->forceFill(['partner_reviewed_at' => now()])->save();

        $this->assertSame(0, Artisan::call('launch:check'));
        $output = Artisan::output();

        $this->assertStringContainsString('CONTENT GATE PASSED', $output);
        $this->assertStringContainsString('No published guides yet', $output);
        $this->assertStringContainsString('NOT a launch authorization', $output);
        $this->assertTrue($offer->fresh()->is_published);
    }

    public function test_incomplete_and_unreviewed_offers_block_but_command_does_not_modify_any_record(): void
    {
        $this->publishLegalPages();
        $offer = $this->createOffer([
            'eligibility' => ' ',
            'partner_url' => 'http://invalid.example/student',
        ]);

        $this->assertSame(1, Artisan::call('launch:check'));
        $output = Artisan::output();
        $this->assertStringContainsString('lack eligibility or redemption steps', $output);
        $this->assertStringContainsString('need manual review', $output);
        $this->assertStringContainsString('invalid/non-HTTPS partner destinations', $output);
        $this->assertStringContainsString('CONTENT GATE FAILED', $output);

        $this->assertSame(' ', $offer->fresh()->eligibility);
        $this->assertNull($offer->fresh()->partner_reviewed_at);
        $this->assertSame('http://invalid.example/student', $offer->fresh()->partner_url);
    }

    public function test_expired_or_draft_offers_do_not_satisfy_live_requirement(): void
    {
        $this->publishLegalPages();
        $this->createOffer(['is_published' => false]);

        $this->assertSame(1, Artisan::call('launch:check'));
        $this->assertStringContainsString('No currently visible offers', Artisan::output());
    }

    private function publishLegalPages(): void
    {
        foreach (['about', 'privacy', 'terms', 'contact'] as $slug) {
            Page::create([
                'title' => ucfirst($slug),
                'slug' => $slug,
                'body' => 'Reviewed editorial content',
                'is_published' => true,
            ]);
        }
    }

    private function createOffer(array $attributes = []): Offer
    {
        $brand = Brand::create(['name' => 'Campus Partner', 'slug' => 'campus-partner', 'is_published' => true]);
        $category = Category::create(['name' => 'Learning', 'slug' => 'learning', 'is_published' => true]);

        return Offer::create(array_merge([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Student education plan',
            'slug' => 'student-education-plan',
            'summary' => 'Learning subscription',
            'description' => 'Official partner terms',
            'offer_type' => 'discount',
            'eligibility' => 'Current students',
            'redemption_steps' => 'Check eligibility on partner website',
            'partner_url' => 'https://example.org/student',
            'is_published' => true,
        ], $attributes));
    }
}
