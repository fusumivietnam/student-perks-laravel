@extends('layouts.public')

@section('title', $offer->seo_title ?: $offer->title)
@section('description', $offer->seo_description ?: $offer->summary)

@section('content')
    <nav aria-label="Breadcrumb" class="mb-6 text-sm text-slate-500">
        <a href="{{ route('offers.index') }}" class="hover:text-indigo-700">Offers</a>
        <span aria-hidden="true"> / </span>
        <span>{{ $offer->title }}</span>
    </nav>
    <article class="max-w-4xl">
        <p class="mb-3 text-sm font-semibold text-indigo-700">
            <a href="{{ route('brands.show', $offer->brand->slug) }}">{{ $offer->brand->name }}</a>
            · <a href="{{ route('categories.show', $offer->category->slug) }}">{{ $offer->category->name }}</a>
        </p>
        <h1 class="text-4xl font-bold tracking-tight">{{ $offer->title }}</h1>
        <p class="mt-5 text-lg text-slate-600">{{ $offer->summary }}</p>
        @if($offer->offer_value)
            <p class="mt-6 inline-block rounded-full bg-indigo-100 px-4 py-2 font-bold text-indigo-800">{{ $offer->offer_value }}</p>
        @endif
        <div class="mt-8 rounded-2xl border border-indigo-100 bg-indigo-50 p-6">
            @if(filter_var($offer->partner_url, FILTER_VALIDATE_URL) && parse_url($offer->partner_url, PHP_URL_SCHEME) === 'https')
                <a href="{{ $offer->partner_url }}" target="_blank" rel="noopener noreferrer sponsored" class="inline-block rounded-lg bg-indigo-700 px-6 py-3 font-bold text-white hover:bg-indigo-800">Visit official partner <span aria-hidden="true">↗</span></a>
            @else
                <p class="font-semibold text-rose-800">Partner link is temporarily unavailable. Please check again later.</p>
            @endif
            <p class="mt-3 text-sm text-slate-600">You'll leave Student Perks Hub. The partner verifies eligibility and handles redemption. Terms and availability may change.</p>
        </div>
        <section class="mt-10 space-y-4 leading-relaxed">
            <h2 class="text-2xl font-bold">About this offer</h2>
            <div class="whitespace-pre-line text-slate-700">{{ $offer->description }}</div>
        </section>
        @if($offer->eligibility)
            <section class="mt-9">
                <h2 class="text-2xl font-bold">Eligibility</h2>
                <p class="mt-3 whitespace-pre-line text-slate-700">{{ $offer->eligibility }}</p>
            </section>
        @endif
        @if($offer->redemption_steps)
            <section class="mt-9">
                <h2 class="text-2xl font-bold">How to redeem</h2>
                <p class="mt-3 whitespace-pre-line text-slate-700">{{ $offer->redemption_steps }}</p>
            </section>
        @endif
        <p class="mt-10 text-sm text-slate-500">Last updated: {{ $offer->updated_at->format('j M Y') }}@if($offer->ends_at) · Valid until {{ $offer->ends_at->format('j M Y') }}@endif</p>
    </article>
@endsection
