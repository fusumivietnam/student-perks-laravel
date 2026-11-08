@extends('layouts.public')

@section('title', $offer->seo_title ?: $offer->title)
@section('description', $offer->seo_description ?: $offer->summary)
@if($isPreview ?? false)
    @section('robots', 'noindex,nofollow,noarchive')
@endif

@section('content')
    @if($isPreview ?? false)
        <div role="status" class="mb-6 rounded-xl border border-amber-300 bg-amber-50 px-5 py-4 font-semibold text-amber-950">Private admin preview of saved content — this offer may not be published. Changes must be saved before they appear here.</div>
    @endif
    <nav aria-label="Breadcrumb" class="mb-8 flex flex-wrap items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('offers.index') }}" class="rounded-sm font-semibold text-indigo-700 hover:underline focus-visible:outline-2 focus-visible:outline-indigo-700">All offers</a>
        <span aria-hidden="true">/</span>
        @if($isPreview ?? false)
            <span>{{ $offer->brand->name }}</span>
        @else
            <a href="{{ route('brands.show', $offer->brand->slug) }}" class="rounded-sm hover:text-indigo-700 hover:underline focus-visible:outline-2 focus-visible:outline-indigo-700">{{ $offer->brand->name }}</a>
        @endif
        <span aria-hidden="true">/</span>
        <span class="max-w-full truncate font-medium text-slate-700" aria-current="page">{{ $offer->title }}</span>
    </nav>

    <article class="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] lg:gap-10">
        <div class="min-w-0">
            <header class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="relative flex min-h-44 items-center justify-between overflow-hidden bg-gradient-to-br from-indigo-100 via-violet-50 to-sky-100 px-7 py-8 sm:px-10">
                    <div class="absolute -right-16 -top-24 h-64 w-64 rounded-full border-[32px] border-white/50" aria-hidden="true"></div>
                    <x-brand-mark :brand="$offer->brand" class="relative h-20 w-20" text-class="text-2xl" />
                    @if($offer->offer_value)
                        <span class="relative max-w-44 rounded-full bg-white px-4 py-2 text-center text-sm font-extrabold text-indigo-800 shadow-sm">{{ $offer->offer_value }}</span>
                    @endif
                </div>
                <div class="px-6 py-8 sm:px-10 sm:py-10">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2 text-sm font-bold text-indigo-700">
                        @if($isPreview ?? false)
                            <span>{{ $offer->brand->name }}</span>
                            <span class="text-slate-300" aria-hidden="true">·</span>
                            <span>{{ $offer->category->name }}</span>
                        @else
                            <a class="rounded-sm hover:underline focus-visible:outline-2 focus-visible:outline-indigo-700" href="{{ route('brands.show', $offer->brand->slug) }}">{{ $offer->brand->name }}</a>
                            <span class="text-slate-300" aria-hidden="true">·</span>
                            <a class="rounded-sm hover:underline focus-visible:outline-2 focus-visible:outline-indigo-700" href="{{ route('categories.show', $offer->category->slug) }}">{{ $offer->category->name }}</a>
                        @endif
                    </div>
                    <h1 class="mt-4 text-3xl font-extrabold leading-tight tracking-tight text-slate-950 sm:text-4xl">{{ $offer->title }}</h1>
                    <p class="mt-5 text-base leading-relaxed text-slate-600 sm:text-lg">{{ $offer->summary }}</p>
                </div>
            </header>

            <div class="mt-8 space-y-6">
                <section aria-labelledby="about-offer-title" class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8">
                    <h2 id="about-offer-title" class="text-2xl font-extrabold tracking-tight">About this offer</h2>
                    <div class="mt-4 whitespace-pre-line break-words leading-7 text-slate-700">{{ $offer->description }}</div>
                </section>

                @if($offer->eligibility)
                    <section aria-labelledby="offer-eligibility-title" class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8">
                        <h2 id="offer-eligibility-title" class="text-2xl font-extrabold tracking-tight">Who is eligible?</h2>
                        <p class="mt-4 whitespace-pre-line break-words leading-7 text-slate-700">{{ $offer->eligibility }}</p>
                    </section>
                @endif

                @if($offer->redemption_steps)
                    <section aria-labelledby="offer-redemption-title" class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8">
                        <h2 id="offer-redemption-title" class="text-2xl font-extrabold tracking-tight">How to redeem</h2>
                        <p class="mt-4 whitespace-pre-line break-words leading-7 text-slate-700">{{ $offer->redemption_steps }}</p>
                    </section>
                @endif

                <p class="text-sm text-slate-500">Last updated: <time datetime="{{ $offer->updated_at->toDateString() }}">{{ $offer->updated_at->format('j M Y') }}</time>@if($offer->ends_at) · Valid until <time datetime="{{ $offer->ends_at->toDateString() }}">{{ $offer->ends_at->format('j M Y') }}</time>@endif</p>
            </div>
        </div>

        <aside aria-label="Offer details and partner link" class="rounded-3xl border border-indigo-100 bg-white p-6 shadow-sm lg:sticky lg:top-28">
            <p class="text-xs font-extrabold uppercase tracking-[0.15em] text-indigo-600">Offer details</p>
            @if($offer->offer_value)
                <p class="mt-3 break-words text-2xl font-extrabold tracking-tight text-slate-950">{{ $offer->offer_value }}</p>
            @endif
            <dl class="mt-5 divide-y divide-slate-100 border-y border-slate-100 text-sm">
                <div class="flex items-start justify-between gap-3 py-3">
                    <dt class="text-slate-500">Brand</dt>
                    <dd class="text-right font-semibold text-slate-900">{{ $offer->brand->name }}</dd>
                </div>
                <div class="flex items-start justify-between gap-3 py-3">
                    <dt class="text-slate-500">Category</dt>
                    <dd class="text-right font-semibold text-slate-900">{{ $offer->category->name }}</dd>
                </div>
                @if($offer->ends_at)
                    <div class="flex items-start justify-between gap-3 py-3">
                        <dt class="text-slate-500">Offer ends</dt>
                        <dd class="text-right font-semibold text-slate-900"><time datetime="{{ $offer->ends_at->toDateString() }}">{{ $offer->ends_at->format('j M Y') }}</time></dd>
                    </div>
                @endif
            </dl>
            @if($isPreview ?? false)
                <p class="mt-6 rounded-xl bg-amber-50 p-4 text-sm font-semibold text-amber-950">External redemption links are disabled in preview.</p>
            @elseif(filter_var($offer->partner_url, FILTER_VALIDATE_URL) && parse_url($offer->partner_url, PHP_URL_SCHEME) === 'https')
                <a href="{{ $offer->partner_url }}" target="_blank" rel="noopener noreferrer sponsored" class="mt-6 flex min-h-12 items-center justify-center gap-2 rounded-xl bg-indigo-700 px-4 py-3 text-center text-sm font-extrabold text-white transition hover:bg-indigo-800 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-indigo-700">Visit official partner <span aria-hidden="true">↗</span></a>
            @else
                <p class="mt-6 rounded-xl bg-rose-50 p-4 text-sm font-semibold text-rose-800">Partner link is temporarily unavailable. Please check again later.</p>
            @endif
            <p class="mt-4 text-sm leading-relaxed text-slate-600">You'll leave Student Perks Hub. The partner verifies eligibility and handles redemption. Terms and availability may change.</p>
            <a href="{{ route('offers.index') }}" class="mt-5 inline-flex min-h-10 items-center rounded-md text-sm font-bold text-indigo-700 hover:underline focus-visible:outline-2 focus-visible:outline-indigo-700">← Browse more offers</a>
        </aside>
    </article>
@endsection
