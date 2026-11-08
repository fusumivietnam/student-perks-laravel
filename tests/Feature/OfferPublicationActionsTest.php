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

class OfferPublicationActionsTest extends TestCase
{
    use RefreshDatabase;

    private function offer(array $attributes = []): Offer
    {
        $brand = Brand::create([
            'name' => 'Example Brand',
            'slug' => 'example-brand',
            'is_published' => true,
        ]);
        $category = Category::create([
            'name' => 'Education',
            'slug' => 'education',
            'is_published' => true,
        ]);

        return Offer::create(array_merge([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Student course',
            'slug' => 'student-course',
            'summary' => 'Student offer',
            'description' => 'Education terms',
            'offer_type' => 'discount',
            'partner_url' => 'https://example.org/course',
            'is_published' => false,
        ], $attributes));
    }

    public function test_publication_status_distinguishes_draft_live_scheduled_expired_and_hidden(): void
    {
        $offer = $this->offer();
        $this->assertSame('Draft', $offer->publicationStatus());
        $this->assertTrue($offer->canPublishNow());

        $offer->update(['is_published' => true]);
        $this->assertSame('Live', $offer->publicationStatus());

        $offer->update(['published_at' => now()->addDay()]);
        $this->assertSame('Scheduled', $offer->publicationStatus());

        $offer->update(['published_at' => null, 'ends_at' => now()->subDay()]);
        $this->assertSame('Expired', $offer->publicationStatus());

        $offer->update(['ends_at' => null]);
        $offer->brand->update(['is_published' => false]);
        $this->assertSame('Hidden by brand/category', $offer->publicationStatus());

        $offer->update(['is_published' => false]);
        $this->assertFalse($offer->canPublishNow());
    }

    public function test_publish_and_unpublish_actions_change_public_visibility(): void
    {
        $offer = $this->offer();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/offers/student-course')->assertNotFound();

        Livewire::test(ListOffers::class)
            ->callTableAction('publish', $offer)
            ->assertHasNoTableActionErrors();

        $this->assertTrue($offer->fresh()->is_published);
        $this->get('/offers/student-course')->assertOk();

        Livewire::test(ListOffers::class)
            ->callTableAction('unpublish', $offer)
            ->assertHasNoTableActionErrors();

        $this->assertFalse($offer->fresh()->is_published);
        $this->get('/offers/student-course')->assertNotFound();
    }

    public function test_publish_now_cannot_bypass_schedule_expiry_or_hidden_taxonomy(): void
    {
        $offer = $this->offer(['published_at' => now()->addDay()]);
        $this->assertFalse($offer->canPublishNow());

        $offer->update(['published_at' => null, 'ends_at' => now()->subDay()]);
        $this->assertFalse($offer->canPublishNow());

        $offer->update(['ends_at' => null]);
        $offer->category->update(['is_published' => false]);
        $this->assertFalse($offer->canPublishNow());
    }

    public function test_non_admin_cannot_access_publication_actions(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->get('/admin/offers')->assertForbidden();
    }
}
