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

class OfferStatusFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_publication_scopes_partition_all_offer_states_and_detect_expiring_soon(): void
    {
        $brand = Brand::create(['name' => 'Published brand', 'slug' => 'published-brand', 'is_published' => true]);
        $hiddenBrand = Brand::create(['name' => 'Hidden brand', 'slug' => 'hidden-brand', 'is_published' => false]);
        $category = Category::create(['name' => 'Published category', 'slug' => 'published-category', 'is_published' => true]);

        $make = function (string $slug, array $attributes = []) use ($brand, $category): Offer {
            return Offer::create(array_merge([
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'title' => $slug,
                'slug' => $slug,
                'summary' => 'Student offer',
                'description' => 'Terms',
                'offer_type' => 'discount',
                'partner_url' => 'https://example.org/offer',
                'is_published' => true,
            ], $attributes));
        };

        $draft = $make('draft', ['is_published' => false]);
        $live = $make('live');
        $soon = $make('soon', ['ends_at' => now()->addDays(3)]);
        $later = $make('later', ['ends_at' => now()->addDays(20)]);
        $scheduled = $make('scheduled', ['published_at' => now()->addDay()]);
        $starting = $make('starting', ['starts_at' => now()->addDay()]);
        $expired = $make('expired', ['ends_at' => now()->subDay()]);
        $hidden = $make('hidden', ['brand_id' => $hiddenBrand->id]);

        $expected = [
            'draft' => [$draft->id],
            'live' => [$live->id, $soon->id, $later->id],
            'scheduled' => [$scheduled->id, $starting->id],
            'expired' => [$expired->id],
            'hidden' => [$hidden->id],
        ];

        foreach ($expected as $status => $ids) {
            $this->assertEqualsCanonicalizing(
                $ids,
                Offer::withPublicationStatus($status)->pluck('id')->all(),
                "Incorrect offer status query: {$status}",
            );
        }

        $this->assertEqualsCanonicalizing([$soon->id], Offer::expiringSoon()->pluck('id')->all());

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ListOffers::class)
            ->filterTable('publication_status', 'scheduled')
            ->assertCanSeeTableRecords([$scheduled, $starting])
            ->assertCanNotSeeTableRecords([$draft, $live, $expired, $hidden]);

        Livewire::test(ListOffers::class)
            ->filterTable('expiring_soon', true)
            ->assertCanSeeTableRecords([$soon])
            ->assertCanNotSeeTableRecords([$live, $later, $scheduled, $expired, $draft, $hidden]);
    }

    public function test_filter_access_is_restricted_to_administrators(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->get('/admin/offers')->assertForbidden();
    }
}
