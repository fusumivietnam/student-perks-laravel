<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsRouteAuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_non_admin_cannot_access_any_cms_resource_or_private_preview(): void
    {
        $brand = Brand::create(['name' => 'Partner', 'slug' => 'partner', 'is_published' => true]);
        $category = Category::create(['name' => 'Learning', 'slug' => 'learning', 'is_published' => true]);
        $offer = Offer::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Admin-only draft',
            'slug' => 'admin-only-draft',
            'summary' => 'Private offer',
            'description' => 'Not public',
            'partner_url' => 'https://example.org/draft',
            'is_published' => false,
        ]);
        $article = Article::create([
            'title' => 'Private article',
            'slug' => 'private-article',
            'summary' => 'Not public',
            'body' => 'Confidential draft text',
            'is_published' => false,
        ]);
        $page = Page::create([
            'title' => 'Private page',
            'slug' => 'private-page',
            'body' => 'Unpublished information',
            'is_published' => false,
        ]);

        $adminRoutes = [
            '/admin',
            '/admin/offers',
            '/admin/offers/create',
            '/admin/offers/'.$offer->id.'/edit',
            '/admin/brands',
            '/admin/categories',
            '/admin/articles',
            '/admin/pages',
        ];
        $previewRoutes = [
            route('offers.preview', $offer),
            route('articles.preview', $article),
            route('pages.preview', $page),
        ];

        foreach ($previewRoutes as $url) {
            $this->get($url)->assertForbidden();
        }
        foreach ($adminRoutes as $url) {
            $this->get($url)->assertRedirect();
        }

        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);
        foreach (array_merge($adminRoutes, $previewRoutes) as $url) {
            $this->get($url)->assertForbidden();
        }

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        foreach ($adminRoutes as $url) {
            $this->get($url)->assertOk();
        }
        foreach ($previewRoutes as $url) {
            $response = $this->get($url);
            $response->assertOk()
                ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');

            // Symfony may reorder Cache-Control directives; assert meaning, not order.
            $cacheControl = (string) $response->headers->get('Cache-Control');
            foreach (['private', 'no-store', 'no-cache', 'must-revalidate'] as $directive) {
                $this->assertContains($directive, array_map('trim', explode(',', $cacheControl)));
            }
        }
    }

    public function test_revoking_admin_status_denies_cms_and_preview_access_on_subsequent_requests(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $page = Page::create([
            'title' => 'Restricted page',
            'slug' => 'restricted-page',
            'body' => 'Only admin preview',
            'is_published' => false,
        ]);
        $this->actingAs($admin);
        $this->get('/admin/pages')->assertOk();
        $this->get(route('pages.preview', $page))->assertOk();

        $admin->forceFill(['is_admin' => false])->save();
        $this->get('/admin/pages')->assertForbidden();
        $this->get(route('pages.preview', $page))->assertForbidden();
    }
}
