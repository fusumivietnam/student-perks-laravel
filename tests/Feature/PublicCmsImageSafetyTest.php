<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Brand;
use App\Support\PublicCmsImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicCmsImageSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_resolver_only_returns_existing_image_urls_in_the_expected_public_directory(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('brands/verified-brand.png', 'valid fixture bytes');
        Storage::disk('public')->put('articles/verified-guide.webp', 'valid fixture bytes');
        Storage::disk('public')->put('brands/document.txt', 'not an image');

        $this->assertSame(
            Storage::disk('public')->url('brands/verified-brand.png'),
            PublicCmsImage::url('brands/verified-brand.png', 'brands'),
        );
        $this->assertSame(
            Storage::disk('public')->url('articles/verified-guide.webp'),
            PublicCmsImage::url('articles/verified-guide.webp', 'articles'),
        );

        foreach ([
            [null, 'brands'],
            ['', 'brands'],
            ['brands/missing.png', 'brands'],
            ['brands/document.txt', 'brands'],
            ['brands/../articles/verified-guide.webp', 'brands'],
            ['brands//verified-brand.png', 'brands'],
            ['articles/verified-guide.webp', 'brands'],
            ['https://external.example/fake.png', 'brands'],
            ['brands/verified-brand.png?tracking=1', 'brands'],
            ['brands/verified-brand.png', 'uploads'],
        ] as [$path, $directory]) {
            $this->assertNull(PublicCmsImage::url($path, $directory));
        }
    }

    public function test_brand_logo_falls_back_to_initials_for_missing_or_untrusted_paths(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('brands/real-logo.png', 'logo bytes');

        $brand = Brand::create([
            'name' => 'Visible Partner',
            'slug' => 'visible-partner',
            'is_published' => true,
            'logo_path' => 'brands/real-logo.png',
        ]);

        $url = Storage::disk('public')->url('brands/real-logo.png');
        $this->get(route('brands.index'))->assertOk()->assertSee($url);

        $brand->update(['logo_path' => 'brands/missing.png']);
        $this->get(route('brands.index'))->assertOk()
            ->assertDontSee('brands/missing.png')
            ->assertSee('VI');

        $brand->update(['logo_path' => 'articles/not-a-logo.png']);
        $this->get(route('brands.index'))->assertOk()
            ->assertDontSee('articles/not-a-logo.png')
            ->assertSee('VI');
    }

    public function test_article_cover_only_renders_when_file_exists_in_articles_directory(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('articles/real-cover.jpg', 'cover bytes');
        Storage::disk('public')->put('brands/other-image.png', 'other bytes');

        $article = Article::create([
            'title' => 'Image safety guide',
            'slug' => 'image-safety-guide',
            'summary' => 'Student guide to images',
            'body' => 'Details',
            'cover_path' => 'articles/real-cover.jpg',
            'is_published' => true,
        ]);

        $url = Storage::disk('public')->url('articles/real-cover.jpg');
        $this->get(route('articles.show', $article->slug))->assertOk()->assertSee($url);
        $this->get(route('articles.index'))->assertOk()->assertSee($url);

        $article->update(['cover_path' => 'brands/other-image.png']);
        $this->get(route('articles.show', $article->slug))->assertOk()
            ->assertDontSee('brands/other-image.png');
        $this->get(route('articles.index'))->assertOk()
            ->assertDontSee('brands/other-image.png');

        $article->update(['cover_path' => 'articles/missing.jpg']);
        $this->get(route('articles.show', $article->slug))->assertOk()
            ->assertDontSee('articles/missing.jpg');
    }
}
