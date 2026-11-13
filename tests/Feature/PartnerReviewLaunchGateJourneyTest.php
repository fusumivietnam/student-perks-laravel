<?php

namespace Tests\Feature;

use App\Filament\Resources\Offers\Pages\EditOffer;
use App\Filament\Resources\Offers\Pages\ListOffers;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerReviewLaunchGateJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_review_can_clear_content_gate_and_partner_url_change_invalidates_review(): void
    {
        foreach (['about', 'privacy', 'terms', 'contact', 'disclaimer'] as $slug) {
            Page::create([
                'title' => ucfirst($slug),
                'slug' => $slug,
                'body' => 'Approved editorial content',
                'is_published' => true,
            ]);
        }

        $brand = Brand::create(['name' => 'Campus Partner', 'slug' => 'campus-partner', 'is_published' => true]);
        $category = Category::create(['name' => 'Software', 'slug' => 'software', 'is_published' => true]);
        $offer = Offer::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Verified student offer',
            'slug' => 'verified-student-offer',
            'summary' => 'Educational benefit',
            'description' => 'Official partner offer',
            'offer_type' => 'discount',
            'eligibility' => 'Current students',
            'redemption_steps' => 'Visit the official partner and verify student status',
            'partner_url' => 'https://example.org/education',
            'is_published' => true,
        ]);

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->assertSame(1, Artisan::call('launch:check'));
        $this->assertStringContainsString('visible partner links need manual review', Artisan::output());
        $this->assertNull($offer->fresh()->partner_reviewed_at);
        $this->get('/offers/verified-student-offer')->assertOk()
            ->assertSee('awaiting editorial review')
            ->assertDontSee('href="https://example.org/education"', false);

        // This action records an explicit human confirmation, not an HTTP link check.
        Livewire::test(ListOffers::class)
            ->callTableAction('confirmPartnerReview', $offer)
            ->assertHasNoTableActionErrors();

        $this->assertNotNull($offer->fresh()->partner_reviewed_at);
        $this->assertSame(0, Artisan::call('launch:check'));
        $this->assertStringContainsString('CONTENT GATE PASSED', Artisan::output());
        $this->get('/offers/verified-student-offer')->assertOk()
            ->assertSee('href="https://example.org/education"', false)
            ->assertDontSee('awaiting editorial review');

        Livewire::test(EditOffer::class, ['record' => $offer->getRouteKey()])
            ->fillForm(['partner_url' => 'https://example.org/new-education'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('https://example.org/new-education', $offer->fresh()->partner_url);
        $this->get('/offers/verified-student-offer')->assertOk()
            ->assertSee('awaiting editorial review')
            ->assertDontSee('href="https://example.org/new-education"', false);
        $this->assertNull($offer->fresh()->partner_reviewed_at);
        $this->assertSame(1, Artisan::call('launch:check'));
        $this->assertStringContainsString('visible partner links need manual review', Artisan::output());
    }

    public function test_review_older_than_ninety_days_requires_reconfirmation_from_admin(): void
    {
        foreach (['about', 'privacy', 'terms', 'contact', 'disclaimer'] as $slug) {
            Page::create([
                'title' => ucfirst($slug),
                'slug' => $slug,
                'body' => 'Approved editorial content',
                'is_published' => true,
            ]);
        }
        $brand = Brand::create(['name' => 'Academy', 'slug' => 'academy', 'is_published' => true]);
        $category = Category::create(['name' => 'Learning', 'slug' => 'learning', 'is_published' => true]);
        $offer = Offer::create([
            'brand_id' => $brand->id, 'category_id' => $category->id,
            'title' => 'Student learning', 'slug' => 'student-learning',
            'summary' => 'Learning discount', 'description' => 'Student terms',
            'offer_type' => 'discount', 'eligibility' => 'Enrolled students',
            'redemption_steps' => 'Apply via the partner',
            'partner_url' => 'https://example.org/learning',
            'is_published' => true,
        ]);
        $offer->forceFill(['partner_reviewed_at' => now()->subDays(91)])->save();

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->assertSame(1, Artisan::call('launch:check'));
        $this->assertStringContainsString('visible partner links need manual review', Artisan::output());

        $this->get('/offers/student-learning')->assertOk()
            ->assertSee('awaiting editorial review')
            ->assertDontSee('href="https://example.org/learning"', false);

        Livewire::test(ListOffers::class)
            ->callTableAction('confirmPartnerReview', $offer)
            ->assertHasNoTableActionErrors();

        $this->assertSame(0, Artisan::call('launch:check'));
        $this->get('/offers/student-learning')->assertOk()
            ->assertSee('href="https://example.org/learning"', false);
    }
}

