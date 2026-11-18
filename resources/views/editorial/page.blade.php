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
    <x-public-reading-panel>
        <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center rounded-md text-sm font-semibold text-indigo-700 hover:underline focus-visible:outline-2 focus-visible:outline-indigo-700">← Home</a>
        <x-public-page-heading class="mt-4" eyebrow="Information" :title="$page->title" />
        <div class="mt-8 whitespace-pre-line break-words leading-8 text-slate-700">{{ $page->body }}</div>
    </x-public-reading-panel>
@endsection
