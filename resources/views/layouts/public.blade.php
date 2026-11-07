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
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:p-3 focus:text-indigo-800 focus:shadow-lg">Skip to content</a>
    <header class="sticky top-0 z-40 border-b border-slate-200/90 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-6 gap-y-4 px-4 py-4 sm:px-6">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center gap-3 rounded-lg font-extrabold tracking-tight text-indigo-800 focus-visible:outline-2 focus-visible:outline-indigo-700">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-700 text-sm font-black text-white" aria-hidden="true">SP</span>
                <span>Student Perks Hub</span>
            </a>
            <nav aria-label="Main navigation" class="flex flex-wrap items-center gap-x-5 gap-y-1 text-sm font-semibold text-slate-700">
                <a class="inline-flex min-h-10 items-center rounded-md px-1 hover:text-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-700" href="{{ route('offers.index') }}" @if(request()->routeIs('offers.index')) aria-current="page" @endif>All offers</a>
                <a class="inline-flex min-h-10 items-center rounded-md px-1 hover:text-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-700" href="{{ route('brands.index') }}" @if(request()->routeIs('brands.index')) aria-current="page" @endif>Brands</a>
                <a class="inline-flex min-h-10 items-center rounded-md px-1 hover:text-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-700" href="{{ route('search') }}" @if(request()->routeIs('search')) aria-current="page" @endif>Search</a>
                <a class="inline-flex min-h-10 items-center rounded-md px-1 hover:text-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-700" href="{{ route('articles.index') }}" @if(request()->routeIs('articles.index')) aria-current="page" @endif>Guides</a>
            </nav>
        </div>
    </header>
    <main id="main" class="mx-auto min-h-[65vh] max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
        @yield('content')
    </main>
    <footer class="mt-16 border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-10 text-sm text-slate-600 sm:px-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <span class="text-base font-extrabold text-indigo-800">Student Perks Hub</span>
                <nav aria-label="Information and policies" class="flex flex-wrap gap-x-6 gap-y-2">
                    @foreach(['about' => 'About', 'privacy' => 'Privacy', 'terms' => 'Terms', 'contact' => 'Contact'] as $slug => $label)
                        @if($footerPages->contains($slug))
                            <a class="font-semibold hover:text-indigo-700 focus-visible:underline" href="{{ route('pages.show', $slug) }}">{{ $label }}</a>
                        @endif
                    @endforeach
                </nav>
            </div>
            <p class="mt-6 max-w-3xl leading-relaxed">Offers are informational. Partners determine eligibility, availability and redemption. Always verify terms on the partner website.</p>
        </div>
    </footer>
</body>
</html>
