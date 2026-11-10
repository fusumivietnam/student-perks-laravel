<?php

namespace Tests\Feature;

use App\Filament\Resources\Articles\Pages\ManageArticles;
use App\Filament\Resources\Brands\Pages\ManageBrands;
use App\Models\Article;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentMediaUploadAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_editor_can_upload_article_cover_and_public_pages_render_stored_file(): void
    {
        Livewire::test(ManageArticles::class)
            ->callAction('create', data: [
                'title' => 'Student media guide',
                'slug' => 'student-media-guide',
                'summary' => 'Guide with cover image',
                'body' => 'Read this student resource',
                'is_published' => true,
                'cover_path' => [$this->validImage('cover.png')],
            ])
            ->assertHasNoFormErrors();

        $article = Article::where('slug', 'student-media-guide')->firstOrFail();
        $this->assertNotEmpty($article->cover_path);
        $this->assertStringStartsWith('articles/', $article->cover_path);
        Storage::disk('public')->assertExists($article->cover_path);

        $src = Storage::disk('public')->url($article->cover_path);
        $this->get('/articles')->assertOk()->assertSee('src="'.$src.'"', false);
        $this->get('/articles/student-media-guide')->assertOk()->assertSee('src="'.$src.'"', false);
    }

    public function test_editor_can_upload_brand_logo_and_public_directory_renders_file(): void
    {
        Livewire::test(ManageBrands::class)
            ->callAction('create', data: [
                'name' => 'Campus Media Brand',
                'slug' => 'campus-media-brand',
                'is_published' => true,
                'logo_path' => [$this->validImage('brand.png')],
            ])
            ->assertHasNoFormErrors();

        $brand = Brand::where('slug', 'campus-media-brand')->firstOrFail();
        $this->assertNotEmpty($brand->logo_path);
        $this->assertStringStartsWith('brands/', $brand->logo_path);
        Storage::disk('public')->assertExists($brand->logo_path);
        $this->get('/brands')->assertOk()
            ->assertSee('src="'.Storage::disk('public')->url($brand->logo_path).'"', false);
    }

    public function test_editorial_media_rejects_nonimage_files_without_publishing_a_record(): void
    {
        Livewire::test(ManageArticles::class)
            ->callAction('create', data: [
                'title' => 'Unsafe media',
                'slug' => 'unsafe-media',
                'summary' => 'Should not be published',
                'body' => 'Invalid upload',
                'is_published' => true,
                'cover_path' => [UploadedFile::fake()->createWithContent('attack.txt', 'not an image')],
            ])
            ->assertHasFormErrors(['cover_path']);

        $this->assertDatabaseCount('articles', 0);
    }

    private function validImage(string $name): UploadedFile
    {
        // Minimal valid PNG; no GD/image-extension dependency required on PHP CI.
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/0HcAAAAASUVORK5CYII=', true);

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
