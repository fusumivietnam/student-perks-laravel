@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($locations as $location)
    <url><loc>{{ $location }}</loc></url>
@endforeach
</urlset>
