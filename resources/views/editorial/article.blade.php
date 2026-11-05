@extends('layouts.public')

@section('title', $article->seo_title ?: $article->title)
@section('description', $article->seo_description ?: $article->summary)
@section('og_type', 'article')

@section('content')
    <article class="mx-auto max-w-3xl">
        <a href="{{ route('articles.index') }}" class="text-sm font-semibold text-indigo-700">← All guides</a>
        <h1 class="mt-6 text-4xl font-bold tracking-tight">{{ $article->title }}</h1>
        <p class="mt-4 text-lg text-slate-600">{{ $article->summary }}</p>
        <p class="mt-4 text-sm text-slate-500">Published {{ ($article->published_at ?? $article->created_at)->format('j M Y') }}</p>
        @if($article->cover_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($article->cover_path) }}" alt="" class="mt-8 w-full rounded-2xl object-cover" loading="lazy">
        @endif
        <div class="mt-8 whitespace-pre-line leading-8 text-slate-700">{{ $article->body }}</div>
    </article>
@endsection
