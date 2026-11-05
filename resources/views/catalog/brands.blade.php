@extends('layouts.public')

@section('title', 'Brands')

@section('content')
    <h1 class="text-3xl font-bold">Brands</h1>
    <p class="mt-2 text-slate-600">Discover education offers from featured partners.</p>
    @forelse($brands as $brand)
        @if($loop->first)<div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">@endif
        <article class="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-semibold"><a class="hover:text-indigo-700" href="{{ route('brands.show', $brand->slug) }}">{{ $brand->name }}</a></h2>
            @if($brand->description)<p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ $brand->description }}</p>@endif
            <a class="mt-4 inline-block text-sm font-semibold text-indigo-700" href="{{ route('brands.show', $brand->slug) }}">View offers</a>
        </article>
        @if($loop->last)</div>@endif
    @empty
        <p class="mt-8 rounded-2xl border border-dashed border-slate-300 p-8 text-slate-600">No brands are currently published.</p>
    @endforelse
    <div class="mt-8">{{ $brands->links() }}</div>
@endsection
