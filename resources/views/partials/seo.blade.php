<title>{{ $pageTitle ?? $title.' — AYN Thor Guides' }}</title>
<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $robots ?? 'index, follow, max-image-preview:large' }}">
<link rel="canonical" href="{{ $canonical }}">
<link rel="sitemap" type="application/xml" href="{{ \App\Support\SiteUrl::to('/sitemap.xml') }}">
<meta property="og:title" content="{{ $pageTitle ?? $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:type" content="{{ $ogType ?? 'website' }}">
<meta property="og:site_name" content="AYN Thor Shipping &amp; Guides">
<meta property="og:locale" content="en_US">
<meta property="og:image" content="{{ \App\Support\SiteUrl::to('/images/ayn-thor.png') }}">
<meta property="og:image:alt" content="AYN Thor dual-screen handheld">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle ?? $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ \App\Support\SiteUrl::to('/images/ayn-thor.png') }}">
@if (!empty($structuredData))
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org', '@graph' => $structuredData,
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
