@extends('layouts.public')

@section('title', $page->seo_title ?: $page->title)
@section('description', $page->seo_description ?: $page->title)
@if($isPreview ?? false)
    @section('robots', 'noindex,nofollow,noarchive')
@endif

@section('content')
    @if($isPreview ?? false)
        <div role="status" class="mx-auto mb-6 max-w-3xl rounded-xl border border-amber-300 bg-amber-50 p-4 font-semibold text-amber-950">Private admin preview of saved content — unpublished changes must be saved first.</div>
    @endif
    <article class="mx-auto max-w-3xl">
        <h1 class="break-words text-3xl font-bold tracking-tight sm:text-4xl">{{ $page->title }}</h1>
        <div class="mt-8 whitespace-pre-line break-words leading-8 text-slate-700">{{ $page->body }}</div>
    </article>
@endsection
