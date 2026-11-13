<?php

namespace Tests\Feature;

use App\Filament\Resources\Offers\Pages\CreateOffer;
use App\Filament\Resources\Offers\Pages\EditOffer;
use App\Filament\Resources\Offers\Pages\ListOffers;
use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaunchAcceptanceJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_offer_stays_private_until_admin_publishes_then_disappears_when_unpublished(): void
    {
        $brand = Brand::create(['name' => 'Student Academy', 'slug' => 'student-academy', 'is_published' => true]);
        $category = Category::create(['name' => 'Software', 'slug' => 'software', 'is_published' => true]);

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(CreateOffer::class)
            ->fillForm([
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'title' => 'Verified student plan',
                'slug' => 'verified-student-plan',
                'summary' => 'A student learning plan',
                'description' => 'Apply using the partner website',
                'offer_type' => 'discount',
                'eligibility' => 'Eligible students',
                'redemption_steps' => 'Verify eligibility with the partner',
                'partner_url' => 'https://example.org/education',
                'is_published' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $offer = Offer::where('slug', 'verified-student-plan')->firstOrFail();

        $this->get(route('offers.preview', $offer))->assertOk()
            ->assertSee('Private admin preview')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');

        foreach (['/', '/offers', '/search?q=Verified', '/brands/student-academy', '/categories/software'] as $path) {
            $this->get($path)->assertOk()->assertDontSee('Verified student plan');
        }
        $this->get('/offers/verified-student-plan')->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee('/offers/verified-student-plan');

        Livewire::test(ListOffers::class)
            ->callTableAction('publish', $offer)
            ->assertHasNoTableActionErrors();

        $this->assertTrue($offer->fresh()->is_published);
        foreach (['/', '/offers', '/search?q=Verified', '/brands/student-academy', '/categories/software'] as $path) {
            $this->get($path)->assertOk()->assertSee('Verified student plan');
        }
        $this->get('/offers/verified-student-plan')->assertOk()
            ->assertSee('Eligible students')
            ->assertSee('Verify eligibility with the partner')
            ->assertSee('awaiting editorial review')
            ->assertDontSee('href="https://example.org/education"', false)
            ->assertSee('rel="canonical"', false);

        Livewire::test(ListOffers::class)
            ->callTableAction('confirmPartnerReview', $offer)
            ->assertHasNoTableActionErrors();

        $this->get('/offers/verified-student-plan')->assertOk()
            ->assertSee('rel="noopener noreferrer sponsored"', false)
            ->assertSee('href="https://example.org/education"', false);
        $this->get('/sitemap.xml')->assertSee('/offers/verified-student-plan');

        Livewire::test(EditOffer::class, ['record' => $offer->getRouteKey()])
            ->fillForm(['summary' => 'Updated student terms'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->get('/offers/verified-student-plan')->assertOk()->assertSee('Updated student terms');

        Livewire::test(ListOffers::class)
            ->callTableAction('unpublish', $offer)
            ->assertHasNoTableActionErrors();

        $this->assertFalse($offer->fresh()->is_published);
        $this->get('/offers/verified-student-plan')->assertNotFound();
        $this->get('/offers')->assertDontSee('Verified student plan');
        $this->get('/sitemap.xml')->assertDontSee('/offers/verified-student-plan');
    }

    public function test_non_admin_cannot_access_admin_or_editorial_previews_and_drafts_are_not_indexed(): void
    {
        $draftArticle = Article::create([
            'title' => 'Unpublished instruction',
            'slug' => 'unpublished-instruction',
            'summary' => 'Internal-only content',
            'body' => 'Do not publish this draft',
            'is_published' => false,
        ]);
        $draftPage = Page::create([
            'title' => 'Internal policy',
            'slug' => 'internal-policy',
            'body' => 'Unapproved draft text',
            'is_published' => false,
        ]);

        $guestRequests = [
            route('articles.preview', $draftArticle),
            route('pages.preview', $draftPage),
        ];
        foreach ($guestRequests as $url) {
            $this->get($url)->assertForbidden();
        }

        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->get('/admin')->assertForbidden();
        $this->get('/admin/offers')->assertForbidden();
        foreach ($guestRequests as $url) {
            $this->get($url)->assertForbidden();
        }

        $this->get('/articles/unpublished-instruction')->assertNotFound();
        $this->get('/pages/internal-policy')->assertNotFound();
        $this->get('/articles')->assertDontSee('Unpublished instruction');
        $this->get('/sitemap.xml')
            ->assertDontSee('/articles/unpublished-instruction')
            ->assertDontSee('/pages/internal-policy');

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin')->assertOk();
        $this->get(route('articles.preview', $draftArticle))->assertOk()
            ->assertSee('Do not publish this draft')
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');
        $this->get(route('pages.preview', $draftPage))->assertOk()
            ->assertSee('Unapproved draft text')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }
}
