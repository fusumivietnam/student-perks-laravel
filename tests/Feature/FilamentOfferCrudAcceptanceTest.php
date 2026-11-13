<?php

namespace Tests\Feature;

use App\Filament\Resources\Offers\Pages\CreateOffer;
use App\Filament\Resources\Offers\Pages\EditOffer;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentOfferCrudAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_edits_publishes_and_unpublishes_offer_through_filament(): void
    {
        $brand = Brand::create([
            'name' => 'Acceptance Partner',
            'slug' => 'acceptance-partner',
            'is_published' => true,
        ]);
        $category = Category::create([
            'name' => 'Acceptance Category',
            'slug' => 'acceptance-category',
            'is_published' => true,
        ]);
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin);

        Livewire::test(CreateOffer::class)
            ->fillForm([
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'title' => 'Acceptance Student Offer',
                'slug' => 'acceptance-student-offer',
                'summary' => 'An offer created through the admin form.',
                'description' => 'Available to enrolled students.',
                'offer_type' => 'discount',
                'partner_url' => 'https://example.org/education',
                'is_published' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $offer = Offer::where('slug', 'acceptance-student-offer')->firstOrFail();
        $this->assertFalse($offer->is_published);
        $this->get(route('offers.index'))->assertDontSee($offer->title);
        $this->get(route('offers.show', $offer->slug))->assertNotFound();

        Livewire::test(EditOffer::class, ['record' => $offer->getRouteKey()])
            ->fillForm([
                'title' => 'Acceptance Student Offer Updated',
                'summary' => 'The edited offer is now live.',
                'is_published' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $offer->refresh();
        $this->assertTrue($offer->is_published);
        $this->assertSame('Acceptance Student Offer Updated', $offer->title);
        $this->get(route('offers.index'))->assertOk()->assertSee($offer->title);
        $this->get(route('offers.show', $offer->slug))->assertOk()
            ->assertSee('The edited offer is now live.');

        Livewire::test(EditOffer::class, ['record' => $offer->getRouteKey()])
            ->fillForm(['is_published' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($offer->fresh()->is_published);
        $this->get(route('offers.index'))->assertDontSee($offer->title);
        $this->get(route('offers.show', $offer->slug))->assertNotFound();
    }

    public function test_non_admin_cannot_use_filament_offer_editor(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateOffer::class)->assertForbidden();
    }
}
