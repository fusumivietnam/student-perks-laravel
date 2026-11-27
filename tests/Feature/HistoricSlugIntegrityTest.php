<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Page;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HistoricSlugIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_offer_historic_slug_cannot_be_reused_via_direct_model_create_or_update(): void
    {
        $brand = Brand::create(['name' => 'Partner', 'slug' => 'partner', 'is_published' => true]);
        $category = Category::create(['name' => 'Learning', 'slug' => 'learning', 'is_published' => true]);
        $base = [
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Student offer',
            'summary' => 'Summary',
            'description' => 'Description',
            'partner_url' => 'https://example.org/student',
            'is_published' => true,
        ];
        $original = Offer::create($base + ['slug' => 'original-offer']);
        $original->update(['slug' => 'renamed-offer']);

        $this->expectHistoricSlugIsReserved(
            fn () => Offer::create($base + ['slug' => 'original-offer']),
            fn () => Offer::create($base + ['slug' => 'another-offer']),
            'original-offer',
            'offer_slug_redirects',
        );

        $this->get(route('offers.show', 'original-offer'))
            ->assertStatus(301)
            ->assertRedirect(route('offers.show', 'renamed-offer'));
    }

    public function test_article_historic_slug_is_reserved_for_all_model_write_paths(): void
    {
        $base = [
            'title' => 'Student guide',
            'summary' => 'A guide',
            'body' => 'Useful content',
            'is_published' => true,
        ];
        $original = Article::create($base + ['slug' => 'old-guide']);
        $original->update(['slug' => 'new-guide']);

        $this->expectHistoricSlugIsReserved(
            fn () => Article::create($base + ['slug' => 'old-guide']),
            fn () => Article::create($base + ['slug' => 'another-guide']),
            'old-guide',
            'article_slug_redirects',
        );

        $this->get(route('articles.show', 'old-guide'))
            ->assertStatus(301)
            ->assertRedirect(route('articles.show', 'new-guide'));
    }

    public function test_page_historic_slug_is_reserved_for_all_model_write_paths(): void
    {
        $base = [
            'title' => 'Information',
            'body' => 'Information content',
            'is_published' => true,
        ];
        $original = Page::create($base + ['slug' => 'old-information']);
        $original->update(['slug' => 'new-information']);

        $this->expectHistoricSlugIsReserved(
            fn () => Page::create($base + ['slug' => 'old-information']),
            fn () => Page::create($base + ['slug' => 'another-information']),
            'old-information',
            'page_slug_redirects',
        );

        $this->get(route('pages.show', 'old-information'))
            ->assertStatus(301)
            ->assertRedirect(route('pages.show', 'new-information'));
    }

    public function test_slug_changes_roll_back_if_redirect_insert_fails(): void
    {
        $brand = Brand::create(['name' => 'Partner', 'slug' => 'partner', 'is_published' => true]);
        $category = Category::create(['name' => 'Learning', 'slug' => 'learning', 'is_published' => true]);
        $offer = Offer::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'title' => 'Offer',
            'slug' => 'old-offer',
            'summary' => 'Summary',
            'description' => 'Description',
            'partner_url' => 'https://example.org/student',
            'is_published' => true,
        ]);
        $article = Article::create([
            'title' => 'Guide', 'slug' => 'old-guide',
            'summary' => 'Summary', 'body' => 'Body', 'is_published' => true,
        ]);
        $page = Page::create([
            'title' => 'Information', 'slug' => 'old-page',
            'body' => 'Body', 'is_published' => true,
        ]);

        foreach ([
            [$offer, 'offer_slug_redirects', 'offer_id'],
            [$article, 'article_slug_redirects', 'article_id'],
            [$page, 'page_slug_redirects', 'page_id'],
        ] as [$record, $table, $foreignKey]) {
            $oldSlug = $record->slug;
            // Force a duplicate-key failure during redirect insertion.
            DB::table($table)->insert([
                $foreignKey => $record->id,
                'slug' => $oldSlug,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            try {
                $record->update(['slug' => 'replacement-'.$oldSlug]);
                $this->fail('Expected redirect insert to fail.');
            } catch (\\Illuminate\\Database\\QueryException $e) {
                $this->assertSame('23505', (string) $e->getCode());
            }

            $this->assertSame($oldSlug, $record->fresh()->slug);
            $this->assertSame(1, DB::table($table)->where('slug', $oldSlug)->count());
        }
    }

    private function expectHistoricSlugIsReserved(
        callable $createInvalid,
        callable $createValid,
        string $historicSlug,
        string $redirectTable,
    ): void {
        try {
            $createInvalid();
            $this->fail('A historic slug was accepted on a new record.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('reserved', $e->getMessage());
        }

        $valid = $createValid();
        $validSlug = $valid->slug;

        try {
            $valid->update(['slug' => $historicSlug]);
            $this->fail('A historic slug was accepted on an existing record.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('reserved', $e->getMessage());
        }

        $this->assertSame($validSlug, $valid->fresh()->slug);
        $this->assertSame(1, DB::table($redirectTable)->where('slug', $historicSlug)->count());
    }
}
