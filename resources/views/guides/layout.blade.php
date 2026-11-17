<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>{{ $title }} — AYN Thor Guides</title>
    <meta name="description" content="{{ $description }}">
    <meta name="theme-color" content="#0c0c14">
    <meta name="robots" content="{{ ($preview ?? false) ? 'noindex, nofollow' : 'index, follow' }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:type" content="{{ isset($guide) ? 'article' : 'website' }}">
    <meta property="og:site_name" content="AYN Thor Guides">
    <meta name="twitter:card" content="summary">
    @if (isset($guide) && !($preview ?? false))
        <meta property="article:published_time" content="{{ $guide->published_at->toAtomString() }}">
        <meta property="article:modified_time" content="{{ $guide->updated_at->toAtomString() }}">
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org', '@type' => 'Article',
            'headline' => $guide->title, 'description' => $guide->excerpt,
            'mainEntityOfPage' => $canonical,
            'datePublished' => $guide->published_at->toAtomString(),
            'dateModified' => $guide->updated_at->toAtomString(),
            'author' => ['@type' => 'Person', 'name' => $guide->author],
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="guide-site min-h-screen bg-[#0c0c14] text-[#e0e0e8] font-sans antialiased">
    <a href="#main-content" class="skip-link">Skip to content</a>
    <header class="border-b border-[#2a2a3a]">
        <nav aria-label="Main navigation" class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-6 py-5">
            <a href="{{ route('guides.index') }}" class="flex items-center gap-3 font-semibold tracking-tight text-white">
                <span aria-hidden="true" class="flex h-9 w-9 items-center justify-center rounded-lg border border-[#3b3d67] bg-[#202036] font-mono text-sm text-[#a5b4fc]">T</span>
                AYN Thor <span class="font-normal text-[#a8a8bc]">/ Guides</span>
            </a>
            <div class="flex items-center gap-6 text-sm">
                <a href="{{ route('guides.index') }}" class="text-white" aria-current="{{ request()->routeIs('guides.index') ? 'page' : 'false' }}">Guides</a>
                <a href="/" class="text-[#a8a8bc] hover:text-white">Shipping estimator <span aria-hidden="true">↗</span></a>
            </div>
        </nav>
    </header>
    <main id="main-content" class="mx-auto max-w-6xl px-6 py-10 sm:py-16">@yield('content')</main>
    <footer class="mx-auto flex max-w-6xl flex-wrap justify-between gap-4 border-t border-[#2a2a3a] px-6 py-8 text-xs leading-6 text-[#a8a8bc]">
        <p>Independent guides for your handheld. Not affiliated with AYN or Blizzard.</p>
        <a href="/" class="hover:text-white">Check your estimated ship date <span aria-hidden="true">→</span></a>
    </footer>
</body>
</html>
