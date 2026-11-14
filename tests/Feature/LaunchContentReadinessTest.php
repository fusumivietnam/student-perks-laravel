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

        foreach (['/pages/about', '/pages/privacy', '/pages/terms', '/pages/contact', '/pages/disclaimer'] as $page) {
            $this->assertStringContainsString($page, $output);
        }

        $this->assertStringContainsString('No currently visible offers', $output);
        $this->assertStringContainsString('CONTENT GATE FAILED', $output);
        $this->assertDatabaseCount('offers', 0);
    }

    public function test_missing_disclaimer_alone_blocks_content_gate(): void
    {
        $this->publishLegalPages();
        Page::where('slug', 'disclaimer')->delete();
        $offer = $this->createOffer();
        $offer->forceFill(['partner_reviewed_at' => now()])->save();

        $this->assertSame(1, Artisan::call('launch:check'));
        $this->assertStringContainsString('Published legal/information page missing: /pages/disclaimer', Artisan::output());
        $this->assertStringNotContainsString('No currently visible offers', Artisan::output());
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

    public function test_content_gate_identifies_existing_draft_pages_and_unreviewed_offer_records_without_writing(): void
    {
        $this->publishLegalPages();
        Page::where('slug', 'privacy')->update(['is_published' => false]);
        Page::where('slug', 'contact')->delete();

        $offer = $this->createOffer();
        $this->assertNull($offer->partner_reviewed_at);

        $this->assertSame(1, Artisan::call('launch:check'));
        $output = Artisan::output();

        $this->assertStringContainsString('review the existing draft in Filament', $output);
        $this->assertStringContainsString('create /pages/contact in Filament', $output);
        $this->assertStringContainsString('Offer #'.$offer->id, $output);
        $this->assertStringContainsString($offer->title, $output);
        $this->assertStringContainsString(route('offers.show', $offer->slug), $output);
        $this->assertStringContainsString('Never confirm unverified links', $output);

        $this->assertFalse(Page::where('slug', 'privacy')->firstOrFail()->is_published);
        $this->assertNull(Page::where('slug', 'contact')->first());
        $this->assertNull($offer->fresh()->partner_reviewed_at);
    }

    public function test_expired_or_draft_offers_do_not_satisfy_live_requirement(): void
    {
        $this->publishLegalPages();
        $this->createOffer(['is_published' => false]);

        $this->assertSame(1, Artisan::call('launch:check'));
        $this->assertStringContainsString('No currently visible offers', Artisan::output());
    }

    public function test_published_legal_page_with_empty_body_is_a_blocker(): void
    {
        $this->publishLegalPages();
        Page::where('slug', 'privacy')->update(['body' => '   ']);
        $offer = $this->createOffer();
        $offer->forceFill(['partner_reviewed_at' => now()])->save();

        $this->assertSame(1, Artisan::call('launch:check'));
        $this->assertStringContainsString(
            'Published legal/information page has no content: /pages/privacy',
            Artisan::output()
        );
        $this->assertSame('   ', Page::where('slug', 'privacy')->value('body'));
    }

    public function test_legacy_published_slugs_are_audited_without_rewriting_them(): void
    {
        $this->publishLegalPages();
        $offer = $this->createOffer();
        $offer->forceFill(['partner_reviewed_at' => now()])->save();
        $offer->brand->update(['slug' => 'brand with spaces']);
        $offer->category->update(['slug' => 'category/invalid']);
        Page::create([
            'title' => 'Old page',
            'slug' => 'OLD_PAGE',
            'body' => 'Archived content',
            'is_published' => true,
        ]);
        Article::create([
            'title' => 'Old article',
            'slug' => 'article with spaces',
            'summary' => 'Editorial summary',
            'body' => 'Editorial content',
            'is_published' => true,
        ]);
        $offer->update(['slug' => 'Offer_Invalid']);

        $this->assertSame(1, Artisan::call('launch:check'));
        $output = Artisan::output();
        foreach (['brands', 'categories', 'offers', 'articles', 'pages'] as $type) {
            $this->assertStringContainsString("published {$type} have invalid public URL slugs", $output);
        }
        $this->assertSame('Offer_Invalid', $offer->fresh()->slug);
        $this->assertSame('brand with spaces', $offer->brand->fresh()->slug);
    }

    public function test_visible_offer_with_empty_summary_or_description_is_a_blocker(): void
    {
        $this->publishLegalPages();
        $offer = $this->createOffer(['summary' => ' ', 'description' => '']);
        $offer->forceFill(['partner_reviewed_at' => now()])->save();

        $this->assertSame(1, Artisan::call('launch:check'));
        $this->assertStringContainsString('visible offers have empty title, summary or description', Artisan::output());
        $this->assertSame(' ', $offer->fresh()->summary);
    }

    private function publishLegalPages(): void
    {
        foreach (['about', 'privacy', 'terms', 'contact', 'disclaimer'] as $slug) {
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
