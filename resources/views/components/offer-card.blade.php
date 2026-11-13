<article class="group flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:border-indigo-200 hover:shadow-xl hover:shadow-indigo-950/10 focus-within:border-indigo-400 focus-within:ring-2 focus-within:ring-indigo-300">
    <div class="relative flex min-h-36 items-center justify-between overflow-hidden bg-gradient-to-br from-indigo-50 via-violet-50 to-sky-50 px-6 py-5">
        <div class="absolute -right-8 -top-12 h-40 w-40 rounded-full border-[22px] border-white/60" aria-hidden="true"></div>
        <x-brand-mark :brand="$offer->brand" class="relative h-16 w-16" text-class="text-xl" />
        @if($offer->offer_value)
            <span class="relative max-w-40 rounded-full bg-white px-3 py-1.5 text-right text-xs font-bold text-indigo-800 shadow-sm">{{ $offer->offer_value }}</span>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-6">
        <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">{{ $offer->brand->name }} <span class="mx-1 text-slate-300" aria-hidden="true">·</span> {{ $offer->category->name }}</p>
        <h3 class="mt-3 text-xl font-bold leading-snug tracking-tight text-slate-950">
            <a class="rounded-sm outline-offset-4 hover:text-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-600" href="{{ route('offers.show', $offer->slug) }}">{{ $offer->title }}</a>
        </h3>
        <p class="mt-3 line-clamp-3 flex-1 text-sm leading-relaxed text-slate-600">{{ $offer->summary }}</p>
        @if($offer->ends_at)
            <p class="mt-4 text-sm font-semibold text-slate-700">Ends <time datetime="{{ $offer->ends_at->toDateString() }}">{{ $offer->ends_at->format('j M Y') }}</time></p>
        @endif
        <div class="mt-6 border-t border-slate-100 pt-4">
            <a class="inline-flex min-h-10 items-center gap-2 rounded-md text-sm font-bold text-indigo-700 outline-offset-4 group-hover:gap-3 hover:text-indigo-900 focus-visible:outline-2 focus-visible:outline-indigo-600" href="{{ route('offers.show', $offer->slug) }}">View offer details <span aria-hidden="true">→</span></a>
        </div>
    </div>
</article>
