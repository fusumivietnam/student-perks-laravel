@extends('layouts.public')

@section('title', 'Brands')
@if((request()->has('page') || $brands->currentPage() > 1))
    @section('robots', 'noindex,follow')
@endif

@section('content')
    <x-public-page-heading eyebrow="Explore providers" title="Discover brands" description="Find education offers from brands in our published directory." />
    @if($brands->isEmpty() && $brands->currentPage() > 1)
        <div class="mt-9">
            <x-public-pagination-recovery :paginator="$brands" />
        </div>
    @elseif($brands->isNotEmpty())
        <div class="mt-9 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($brands as $brand)
                <article class="group flex h-full min-w-0 flex-col rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 transition motion-safe:hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-lg focus-within:ring-2 focus-within:ring-indigo-300">
                    <x-brand-mark :brand="$brand" class="h-14 w-14" />
                    <h2 class="mt-5 break-words text-xl font-bold tracking-tight"><a class="rounded-sm hover:text-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-700" href="{{ route('brands.show', $brand->slug) }}">{{ $brand->name }}</a></h2>
                    @if($brand->description)<p class="mt-3 line-clamp-3 flex-1 break-words text-sm leading-relaxed text-slate-600">{{ $brand->description }}</p>@endif
                    <a class="mt-6 inline-flex min-h-11 items-center gap-2 border-t border-slate-100 pt-4 text-sm font-bold text-indigo-700 hover:text-indigo-900 focus-visible:outline-2 focus-visible:outline-indigo-700" href="{{ route('brands.show', $brand->slug) }}">Explore offers<span class="sr-only">: {{ $brand->name }}</span> <span aria-hidden="true">→</span></a>
                </article>
            @endforeach
        </div>
    @else
        <div class="mt-9 rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <h2 class="text-xl font-bold">No brands available yet</h2>
            <p class="mt-2 text-slate-600">We’re reviewing new brands to add to the directory.</p>
        </div>
    @endif
    @if($brands->isNotEmpty())
        <div class="mt-9">{{ $brands->links() }}</div>
    @endif
@endsection
