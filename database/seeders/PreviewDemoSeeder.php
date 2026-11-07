<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Explicit, non-destructive fictional content for local/Codespaces UI acceptance.
 * Run: php artisan db:seed --class=PreviewDemoSeeder
 */
class PreviewDemoSeeder extends Seeder
{
    public function run(): void
    {
        $url = (string) config('app.url');
        $host = parse_url($url, PHP_URL_HOST);

        if (! app()->environment('local')
            || ! in_array($host, ['localhost', '127.0.0.1'], true)
                && (! is_string($host) || ! str_ends_with($host, '.app.github.dev'))) {
            throw new RuntimeException('Preview demo content is restricted to local or Codespaces APP_URL.');
        }

        // firstOrCreate preserves any content the editor has since changed in Filament.
        $brands = [
            ['name' => 'Demo Learning Lab', 'slug' => 'demo-learning-lab', 'description' => 'Fictional learning brand used for layout testing.'],
            ['name' => 'Demo Cloud Studio', 'slug' => 'demo-cloud-studio', 'description' => 'Fictional productivity brand used for preview only.'],
            ['name' => 'Demo Campus Goods', 'slug' => 'demo-campus-goods', 'description' => 'Fictional campus essentials brand.'],
        ];
        $categories = [
            ['name' => 'Software (Demo)', 'slug' => 'demo-software'],
            ['name' => 'Learning (Demo)', 'slug' => 'demo-learning'],
            ['name' => 'Lifestyle (Demo)', 'slug' => 'demo-lifestyle'],
        ];

        foreach ($brands as $data) {
            Brand::firstOrCreate(['slug' => $data['slug']], $data + ['is_published' => true]);
        }
        foreach ($categories as $data) {
            Category::firstOrCreate(['slug' => $data['slug']], $data + ['is_published' => true]);
        }

        $brandIds = Brand::whereIn('slug', array_column($brands, 'slug'))->pluck('id', 'slug');
        $categoryIds = Category::whereIn('slug', array_column($categories, 'slug'))->pluck('id', 'slug');

        $offers = [
            ['demo-student-design-kit', 'Student Design Kit — DEMO', 'demo-cloud-studio', 'demo-software', true],
            ['demo-online-study-pack', 'Online Study Pack — DEMO', 'demo-learning-lab', 'demo-learning', true],
            ['demo-campus-organizer', 'Campus Organizer — DEMO', 'demo-campus-goods', 'demo-lifestyle', true],
            ['demo-productivity-bundle', 'Productivity Bundle — DEMO', 'demo-cloud-studio', 'demo-software', true],
            ['demo-unpublished-offer', 'Draft Offer — DEMO', 'demo-learning-lab', 'demo-learning', false],
        ];

        foreach ($offers as [$slug, $title, $brandSlug, $categorySlug, $published]) {
            Offer::firstOrCreate(['slug' => $slug], [
                'title' => $title,
                'brand_id' => $brandIds[$brandSlug],
                'category_id' => $categoryIds[$categorySlug],
                'summary' => 'Fictional sample for testing Student Perks Hub. Not a real discount or partner promotion.',
                'description' => 'DEMO CONTENT ONLY. This preview listing is not an actual offer. No student eligibility is asserted.',
                'offer_type' => 'discount',
                'offer_value' => 'Demo only',
                'eligibility' => 'Preview testing only. No actual student benefit.',
                'redemption_steps' => 'No redemption available. Do not submit personal information.',
                'partner_url' => 'https://example.org/',
                'is_published' => $published,
            ]);
        }

        foreach ([
            ['slug' => 'demo-how-to-compare-offers', 'title' => 'How to compare student offers — DEMO',
                'summary' => 'Example editorial content for testing article layout.',
                'body' => 'DEMO CONTENT ONLY. Check the publisher, eligibility requirements and expiry date before using any real promotion.',
                'is_published' => true],
            ['slug' => 'demo-editorial-draft', 'title' => 'Editorial draft — DEMO',
                'summary' => 'Draft-only example for visibility testing.',
                'body' => 'This draft should not appear on public article pages.',
                'is_published' => false],
        ] as $data) {
            Article::firstOrCreate(['slug' => $data['slug']], $data);
        }

        $this->command?->info('Preview demo seeded: 3 fictional brands, 3 categories, 5 offers (one draft), 2 articles (one draft). Existing records were not changed.');
    }
}
