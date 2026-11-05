<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('description', 'Find current student discounts, free plans and education offers from official partners.')">
    <title>@yield('title', 'Student Perks Hub') · Student Perks Hub</title>
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
            </nav>
        </div>
    </header>
    <main id="main" class="mx-auto min-h-[65vh] max-w-6xl px-4 py-10">
        @yield('content')
    </main>
    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-7 text-sm text-slate-600">
            Offers are informational. Partners determine eligibility, availability and redemption. Always verify terms on the partner website.
        </div>
    </footer>
</body>
</html>
