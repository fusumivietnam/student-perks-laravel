@extends('layouts.public')

@section('title', 'Page not found')
@section('robots', 'noindex,nofollow')
@section('description', 'This page is no longer available. Explore current student offers and guides.')

@section('content')
    <section class="mx-auto max-w-2xl py-12 text-center sm:py-20" aria-labelledby="not-found-title">
        <p class="text-sm font-extrabold uppercase tracking-[0.2em] text-indigo-700">404 · Page not found</p>
        <h1 id="not-found-title" class="mt-4 break-words text-4xl font-extrabold tracking-tight text-slate-950 sm:text-5xl">We couldn't find that page</h1>
        <p class="mx-auto mt-5 max-w-xl text-base leading-7 text-slate-600">The link might be outdated, or this content may no longer be available. Browse current student benefits or try searching for another offer.</p>
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('offers.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-indigo-700 px-6 py-3 text-sm font-bold text-white hover:bg-indigo-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700">Browse offers</a>
            <a href="{{ route('search') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-indigo-200 bg-white px-6 py-3 text-sm font-bold text-indigo-800 hover:border-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700">Search offers</a>
        </div>
    </section>
@endsection
