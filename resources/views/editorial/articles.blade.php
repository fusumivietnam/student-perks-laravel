@extends('layouts.public')

@section('title', 'Student guides and news')
@if($articles->currentPage() > 1)
    @section('robots', 'noindex,follow')
@endif
@section('description', 'Helpful guides, student resources and education news.')

@section('content')
    <x-public-page-heading eyebrow="Student resources" title="Student guides and news" description="Practical advice to help you understand education offers and make informed choices." />
    <div class="mt-9 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($articles as $article)
            <article class="flex h-full min-w-0 flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:border-indigo-200 hover:shadow-md focus-within:ring-2 focus-within:ring-indigo-300">
                @if($coverUrl = \App\Support\PublicCmsImage::url($article->cover_path, 'articles'))
                    <img src="{{ $coverUrl }}" alt="" loading="lazy" class="aspect-video w-full object-cover">
                @else
                    <div aria-hidden="true" class="flex aspect-video items-center justify-center bg-gradient-to-br from-indigo-50 via-violet-50 to-sky-50">
                        <span class="rounded-full bg-white px-4 py-2 text-sm font-bold text-indigo-800">Student guide</span>
                    </div>
                @endif
                <div class="flex min-w-0 flex-1 flex-col p-5 sm:p-6">
                <p class="text-sm text-slate-500">{{ ($article->published_at ?? $article->created_at)->format('j M Y') }}</p>
                <h2 class="mt-3 break-words text-xl font-bold"><a href="{{ route('articles.show', $article->slug) }}" class="rounded-sm hover:text-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-700">{{ $article->title }}</a></h2>
                <p class="mt-3 flex-1 break-words text-sm leading-relaxed text-slate-600">{{ $article->summary }}</p>
                <a href="{{ route('articles.show', $article->slug) }}" class="mt-5 inline-flex min-h-11 items-center self-start rounded-md font-semibold text-indigo-700 hover:underline focus-visible:outline-2 focus-visible:outline-indigo-700">Read guide<span class="sr-only">: {{ $article->title }}</span></a>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center sm:col-span-2 lg:col-span-3">
                <h2 class="text-xl font-extrabold text-slate-950">Guides are being prepared.</h2>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">In the meantime, explore the current education offers.</p>
                <a href="{{ route('offers.index') }}" class="mt-5 inline-flex min-h-11 items-center rounded-xl bg-indigo-700 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700">Browse offers</a>
            </div>
        @endforelse
    </div>
    <div class="mt-8">{{ $articles->links() }}</div>
@endsection
