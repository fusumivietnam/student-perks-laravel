@props(['title', 'eyebrow' => null, 'description' => null])

<header data-ui="public-page-heading" {{ $attributes->class(['min-w-0 max-w-3xl']) }}>
    @if(filled($eyebrow))
        <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-indigo-600">{{ $eyebrow }}</p>
    @endif
    <h1 class="mt-2 break-words text-3xl font-extrabold leading-tight tracking-tight text-slate-950 sm:text-4xl">{{ $title }}</h1>
    @if(filled($description))
        <p class="mt-3 break-words text-base leading-relaxed text-slate-600 sm:text-lg">{{ $description }}</p>
    @endif
    {{ $slot }}
</header>
