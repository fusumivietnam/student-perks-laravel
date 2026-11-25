@extends('layouts.public')

@section('title', $title)
@if(request()->has('page') || $offers->currentPage() > 1 || (!($hideFilters ?? false) && (request()->routeIs('search') || request()->hasAny(['q', 'brand', 'category', 'type', 'sort']))))
    @section('robots', 'noindex,follow')
@endif
@section('description', filled($description ?? null) ? $description : 'Explore current education benefits and official partner offers.')

@section('content')
    @if($hideFilters ?? false)
        <nav aria-label="Breadcrumb" class="mb-5 flex flex-wrap items-center gap-2 text-sm text-slate-600">
            <a href="{{ route('home') }}" class="rounded-sm font-semibold hover:text-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-700">Home</a>
            <span aria-hidden="true">/</span>
            <a href="{{ request()->routeIs('brands.show') ? route('brands.index') : route('offers.index') }}" class="rounded-sm font-semibold hover:text-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-700">{{ request()->routeIs('brands.show') ? 'Brands' : 'All offers' }}</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page" class="break-words">{{ $title }}</span>
        </nav>
    @endif
    <x-public-page-heading eyebrow="Explore the directory" :title="$title" :description="filled($description ?? null) ? $description : (($hideFilters ?? false) ? 'Explore current student offers in this collection.' : 'Search student offers, browse by brand, and compare what is available.')" />

    @unless($hideFilters ?? false)
        <section aria-labelledby="offer-filters-title" class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="offer-filters-title" class="text-lg font-extrabold tracking-tight">Find the right offer</h2>
                <a href="{{ ($filterAction ?? route('offers.index')) }}" class="inline-flex min-h-11 items-center text-sm font-bold text-indigo-700 underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-indigo-700">Clear filters</a>
            </div>
            <form method="GET" action="{{ ($filterAction ?? route('offers.index')) }}" role="search" class="mt-4 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <div class="col-span-2 lg:col-span-4">
                    <label for="q" class="mb-2 block text-sm font-bold text-slate-700">Search offers</label>
                    <input id="q" type="search" name="q" value="{{ $term }}" maxlength="100" placeholder="Title, brand or keyword" class="min-h-12 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 placeholder:text-slate-500 focus-visible:border-indigo-500 focus-visible:outline-2 focus-visible:outline-indigo-500">
                </div>
                <div class="min-w-0">
                    <label for="brand" class="mb-2 block text-sm font-bold text-slate-700">Brand</label>
                    <select name="brand" id="brand" class="min-h-12 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus-visible:outline-2 focus-visible:outline-indigo-500">
                        <option value="">All brands</option>
                        @foreach($brands as $item)
                            <option value="{{ $item->slug }}" @selected($brand === $item->slug)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-0">
                    <label for="category" class="mb-2 block text-sm font-bold text-slate-700">Category</label>
                    <select name="category" id="category" class="min-h-12 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus-visible:outline-2 focus-visible:outline-indigo-500">
                        <option value="">All categories</option>
                        @foreach($categories as $item)
                            <option value="{{ $item->slug }}" @selected($category === $item->slug)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-0">
                    <label for="type" class="mb-2 block text-sm font-bold text-slate-700">Offer type</label>
                    <select name="type" id="type" class="min-h-12 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus-visible:outline-2 focus-visible:outline-indigo-500">
                        <option value="">All types</option>
                        @foreach($types as $item)
                            <option value="{{ $item }}" @selected($type === $item)>{{ \Illuminate\Support\Str::headline($item) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-0">
                    <label for="sort" class="mb-2 block text-sm font-bold text-slate-700">Sort by</label>
                    <select name="sort" id="sort" class="min-h-12 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus-visible:outline-2 focus-visible:outline-indigo-500">
                        <option value="latest" @selected($sort === 'latest')>Newest first</option>
                        <option value="ending" @selected($sort === 'ending')>Ending soon</option>
                    </select>
                </div>
                <button type="submit" class="col-span-2 min-h-12 rounded-xl bg-indigo-700 px-4 py-3 text-sm font-extrabold text-white transition hover:bg-indigo-800 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-indigo-700 lg:col-span-4">Apply filters <span aria-hidden="true">→</span></button>
            </form>
        </section>

        @if($term !== '' || $brand !== '' || $category !== '' || $type !== '' || $sort !== 'latest')
            <div class="mt-5 flex flex-wrap items-center gap-2" aria-label="Active filters">
                <span class="mr-1 text-sm font-semibold text-slate-600">Active filters:</span>
                @if($term !== '')
                    <a href="{{ $removeFilterUrls['q'] }}" aria-label="Remove search filter" class="inline-flex min-h-11 max-w-full items-center gap-2 rounded-full bg-indigo-100 px-3 py-2 text-xs font-bold text-indigo-800 hover:bg-indigo-200 focus-visible:outline-2 focus-visible:outline-indigo-700"><span class="min-w-0 wrap-anywhere">Search: {{ $term }}</span><span aria-hidden="true" class="shrink-0 text-base">×</span></a>
                @endif
                @if($brand !== '')
                    <a href="{{ $removeFilterUrls['brand'] }}" aria-label="Remove brand filter" class="inline-flex min-h-11 max-w-full items-center gap-2 rounded-full bg-indigo-100 px-3 py-2 text-xs font-bold text-indigo-800 hover:bg-indigo-200 focus-visible:outline-2 focus-visible:outline-indigo-700"><span class="min-w-0 wrap-anywhere">Brand: {{ $brands->firstWhere('slug', $brand)?->name ?? $brand }}</span><span aria-hidden="true" class="shrink-0 text-base">×</span></a>
                @endif
                @if($category !== '')
                    <a href="{{ $removeFilterUrls['category'] }}" aria-label="Remove category filter" class="inline-flex min-h-11 max-w-full items-center gap-2 rounded-full bg-indigo-100 px-3 py-2 text-xs font-bold text-indigo-800 hover:bg-indigo-200 focus-visible:outline-2 focus-visible:outline-indigo-700"><span class="min-w-0 wrap-anywhere">Category: {{ $categories->firstWhere('slug', $category)?->name ?? $category }}</span><span aria-hidden="true" class="shrink-0 text-base">×</span></a>
                @endif
                @if($type !== '')
                    <a href="{{ $removeFilterUrls['type'] }}" aria-label="Remove offer type filter" class="inline-flex min-h-11 max-w-full items-center gap-2 rounded-full bg-indigo-100 px-3 py-2 text-xs font-bold text-indigo-800 hover:bg-indigo-200 focus-visible:outline-2 focus-visible:outline-indigo-700"><span class="min-w-0 wrap-anywhere">Type: {{ \Illuminate\Support\Str::headline($type) }}</span><span aria-hidden="true" class="shrink-0 text-base">×</span></a>
                @endif
                @if($sort === 'ending')
                    <a href="{{ $removeFilterUrls['sort'] }}" aria-label="Remove ending soon sort" class="inline-flex min-h-11 max-w-full items-center gap-2 rounded-full bg-indigo-100 px-3 py-2 text-xs font-bold text-indigo-800 hover:bg-indigo-200 focus-visible:outline-2 focus-visible:outline-indigo-700"><span>Ending soon</span><span aria-hidden="true" class="shrink-0 text-base">×</span></a>
                @endif
                <a href="{{ ($filterAction ?? route('offers.index')) }}" class="inline-flex min-h-11 items-center rounded-md px-2 text-xs font-bold text-indigo-700 underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-indigo-700">Reset all</a>
            </div>
        @endif
    @endunless

    <section aria-labelledby="offer-results-title" class="mt-9">
        <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 pb-5">
            <div>
                <h2 id="offer-results-title" class="text-2xl font-extrabold tracking-tight text-slate-950">Available offers</h2>
                <p class="mt-1 text-sm text-slate-600" role="status">{{ $offers->total() }} {{ \Illuminate\Support\Str::plural('offer', $offers->total()) }} found</p>
            </div>
        </div>
        @if($offers->isEmpty() && $offers->currentPage() > 1)
            <div class="mt-6">
                <x-public-pagination-recovery :paginator="$offers" />
            </div>
        @elseif($offers->isEmpty())
            <div class="mt-6 rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
                <h3 class="text-xl font-extrabold">No offers found</h3>
                <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-slate-600">{{ ($hideFilters ?? false) ? 'There are no current offers in this collection. Explore the rest of the directory instead.' : 'Try another keyword, remove a filter, or browse all current offers.' }}</p>
                <a class="mt-6 inline-flex min-h-11 items-center rounded-xl bg-indigo-700 px-6 py-3 text-sm font-bold text-white transition hover:bg-indigo-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700" href="{{ route('offers.index') }}">See all offers</a>
            </div>
        @else
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($offers as $offer)
                    <x-offer-card :offer="$offer" />
                @endforeach
            </div>
            <div class="mt-9">{{ $offers->links() }}</div>
        @endif
    </section>
@endsection
