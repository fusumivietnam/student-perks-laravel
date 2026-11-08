@extends('layouts.public')

@section('title', 'Student deals and education benefits')

@section('content')
    <section class="relative isolate overflow-hidden rounded-[2rem] bg-indigo-950 px-6 py-12 text-white sm:px-12 sm:py-16 lg:px-16">
        <div class="pointer-events-none absolute -right-24 -top-40 h-96 w-96 rounded-full bg-violet-500/25 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-40 right-12 h-96 w-96 rounded-full bg-sky-400/15 blur-3xl" aria-hidden="true"></div>
        <div class="relative max-w-3xl">
            <span class="inline-flex items-center gap-2 rounded-full border border-indigo-300/30 bg-white/10 px-4 py-2 text-xs font-bold tracking-widest text-indigo-100 uppercase"><span class="h-2 w-2 rounded-full bg-amber-400" aria-hidden="true"></span> Education benefits, made easier</span>
            <h1 class="mt-7 text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl lg:text-6xl">Your student status deserves <span class="text-amber-300">more.</span></h1>
            <p class="mt-6 max-w-2xl text-base leading-relaxed text-indigo-100 sm:text-lg">Discover software, learning and lifestyle offers in one place. Compare the details, check eligibility, and continue with the partner.</p>
            <form method="GET" action="{{ route('search') }}" role="search" class="mt-8 flex max-w-2xl flex-col gap-3 sm:flex-row">
                <label for="home-search" class="sr-only">Search student offers</label>
                <input id="home-search" name="q" type="search" maxlength="100" placeholder="Search offers, brands, learning tools…" class="min-h-13 min-w-0 flex-1 rounded-xl border border-white bg-white px-5 py-3 text-base text-slate-900 placeholder:text-slate-500 focus-visible:outline-3 focus-visible:outline-amber-400" required>
                <button type="submit" class="min-h-13 rounded-xl bg-amber-400 px-8 py-3 font-extrabold text-slate-950 transition hover:bg-amber-300 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-white">Explore offers <span aria-hidden="true">→</span></button>
            </form>
            <p class="mt-5 text-sm text-indigo-200">No account required to browse. Always verify conditions with the provider.</p>
        </div>
    </section>

    <section class="mt-14" aria-labelledby="latest-offers-title">
        <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-indigo-600">Explore what’s available</p>
                <h2 id="latest-offers-title" class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Latest student offers</h2>
                <p class="mt-2 text-sm text-slate-600">Recently added benefits from our published directory.</p>
            </div>
            <a href="{{ route('offers.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-indigo-200 bg-white px-5 py-2 text-sm font-bold text-indigo-700 transition hover:border-indigo-500 hover:bg-indigo-50 focus-visible:outline-2 focus-visible:outline-indigo-700">Browse all offers <span aria-hidden="true">→</span></a>
        </div>
        @if($offers->isNotEmpty())
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($offers as $offer)
                    <x-offer-card :offer="$offer" />
                @endforeach
            </div>
        @else
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-8 py-12 text-center">
                <h3 class="text-xl font-bold">Offers are on their way</h3>
                <p class="mt-2 text-slate-600">We’re reviewing new offers. Explore brands or check back soon.</p>
                <a class="mt-4 inline-block font-semibold text-indigo-700 underline-offset-4 hover:underline" href="{{ route('brands.index') }}">Explore brands</a>
            </div>
        @endif
    </section>

    @if($categories->isNotEmpty())
        <section class="mt-16" aria-labelledby="categories-title">
            <div class="mb-6">
                <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-indigo-600">Find your fit</p>
                <h2 id="categories-title" class="mt-2 text-3xl font-extrabold tracking-tight">Browse by category</h2>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($categories as $category)
                    <a class="group flex min-h-24 items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md focus-visible:outline-2 focus-visible:outline-indigo-700" href="{{ route('categories.show', $category->slug) }}">
                        <span class="text-lg font-bold text-slate-900 group-hover:text-indigo-700">{{ $category->name }}</span>
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-50 font-bold text-indigo-700 transition group-hover:bg-indigo-700 group-hover:text-white" aria-hidden="true">→</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if($brands->isNotEmpty())
        <section class="mt-16" aria-labelledby="brands-title">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-indigo-600">Discover providers</p>
                    <h2 id="brands-title" class="mt-2 text-3xl font-extrabold tracking-tight">Explore brands</h2>
                </div>
                <a href="{{ route('brands.index') }}" class="text-sm font-bold text-indigo-700 underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-indigo-700">See all brands <span aria-hidden="true">→</span></a>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($brands as $brand)
                    <a class="group flex min-h-28 items-center gap-4 rounded-2xl border border-slate-200 bg-white px-5 py-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md focus-visible:outline-2 focus-visible:outline-indigo-700" href="{{ route('brands.show', $brand->slug) }}">
                        <x-brand-mark :brand="$brand" class="h-12 w-12" text-class="text-sm" />
                        <span class="font-bold leading-snug text-slate-900 group-hover:text-indigo-700">{{ $brand->name }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
