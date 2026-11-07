<?php

namespace Tests\Feature;

use App\Filament\Resources\Offers\Pages\CreateOffer;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class OfferSlugRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function offer(string $slug = 'first-name', bool $published = true): Offer
    {
        $brand = Brand::create(['name' => 'Learning Studio', 'slug' => 'learning-studio', 'is_published' => true]);
        $category = Category::create(['name' => 'Software', 'slug' => 'software', 'is_published' => true]);

        return Offer::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Learning benefit',
            'slug' => $slug,
            'summary' => 'Benefit summary',
            'description' => 'Benefit description',
            'partner_url' => 'https://example.org/education',
            'offer_type' => 'discount',
            'is_published' => $published,
        ]);
    }

    public function test_published_slug_changes_redirect_directly_to_latest_canonical_url(): void
    {
        $offer = $this->offer();

        $offer->update(['slug' => 'second-name']);
        $this->get('/offers/first-name')
            ->assertStatus(301)
            ->assertRedirect(route('offers.show', 'second-name'));

        $offer->update(['slug' => 'third-name']);

        $this->get('/offers/first-name')
            ->assertStatus(301)
            ->assertRedirect(route('offers.show', 'third-name'));
        $this->get('/offers/second-name')
            ->assertStatus(301)
            ->assertRedirect(route('offers.show', 'third-name'));
        $this->get('/offers/third-name')->assertOk();

        $this->assertSame(2, DB::table('offer_slug_redirects')->where('offer_id', $offer->id)->count());
        $this->get('/sitemap.xml')->assertOk()
            ->assertSee('/offers/third-name')
            ->assertDontSee('/offers/first-name')
            ->assertDontSee('/offers/second-name');
    }

    public function test_redirect_never_exposes_unpublished_or_expired_offer(): void
    {
        $offer = $this->offer();
        $offer->update(['slug' => 'new-name']);
        $offer->update(['is_published' => false]);

        $this->get('/offers/first-name')->assertNotFound();
        $this->get('/offers/new-name')->assertNotFound();

        $offer->update(['is_published' => true, 'ends_at' => now()->subDay()]);
        $this->get('/offers/first-name')->assertNotFound();
    }

    public function test_draft_slug_change_does_not_create_public_redirect(): void
    {
        $offer = $this->offer(published: false);
        $offer->update(['slug' => 'second-name']);

        $this->assertDatabaseCount('offer_slug_redirects', 0);
        $this->get('/offers/first-name')->assertNotFound();
    }

    public function test_admin_cannot_reuse_slug_reserved_for_published_redirect(): void
    {
        $offer = $this->offer();
        $offer->update(['slug' => 'new-name']);

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(CreateOffer::class)
            ->fillForm([
                'brand_id' => $offer->brand_id,
                'category_id' => $offer->category_id,
                'title' => 'Another offer',
                'slug' => 'first-name',
                'summary' => 'Another summary',
                'description' => 'Another description',
                'offer_type' => 'discount',
                'partner_url' => 'https://example.org/other',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);

        $this->assertDatabaseCount('offers', 1);
    }

    public function test_deleting_offer_removes_retired_slug_history(): void
    {
        $offer = $this->offer();
        $offer->update(['slug' => 'new-name']);
        $offer->delete();

        $this->assertDatabaseCount('offer_slug_redirects', 0);
        $this->get('/offers/first-name')->assertNotFound();
    }
}
