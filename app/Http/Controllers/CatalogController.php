<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function home(): View
    {
        return view('catalog.home', [
            'offers' => Offer::current()->with(['brand', 'category'])->latest()->limit(6)->get(),
            'brands' => Brand::where('is_published', true)->orderBy('name')->limit(12)->get(),
            'categories' => Category::where('is_published', true)->orderBy('name')->get(),
        ]);
    }

    public function offers(Request $request): View
    {
        $term = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $brand = (string) $request->query('brand', '');
        $category = (string) $request->query('category', '');
        $sort = $request->query('sort') === 'ending' ? 'ending' : 'latest';

        $query = Offer::current()->with(['brand', 'category'])
            ->when($term !== '', fn (Builder $query) => $query->where(
                fn (Builder $query) => $query->whereLike('title', "%{$term}%")
                    ->orWhereLike('summary', "%{$term}%")
                    ->orWhereHas('brand', fn (Builder $query) => $query->whereLike('name', "%{$term}%")),
            ))
            ->when($brand !== '', fn (Builder $query) => $query->whereHas('brand', fn (Builder $query) => $query->where('slug', $brand)))
            ->when($category !== '', fn (Builder $query) => $query->whereHas('category', fn (Builder $query) => $query->where('slug', $category)));

        $sort === 'ending' ? $query->orderBy('ends_at') : $query->latest();

        return view('catalog.offers', [
            'title' => 'Student offers',
            'offers' => $query->paginate(12)->withQueryString(),
            'brands' => Brand::where('is_published', true)->orderBy('name')->get(),
            'categories' => Category::where('is_published', true)->orderBy('name')->get(),
            'term' => $term,
            'brand' => $brand,
            'category' => $category,
            'sort' => $sort,
        ]);
    }

    public function offer(string $slug): View
    {
        return view('catalog.offer', [
            'offer' => Offer::current()->with(['brand', 'category'])->where('slug', $slug)->firstOrFail(),
        ]);
    }

    public function brands(): View
    {
        return view('catalog.brands', [
            'brands' => Brand::where('is_published', true)->orderBy('name')->paginate(24),
        ]);
    }

    public function brand(string $slug): View
    {
        $brand = Brand::where('is_published', true)->where('slug', $slug)->firstOrFail();

        return view('catalog.offers', [
            'title' => $brand->name,
            'description' => $brand->description,
            'offers' => Offer::current()->with(['brand', 'category'])->whereBelongsTo($brand)->latest()->paginate(12),
            'brands' => collect(),
            'categories' => collect(),
            'term' => '',
            'brand' => '',
            'category' => '',
            'sort' => 'latest',
            'hideFilters' => true,
        ]);
    }

    public function category(string $slug): View
    {
        $category = Category::where('is_published', true)->where('slug', $slug)->firstOrFail();

        return view('catalog.offers', [
            'title' => $category->name,
            'description' => $category->description,
            'offers' => Offer::current()->with(['brand', 'category'])->whereBelongsTo($category)->latest()->paginate(12),
            'brands' => collect(),
            'categories' => collect(),
            'term' => '',
            'brand' => '',
            'category' => '',
            'sort' => 'latest',
            'hideFilters' => true,
        ]);
    }
}
