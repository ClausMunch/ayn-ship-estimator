@php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ $url }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ \App\Support\SiteUrl::to('/guides') }}</loc>
        <changefreq>weekly</changefreq>
    </url>
    <url>
        <loc>{{ \App\Support\SiteUrl::to('/about') }}</loc>
    </url>
    @foreach ($guides as $guide)
        <url>
            <loc>{{ \App\Support\SiteUrl::to('/guides/'.$guide->slug) }}</loc>
            <lastmod>{{ $guide->updated_at->toAtomString() }}</lastmod>
        </url>
    @endforeach
</urlset>
