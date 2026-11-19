@props(['paginator'])

<div data-ui="pagination-recovery" class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
    <h2 class="text-xl font-extrabold text-slate-950">This results page is no longer available</h2>
    <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-slate-600">The directory may have changed since you opened this link. Return to the first page to see the current results.</p>
    <a href="{{ $paginator->url(1) }}" class="mt-6 inline-flex min-h-11 items-center rounded-xl bg-indigo-700 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700">Back to first page</a>
</div>
