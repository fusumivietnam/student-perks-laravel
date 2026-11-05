<article class="flex h-full flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div>
        <p class="mb-3 text-xs font-bold uppercase tracking-widest text-indigo-600">{{ $offer->brand->name }} · {{ $offer->category->name }}</p>
        <h3 class="text-lg font-semibold leading-snug">
            <a class="hover:text-indigo-700" href="{{ route('offers.show', $offer->slug) }}">{{ $offer->title }}</a>
        </h3>
        <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-slate-600">{{ $offer->summary }}</p>
        @if ($offer->offer_value)
            <p class="mt-4 inline-block rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700">{{ $offer->offer_value }}</p>
        @endif
    </div>
    <a class="mt-6 text-sm font-semibold text-indigo-700 hover:underline" href="{{ route('offers.show', $offer->slug) }}">View offer details <span aria-hidden="true">→</span></a>
</article>
