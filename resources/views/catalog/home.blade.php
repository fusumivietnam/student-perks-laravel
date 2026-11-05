@extends('layouts.public')

@section('title', 'Student deals and education benefits')

@section('content')
    <section class="rounded-3xl bg-indigo-950 px-8 py-14 text-white sm:px-14">
        <p class="text-sm font-semibold uppercase tracking-widest text-indigo-200">Education benefits, in one place</p>
        <h1 class="mt-4 max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl">Make the most of being a student.</h1>
        <p class="mt-5 max-w-2xl text-indigo-100">Discover software, learning and lifestyle offers. Read the conditions here, then redeem directly with the official partner.</p>
        <form method="GET" action="{{ route('search') }}" role="search" class="mt-8 flex max-w-xl flex-wrap gap-3">
            <label for="home-search" class="sr-only">Search student offers</label>
            <input id="home-search" name="q" type="search" placeholder="Search discounts, brands, learning tools..." class="min-w-0 flex-1 rounded-xl bg-white px-4 py-3 text-slate-900" required>
            <button class="rounded-xl bg-amber-400 px-6 py-3 font-bold text-slate-950 hover:bg-amber-300">Search</button>
        </form>
    </section>

    <section class="mt-12">
        <div class="mb-6 flex items-center justify-between gap-4">
            <h2 class="text-2xl font-bold">Latest offers</h2>
            <a href="{{ route('offers.index') }}" class="font-semibold text-indigo-700 hover:underline">Browse all</a>
        </div>
        @forelse($offers->chunk(3) as $row)
            <div class="mb-5 grid gap-5 md:grid-cols-3">
                @foreach($row as $offer)
                    <x-offer-card :offer="$offer" />
                @endforeach
            </div>
        @empty
            <p class="rounded-2xl border border-dashed border-slate-300 p-8 text-slate-600">We're reviewing new offers. Browse again soon.</p>
        @endforelse
    </section>

    @if($categories->isNotEmpty())
        <section class="mt-12">
            <h2 class="mb-5 text-2xl font-bold">Explore categories</h2>
            <div class="flex flex-wrap gap-3">
                @foreach($categories as $category)
                    <a class="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-semibold hover:border-indigo-400 hover:text-indigo-700" href="{{ route('categories.show', $category->slug) }}">{{ $category->name }}</a>
                @endforeach
            </div>
        </section>
    @endif

    @if($brands->isNotEmpty())
        <section class="mt-12">
            <h2 class="mb-5 text-2xl font-bold">Featured brands</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($brands as $brand)
                    <a class="rounded-xl border border-slate-200 bg-white p-5 font-semibold hover:border-indigo-400 hover:text-indigo-700" href="{{ route('brands.show', $brand->slug) }}">{{ $brand->name }}</a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
