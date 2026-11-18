<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Offer;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Browser-only login fixture; never included in DatabaseSeeder or preview scripts.
 * Requires an explicit marker and the loopback-only disposable Compose endpoint.
 */
class BrowserCiAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (env('E2E_TEST_STACK') !== '1'
            || config('app.url') !== 'http://127.0.0.1:18080'
            || ! in_array(config('app.env'), ['local', 'testing'], true)) {
            throw new RuntimeException('Browser CI admin fixture is restricted to the disposable CI stack.');
        }

        $admin = User::firstOrCreate(
            ['email' => 'browser-admin@ci.invalid'],
            ['name' => 'Browser CI Admin', 'password' => 'ci-browser-password-only'],
        );

        $admin->forceFill(['is_admin' => true])->save();

        $regular = User::firstOrCreate(
            ['email' => 'browser-user@ci.invalid'],
            ['name' => 'Browser CI User', 'password' => 'ci-browser-password-only'],
        );
        $regular->forceFill(['is_admin' => false])->save();

        Offer::firstOrCreate(
            ['slug' => 'browser-e2e-draft-offer'],
            [
                'brand_id' => Brand::where('slug', 'demo-learning-lab')->firstOrFail()->id,
                'category_id' => Category::where('slug', 'demo-learning')->firstOrFail()->id,
                'title' => 'Browser CI Draft Offer',
                'summary' => 'A fictional draft exclusively used for browser write-flow acceptance.',
                'description' => 'No real offer is issued by this CI-only fixture.',
                'offer_type' => 'discount',
                'eligibility' => 'This is a browser-only test.',
                'redemption_steps' => 'This is a browser-only test.',
                'partner_url' => 'https://example.org/',
                'is_published' => false,
            ],
        );

        // Exclusive browser-CI Page fixtures; never alter editor-owned preview content.
        \App\Models\Page::firstOrCreate(
            ['slug' => 'browser-ci-draft-page'],
            [
                'title' => 'Browser CI Draft Page',
                'body' => 'Private page fixture for browser acceptance.',
                'is_published' => false,
            ],
        );
        \App\Models\Page::firstOrCreate(
            ['slug' => 'browser-ci-live-page'],
            [
                'title' => 'Browser CI Live Page',
                'body' => 'Published fictional browser acceptance page.',
                'is_published' => true,
            ],
        );

        // Published offers with unpublished parents must remain invisible in every public journey.
        $visibleBrand = Brand::firstOrCreate(
            ['slug' => 'browser-ci-visible-brand'],
            ['name' => 'Browser CI Visible Brand', 'is_published' => true],
        );
        $hiddenBrand = Brand::firstOrCreate(
            ['slug' => 'browser-ci-hidden-brand'],
            ['name' => 'Browser CI Hidden Brand', 'is_published' => false],
        );
        $visibleCategory = Category::firstOrCreate(
            ['slug' => 'browser-ci-visible-category'],
            ['name' => 'Browser CI Visible Category', 'is_published' => true],
        );
        $hiddenCategory = Category::firstOrCreate(
            ['slug' => 'browser-ci-hidden-category'],
            ['name' => 'Browser CI Hidden Category', 'is_published' => false],
        );

        foreach ([
            ['browser-ci-hidden-brand-offer', $hiddenBrand->id, $visibleCategory->id],
            ['browser-ci-hidden-category-offer', $visibleBrand->id, $hiddenCategory->id],
        ] as [$slug, $brandId, $categoryId]) {
            Offer::firstOrCreate(['slug' => $slug], [
                'brand_id' => $brandId,
                'category_id' => $categoryId,
                'title' => 'Browser CI Parent Visibility '.$slug,
                'summary' => 'A fictional offer that must never appear publicly.',
                'description' => 'Browser-only acceptance fixture.',
                'partner_url' => 'https://example.org/',
                'is_published' => true,
            ]);
        }

        $this->command?->info('Disposable CI browser accounts and draft fixture created. Never use in real deployments.');
    }
}
