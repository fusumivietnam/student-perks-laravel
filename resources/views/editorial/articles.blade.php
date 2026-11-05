@extends('layouts.public')

@section('title', 'Student guides and news')
@section('description', 'Helpful guides, student resources and education news.')

@section('content')
    <h1 class="text-3xl font-bold tracking-tight">Student guides and news</h1>
    <p class="mt-3 text-slate-600">Practical advice to help you understand education offers and make informed choices.</p>
    <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse($articles as $article)
            <article class="rounded-2xl border border-slate-200 bg-white p-6">
                <p class="text-sm text-slate-500">{{ ($article->published_at ?? $article->created_at)->format('j M Y') }}</p>
                <h2 class="mt-3 text-xl font-bold"><a href="{{ route('articles.show', $article->slug) }}" class="hover:text-indigo-700">{{ $article->title }}</a></h2>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $article->summary }}</p>
                <a href="{{ route('articles.show', $article->slug) }}" class="mt-5 inline-block font-semibold text-indigo-700">Read guide</a>
            </article>
        @empty
            <p class="rounded-2xl border border-dashed border-slate-300 p-8 text-slate-600 md:col-span-3">Guides are being prepared.</p>
        @endforelse
    </div>
    <div class="mt-8">{{ $articles->links() }}</div>
@endsection
