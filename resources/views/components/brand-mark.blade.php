@props(['brand', 'class' => 'h-14 w-14', 'textClass' => 'text-base'])

@php
    $hasLogo = filled($brand->logo_path)
        && \Illuminate\Support\Facades\Storage::disk('public')->exists($brand->logo_path);
@endphp

<span {{ $attributes->class(['inline-flex shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-white bg-white font-extrabold text-indigo-800 shadow-sm', $class]) }} aria-hidden="true">
    @if($hasLogo)
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($brand->logo_path) }}"
             alt=""
             loading="lazy"
             class="h-full w-full object-contain p-1.5">
    @else
        <span class="{{ $textClass }}">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($brand->name, 0, 2)) }}</span>
    @endif
</span>
