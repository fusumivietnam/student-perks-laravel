@extends('layouts.public')

@section('title', $title)
@if(!($hideFilters ?? false))
    @section('robots', 'noindex,follow')
@endif
@section('description', $description ?? 'Explore current education benefits and official partner offers.')

@section('content')
    <header class="max-w-3xl">
        <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-indigo-600">Explore the directory</p>
        <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-slate-950">{{ $title }}</h1>
        <p class="mt-3 text-base leading-relaxed text-slate-600">{{ $description ?? 'Search student offers, browse by brand, and compare what is available.' }}</p>
    </header>

    @unless($hideFilters ?? false)
        <section aria-labelledby="offer-filters-title" class="mt-8 overflow-hidden rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="offer-filters-title" class="text-lg font-extrabold tracking-tight">Find the right offer</h2>
                <a href="{{ route('offers.index') }}" class="inline-flex min-h-10 items-center text-sm font-bold text-indigo-700 underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-indigo-700">Clear filters</a>
            </div>
            <form method="GET" action="{{ route('offers.index') }}" role="search" class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
                <div class="sm:col-span-2 lg:col-span-3">
                    <label for="q" class="mb-2 block text-sm font-bold text-slate-700">Search offers</label>
                    <input id="q" type="search" name="q" value="{{ $term }}" maxlength="100" placeholder="Title, brand or keyword" class="min-h-12 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 placeholder:text-slate-500 focus-visible:border-indigo-500 focus-visible:outline-2 focus-visible:outline-indigo-500">
                </div>
                <div class="lg:col-span-3">
                    <label for="brand" class="mb-2 block text-sm font-bold text-slate-700">Brand</label>
                    <select name="brand" id="brand" class="min-h-12 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus-visible:outline-2 focus-visible:outline-indigo-500">
                        <option value="">All brands</option>
                        @foreach($brands as $item)
                            <option value="{{ $item->slug }}" @selected($brand === $item->slug)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label for="category" class="mb-2 block text-sm font-bold text-slate-700">Category</label>
                    <select name="category" id="category" class="min-h-12 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus-visible:outline-2 focus-visible:outline-indigo-500">
                        <option value="">All categories</option>
                        @foreach($categories as $item)
                            <option value="{{ $item->slug }}" @selected($category === $item->slug)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label for="sort" class="mb-2 block text-sm font-bold text-slate-700">Sort by</label>
                    <select name="sort" id="sort" class="min-h-12 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus-visible:outline-2 focus-visible:outline-indigo-500">
                        <option value="latest" @selected($sort === 'latest')>Newest first</option>
                        <option value="ending" @selected($sort === 'ending')>Ending soon</option>
                    </select>
                </div>
                <button type="submit" class="min-h-12 rounded-xl bg-indigo-700 px-6 py-3 text-sm font-extrabold text-white transition hover:bg-indigo-800 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-indigo-700 sm:col-span-2 lg:col-span-6">Apply filters <span aria-hidden="true">→</span></button>
            </form>
        </section>

        @if($term !== '' || $brand !== '' || $category !== '' || $sort !== 'latest')
            <div class="mt-5 flex flex-wrap items-center gap-2" aria-label="Active filters">
                <span class="mr-1 text-sm font-semibold text-slate-600">Active filters:</span>
                @if($term !== '')
                    <span class="rounded-full bg-indigo-100 px-3 py-1.5 text-xs font-bold text-indigo-800">Search: {{ $term }}</span>
                @endif
                @if($brand !== '')
                    <span class="rounded-full bg-indigo-100 px-3 py-1.5 text-xs font-bold text-indigo-800">Brand: {{ $brands->firstWhere('slug', $brand)?->name ?? $brand }}</span>
                @endif
                @if($category !== '')
                    <span class="rounded-full bg-indigo-100 px-3 py-1.5 text-xs font-bold text-indigo-800">Category: {{ $categories->firstWhere('slug', $category)?->name ?? $category }}</span>
                @endif
                @if($sort === 'ending')
                    <span class="rounded-full bg-indigo-100 px-3 py-1.5 text-xs font-bold text-indigo-800">Ending soon</span>
                @endif
                <a href="{{ route('offers.index') }}" class="inline-flex min-h-9 items-center rounded-md px-2 text-xs font-bold text-indigo-700 underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-indigo-700">Reset all</a>
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
        @if($offers->isEmpty())
            <div class="mt-6 rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
                <h3 class="text-xl font-extrabold">No offers found</h3>
                <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-slate-600">Try another keyword, remove a filter, or browse all current offers.</p>
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
