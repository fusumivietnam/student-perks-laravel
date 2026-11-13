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

class FilamentOfferWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_scheduled_offer_edit_it_and_publish_to_public_catalog(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $brand = Brand::create(['name' => 'Demo Studio', 'slug' => 'demo-studio', 'is_published' => true]);
        $category = Category::create(['name' => 'Software', 'slug' => 'software', 'is_published' => true]);

        $this->actingAs($admin);

        Livewire::test(CreateOffer::class)
            ->fillForm([
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'title' => 'Preview student offer',
                'slug' => 'preview-student-offer',
                'summary' => 'Safe example summary',
                'description' => 'Eligibility and terms are outlined here.',
                'offer_type' => 'discount',
                'partner_url' => 'https://example.org/student',
                'is_published' => true,
                'published_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $offer = Offer::where('slug', 'preview-student-offer')->firstOrFail();
        $this->get('/offers/preview-student-offer')->assertNotFound();

        Livewire::test(EditOffer::class, ['record' => $offer->getRouteKey()])
            ->fillForm([
                'title' => 'Updated preview offer',
                'published_at' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/offers/preview-student-offer')->assertOk()->assertSee('Updated preview offer');

        Livewire::test(EditOffer::class, ['record' => $offer->getRouteKey()])
            ->fillForm(['is_published' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/offers/preview-student-offer')->assertNotFound();
    }

    public function test_offer_cannot_expire_before_or_at_its_scheduled_publication_time(): void
    {
        $brand = Brand::create(['name' => 'Schedule Brand', 'slug' => 'schedule-brand', 'is_published' => true]);
        $category = Category::create(['name' => 'Courses', 'slug' => 'courses', 'is_published' => true]);

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $data = [
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Future student offer',
            'slug' => 'future-student-offer',
            'summary' => 'A future offer',
            'description' => 'Student terms',
            'offer_type' => 'discount',
            'partner_url' => 'https://example.org/student',
            'is_published' => true,
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'published_at' => now()->addDays(4)->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ];

        Livewire::test(CreateOffer::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasFormErrors(['ends_at']);

        $this->assertDatabaseCount('offers', 0);

        Livewire::test(CreateOffer::class)
            ->fillForm(array_replace($data, [
                'ends_at' => now()->addDays(6)->format('Y-m-d H:i:s'),
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $offer = Offer::where('slug', 'future-student-offer')->firstOrFail();
        $this->get('/offers/future-student-offer')->assertNotFound();

        Livewire::test(EditOffer::class, ['record' => $offer->getRouteKey()])
            ->fillForm(['published_at' => now()->addDays(7)->format('Y-m-d H:i:s')])
            ->call('save')
            ->assertHasFormErrors(['ends_at']);

        $this->assertTrue($offer->fresh()->published_at->lt($offer->fresh()->ends_at));

        Livewire::test(EditOffer::class, ['record' => $offer->getRouteKey()])
            ->fillForm(['published_at' => null])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_admin_form_rejects_non_https_partner_links_and_invalid_slugs(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $brand = Brand::create(['name' => 'Demo Studio', 'slug' => 'demo-studio', 'is_published' => true]);
        $category = Category::create(['name' => 'Software', 'slug' => 'software', 'is_published' => true]);
        $this->actingAs($admin);

        Livewire::test(CreateOffer::class)
            ->fillForm([
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'title' => 'Invalid offer',
                'slug' => 'Invalid Slug',
                'summary' => 'Example',
                'description' => 'Example',
                'offer_type' => 'discount',
                'partner_url' => 'http://example.org/student',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug', 'partner_url']);

        $this->assertDatabaseCount('offers', 0);
    }
}
