<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function home(): View
    {
        return view('catalog.home', [
            'offers' => Offer::current()->with(['brand', 'category'])->latest()->orderByDesc('id')->limit(6)->get(),
            'brands' => Brand::where('is_published', true)->orderBy('name')->orderBy('id')->limit(12)->get(),
            'categories' => Category::where('is_published', true)->orderBy('name')->orderBy('id')->get(),
        ]);
    }

    public function offers(Request $request): View
    {
        $rawTerm = $request->query('q', '');
        $rawBrand = $request->query('brand', '');
        $rawCategory = $request->query('category', '');
        $rawType = $request->query('type', '');

        $term = is_string($rawTerm) ? mb_substr(trim($rawTerm), 0, 100) : '';
        // Escape LIKE metacharacters so user input is a literal search phrase.
        $searchPattern = '%'.addcslashes($term, '%_\\').'%';
        $brand = is_string($rawBrand) ? mb_substr($rawBrand, 0, 255) : '';
        $category = is_string($rawCategory) ? mb_substr($rawCategory, 0, 255) : '';
        $type = is_string($rawType) ? mb_substr($rawType, 0, 255) : '';
        $sort = $request->query('sort') === 'ending' ? 'ending' : 'latest';

        $query = Offer::current()->with(['brand', 'category'])
            ->when($term !== '', fn (Builder $query) => $query->where(
                fn (Builder $query) => $query->whereLike('title', $searchPattern)
                    ->orWhereLike('summary', $searchPattern)
                    ->orWhereHas('brand', fn (Builder $query) => $query->whereLike('name', $searchPattern)),
            ))
            ->when($brand !== '', fn (Builder $query) => $query->whereHas('brand', fn (Builder $query) => $query->where('slug', $brand)))
            ->when($category !== '', fn (Builder $query) => $query->whereHas('category', fn (Builder $query) => $query->where('slug', $category)))
            ->when($type !== '', fn (Builder $query) => $query->where('offer_type', $type));

        $sort === 'ending'
            ? $query->orderBy('ends_at')->orderBy('id')
            : $query->latest()->orderByDesc('id');

        return view('catalog.offers', [
            'title' => $request->routeIs('search') ? 'Search student offers' : 'Student offers',
            'filterAction' => $request->routeIs('search') ? route('search') : route('offers.index'),
            'offers' => $query->paginate(12)->withQueryString(),
            'brands' => Brand::where('is_published', true)->orderBy('name')->get(),
            'categories' => Category::where('is_published', true)->orderBy('name')->get(),
            'types' => Offer::current()->whereNotNull('offer_type')->where('offer_type', '<>', '')
                ->distinct()->orderBy('offer_type')->pluck('offer_type'),
            'term' => $term,
            'brand' => $brand,
            'category' => $category,
            'type' => $type,
            'sort' => $sort,
        ]);
    }

    public function offer(string $slug): View|RedirectResponse
    {
        $offer = Offer::current()->with(['brand', 'category'])->where('slug', $slug)->first();

        if ($offer) {
            return view('catalog.offer', ['offer' => $offer]);
        }

        $offerId = DB::table('offer_slug_redirects')->where('slug', $slug)->value('offer_id');
        abort_unless($offerId, 404);

        $currentOffer = Offer::current()->findOrFail($offerId);

        return redirect()->route('offers.show', $currentOffer->slug, 301);
    }

    public function previewOffer(Request $request, Offer $offer): Response
    {
        abort_unless($request->user()?->is_admin, 403);

        return response()->view('catalog.offer', [
            'offer' => $offer->load(['brand', 'category']),
            'isPreview' => true,
        ])->withHeaders([
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ]);
    }

    public function brands(): View
    {
        return view('catalog.brands', [
            'brands' => Brand::where('is_published', true)->orderBy('name')->orderBy('id')->paginate(24),
        ]);
    }

    public function brand(string $slug): View
    {
        $brand = Brand::where('is_published', true)->where('slug', $slug)->firstOrFail();

        return view('catalog.offers', [
            'title' => $brand->name,
            'description' => $brand->description,
            'offers' => Offer::current()->with(['brand', 'category'])->whereBelongsTo($brand)->latest()->orderByDesc('id')->paginate(12),
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
            'offers' => Offer::current()->with(['brand', 'category'])->whereBelongsTo($category)->latest()->orderByDesc('id')->paginate(12),
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
