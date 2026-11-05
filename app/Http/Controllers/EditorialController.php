<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Page;
use Illuminate\Http\Response;
use Illuminate\View\View;

class EditorialController extends Controller
{
    public function articles(): View
    {
        return view('editorial.articles', [
            'articles' => Article::published()->orderByDesc('published_at')->latest()->paginate(12),
        ]);
    }

    public function article(string $slug): View
    {
        return view('editorial.article', [
            'article' => Article::published()->where('slug', $slug)->firstOrFail(),
        ]);
    }

    public function page(string $slug): View
    {
        return view('editorial.page', [
            'page' => Page::published()->where('slug', $slug)->firstOrFail(),
        ]);
    }

    public function sitemap(): Response
    {
        $locations = collect([
            route('home'), route('offers.index'), route('brands.index'), route('articles.index'),
        ]);

        foreach (Brand::where('is_published', true)->pluck('slug') as $slug) {
            $locations->push(route('brands.show', $slug));
        }
        foreach (Category::where('is_published', true)->pluck('slug') as $slug) {
            $locations->push(route('categories.show', $slug));
        }
        foreach (Offer::current()->pluck('slug') as $slug) {
            $locations->push(route('offers.show', $slug));
        }
        foreach (Article::published()->pluck('slug') as $slug) {
            $locations->push(route('articles.show', $slug));
        }
        foreach (Page::published()->pluck('slug') as $slug) {
            $locations->push(route('pages.show', $slug));
        }

        return response()->view('editorial.sitemap', ['locations' => $locations], 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
