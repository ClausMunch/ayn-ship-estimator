@php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ $url }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ route('guides.index') }}</loc>
        <changefreq>weekly</changefreq>
    </url>
    @foreach ($guides as $guide)
        <url>
            <loc>{{ route('guides.show', $guide->slug) }}</loc>
            <lastmod>{{ $guide->updated_at->toAtomString() }}</lastmod>
        </url>
    @endforeach
</urlset>
