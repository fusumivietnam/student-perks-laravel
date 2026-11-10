<?php

namespace Tests\Feature;

use App\Filament\Resources\Articles\Pages\ManageArticles;
use App\Filament\Resources\Brands\Pages\ManageBrands;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Resources\Pages\Pages\ManagePages;
use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CmsRouteSlugValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_editorial_create_actions_reject_slugs_that_break_public_routes(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        foreach ($this->resources() as [$component, $model, $payload, $route]) {
            foreach (['contains space', 'path/segment', 'Upper-Case', 'bad_slug'] as $slug) {
                Livewire::test($component)
                    ->callAction('create', data: array_merge($payload, ['slug' => $slug]))
                    ->assertHasFormErrors(['slug']);

                $this->assertSame(0, $model::count(), "$model accepted an invalid slug: $slug");
            }
        }
    }

    public function test_all_editorial_create_actions_accept_valid_public_slugs(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        foreach ($this->resources() as [$component, $model, $payload, $route]) {
            Livewire::test($component)
                ->callAction('create', data: array_merge($payload, [
                    'slug' => 'student-offer-2026',
                ]))
                ->assertHasNoFormErrors();

            $this->assertSame(1, $model::where('slug', 'student-offer-2026')->count());
            $this->get(route($route, 'student-offer-2026'))->assertOk();
        }
    }

    private function resources(): array
    {
        return [
            [ManageBrands::class, Brand::class, [
                'name' => 'Campus Brand',
                'is_published' => true,
            ], 'brands.show'],
            [ManageCategories::class, Category::class, [
                'name' => 'Learning Category',
                'is_published' => true,
            ], 'categories.show'],
            [ManageArticles::class, Article::class, [
                'title' => 'Campus Guide',
                'summary' => 'Student guide summary',
                'body' => 'Student guide details',
                'is_published' => true,
            ], 'articles.show'],
            [ManagePages::class, Page::class, [
                'title' => 'Campus Policy',
                'body' => 'Policy details',
                'is_published' => true,
            ], 'pages.show'],
        ];
    }
}
