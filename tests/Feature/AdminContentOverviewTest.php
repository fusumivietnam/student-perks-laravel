<?php

namespace Tests\Feature;

use App\Filament\Widgets\ContentOverview;
use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminContentOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_real_publication_scopes_and_reports_content_counts(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $brand = Brand::create(['name' => 'Example Academy', 'slug' => 'example-academy', 'is_published' => true]);
        $category = Category::create(['name' => 'Education', 'slug' => 'education', 'is_published' => true]);

        foreach ([
            ['slug' => 'visible', 'title' => 'Visible offer'],
            ['slug' => 'draft', 'title' => 'Draft offer', 'is_published' => false],
            ['slug' => 'scheduled', 'title' => 'Scheduled offer', 'published_at' => now()->addDay()],
            ['slug' => 'expired', 'title' => 'Expired offer', 'ends_at' => now()->subDay()],
        ] as $attributes) {
            Offer::create(array_merge([
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'title' => 'Example student offer',
                'summary' => 'Example benefit',
                'description' => 'Example terms',
                'slug' => 'example-offer',
                'partner_url' => 'https://example.org/offer',
                'offer_type' => 'discount',
                'is_published' => true,
            ], $attributes));
        }

        Article::create([
            'title' => 'Visible guide',
            'slug' => 'visible-guide',
            'summary' => 'Guide summary',
            'body' => 'Guide body',
            'is_published' => true,
        ]);
        Article::create([
            'title' => 'Future guide',
            'slug' => 'future-guide',
            'summary' => 'Guide summary',
            'body' => 'Guide body',
            'is_published' => true,
            'published_at' => now()->addDay(),
        ]);
        Page::create([
            'title' => 'Privacy',
            'slug' => 'privacy',
            'body' => 'Privacy copy',
            'is_published' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(ContentOverview::class)
            ->assertSee('Live offers')
            ->assertSee('Offers not live')
            ->assertSee('Published brands')
            ->assertSee('Published categories')
            ->assertSee('Live articles')
            ->assertSee('Published pages')
            ->assertSee('Currently visible in the public directory')
            ->assertSee('Draft, scheduled, expired, or hidden by taxonomy');

        $widget = new class extends ContentOverview
        {
            public function values(): array
            {
                return array_map(fn ($stat) => $stat->getValue(), $this->getStats());
            }
        };

        $this->assertSame([1, 3, 1, 1, 1, 1], $widget->values());
    }

    public function test_admin_dashboard_requires_login_and_admin_role(): void
    {
        $this->get('/admin')->assertRedirect();

        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->get('/admin')->assertForbidden();

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin')->assertOk();
    }
}
