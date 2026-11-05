@extends('layouts.public')

@section('title', $page->seo_title ?: $page->title)
@section('description', $page->seo_description ?: $page->title)

@section('content')
    <article class="mx-auto max-w-3xl">
        <h1 class="text-4xl font-bold tracking-tight">{{ $page->title }}</h1>
        <div class="mt-8 whitespace-pre-line leading-8 text-slate-700">{{ $page->body }}</div>
    </article>
@endsection
