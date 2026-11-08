<?php

namespace Tests\Feature;

use App\Filament\Resources\Offers\Pages\ListOffers;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OfferEditorialReviewTest extends TestCase
{
    use RefreshDatabase;

    private function offer(string $slug, array $attributes = []): Offer
    {
        $brand = Brand::firstOrCreate(
            ['slug' => 'review-brand'],
            ['name' => 'Review Brand', 'is_published' => true],
        );
        $category = Category::firstOrCreate(
            ['slug' => 'review-category'],
            ['name' => 'Review Category', 'is_published' => true],
        );

        return Offer::create(array_merge([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Student offer '.$slug,
            'slug' => $slug,
            'summary' => 'Student benefit',
            'description' => 'Terms and conditions',
            'offer_type' => 'discount',
            'partner_url' => 'https://example.org/student',
            'eligibility' => 'Current students',
            'redemption_steps' => 'Verify with provider',
            'is_published' => true,
        ], $attributes));
    }

    public function test_editorial_review_filter_finds_null_empty_and_whitespace_only_fields(): void
    {
        $ready = $this->offer('ready');
        $missingEligibility = $this->offer('missing-eligibility', ['eligibility' => null]);
        $missingSteps = $this->offer('missing-steps', ['redemption_steps' => '   ']);
        $missingBoth = $this->offer('missing-both', ['eligibility' => '', 'redemption_steps' => null]);

        $this->assertFalse($ready->needsEditorialReview());
        foreach ([$missingEligibility, $missingSteps, $missingBoth] as $incomplete) {
            $this->assertTrue($incomplete->needsEditorialReview());
        }

        $this->assertEqualsCanonicalizing(
            [$missingEligibility->id, $missingSteps->id, $missingBoth->id],
            Offer::needsEditorialReview()->pluck('id')->all(),
        );

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(ListOffers::class)
            ->filterTable('needs_editorial_review', true)
            ->assertCanSeeTableRecords([$missingEligibility, $missingSteps, $missingBoth])
            ->assertCanNotSeeTableRecords([$ready]);
    }

    public function test_bulk_unpublish_does_not_delete_records_or_change_schedules(): void
    {
        $live = $this->offer('live');
        $scheduled = $this->offer('scheduled', ['published_at' => now()->addDay()]);
        $draft = $this->offer('draft', ['is_published' => false]);
        $untouched = $this->offer('untouched');

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ListOffers::class)
            ->callTableBulkAction('unpublishSelected', [$live, $scheduled, $draft])
            ->assertHasNoTableBulkActionErrors();

        foreach ([$live, $scheduled, $draft] as $offer) {
            $this->assertFalse($offer->fresh()->is_published);
        }

        $this->assertTrue($untouched->fresh()->is_published);
        $this->assertTrue($scheduled->fresh()->published_at->isFuture());
        $this->assertDatabaseCount('offers', 4);
        $this->get('/offers/live')->assertNotFound();
        $this->get('/offers/untouched')->assertOk();
    }

    public function test_non_admin_cannot_access_editorial_review_tools(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->get('/admin/offers')->assertForbidden();
    }
}
