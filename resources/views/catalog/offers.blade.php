@extends('layouts.public')

@section('title', $title)
@if(!($hideFilters ?? false))
    @section('robots', 'noindex,follow')
@endif
@section('description', $description ?? 'Explore current education benefits and official partner offers.')

@section('content')
    <h1 class="text-3xl font-bold tracking-tight">{{ $title }}</h1>
    @if(!empty($description))
        <p class="mt-3 max-w-3xl text-slate-600">{{ $description }}</p>
    @endif

    @unless($hideFilters ?? false)
        <form method="GET" action="{{ route('offers.index') }}" role="search" class="mt-7 grid gap-3 rounded-2xl border border-slate-200 bg-white p-5 md:grid-cols-5">
            <div class="md:col-span-2">
                <label for="q" class="mb-1 block text-sm font-semibold">Search</label>
                <input id="q" type="search" name="q" value="{{ $term }}" maxlength="100" placeholder="Title, brand or keyword" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label for="brand" class="mb-1 block text-sm font-semibold">Brand</label>
                <select name="brand" id="brand" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">All brands</option>
                    @foreach($brands as $item)
                        <option value="{{ $item->slug }}" @selected($brand === $item->slug)>{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="category" class="mb-1 block text-sm font-semibold">Category</label>
                <select name="category" id="category" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">All categories</option>
                    @foreach($categories as $item)
                        <option value="{{ $item->slug }}" @selected($category === $item->slug)>{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="sort" class="mb-1 block text-sm font-semibold">Sort</label>
                <select name="sort" id="sort" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="latest" @selected($sort === 'latest')>Newest</option>
                    <option value="ending" @selected($sort === 'ending')>Ending soon</option>
                </select>
            </div>
            <button class="rounded-lg bg-indigo-700 px-5 py-2 font-semibold text-white hover:bg-indigo-800 md:col-span-5">Apply filters</button>
        </form>
    @endunless

    <p class="mt-7 text-sm text-slate-600">{{ $offers->total() }} {{ \Illuminate\Support\Str::plural('offer', $offers->total()) }} found</p>
    @if($offers->isEmpty())
        <div class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-white px-8 py-12 text-center">
            <h2 class="text-xl font-semibold">No offers found</h2>
            <p class="mt-2 text-slate-600">Try a different search or check back as we add new offers.</p>
            <a class="mt-4 inline-block font-semibold text-indigo-700 hover:underline" href="{{ route('offers.index') }}">See all offers</a>
        </div>
    @else
        <div class="mt-5 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach($offers as $offer)
                <x-offer-card :offer="$offer" />
            @endforeach
        </div>
        <div class="mt-8">{{ $offers->links() }}</div>
    @endif
@endsection
