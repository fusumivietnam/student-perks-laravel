<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $__env->yieldContent('description', 'Find current student discounts, free plans and education offers from official partners.') }}">
    <meta name="robots" content="{{ $__env->yieldContent('robots', 'index,follow') }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="{{ $__env->yieldContent('og_type', 'website') }}">
    <meta property="og:title" content="{{ $__env->yieldContent('title', 'Student Perks Hub') }}">
    <meta property="og:description" content="{{ $__env->yieldContent('description', 'Find student offers and learning guides.') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <title>{{ $__env->yieldContent('title', 'Student Perks Hub') }} · Student Perks Hub</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-white focus:p-3">Skip to content</a>
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-5">
            <a href="{{ route('home') }}" class="text-xl font-black tracking-tight text-indigo-700">Student Perks Hub</a>
            <nav aria-label="Main navigation" class="flex flex-wrap gap-5 text-sm font-medium">
                <a class="hover:text-indigo-700" href="{{ route('offers.index') }}">All offers</a>
                <a class="hover:text-indigo-700" href="{{ route('brands.index') }}">Brands</a>
                <a class="hover:text-indigo-700" href="{{ route('search') }}">Search</a>
                <a class="hover:text-indigo-700" href="{{ route('articles.index') }}">Guides</a>
            </nav>
        </div>
    </header>
    <main id="main" class="mx-auto min-h-[65vh] max-w-6xl px-4 py-10">
        @yield('content')
    </main>
    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-7 text-sm text-slate-600">
            <a class="mr-4 font-semibold hover:text-indigo-700" href="{{ route('pages.show', 'about') }}">About</a>
            <a class="mr-4 font-semibold hover:text-indigo-700" href="{{ route('pages.show', 'privacy') }}">Privacy</a>
            <a class="mr-4 font-semibold hover:text-indigo-700" href="{{ route('pages.show', 'terms') }}">Terms</a>
            <a class="mr-4 font-semibold hover:text-indigo-700" href="{{ route('pages.show', 'contact') }}">Contact</a>
            <span class="block mt-3">Offers are informational. Partners determine eligibility, availability and redemption. Always verify terms on the partner website.</span>
        </div>
    </footer>
</body>
</html>
