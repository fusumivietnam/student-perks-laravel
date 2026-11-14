@extends('layouts.public')

@section('title', 'Brands')
@if($brands->currentPage() > 1)
    @section('robots', 'noindex,follow')
@endif

@section('content')
    <div class="max-w-3xl">
        <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-indigo-600">Explore providers</p>
        <h1 class="mt-2 text-4xl font-extrabold tracking-tight">Discover brands</h1>
        <p class="mt-4 text-lg text-slate-600">Find education offers from brands in our published directory.</p>
    </div>
    @if($brands->isNotEmpty())
        <div class="mt-9 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($brands as $brand)
                <article class="group flex h-full flex-col rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-lg focus-within:ring-2 focus-within:ring-indigo-300">
                    <x-brand-mark :brand="$brand" class="h-14 w-14" />
                    <h2 class="mt-5 text-xl font-bold tracking-tight"><a class="rounded-sm hover:text-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-700" href="{{ route('brands.show', $brand->slug) }}">{{ $brand->name }}</a></h2>
                    @if($brand->description)<p class="mt-3 line-clamp-3 flex-1 text-sm leading-relaxed text-slate-600">{{ $brand->description }}</p>@endif
                    <a class="mt-6 inline-flex min-h-10 items-center gap-2 border-t border-slate-100 pt-4 text-sm font-bold text-indigo-700 hover:text-indigo-900 focus-visible:outline-2 focus-visible:outline-indigo-700" href="{{ route('brands.show', $brand->slug) }}">Explore offers <span aria-hidden="true">→</span></a>
                </article>
            @endforeach
        </div>
    @else
        <div class="mt-9 rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <h2 class="text-xl font-bold">No brands available yet</h2>
            <p class="mt-2 text-slate-600">We’re reviewing new brands to add to the directory.</p>
        </div>
    @endif
    <div class="mt-9">{{ $brands->links() }}</div>
@endsection
