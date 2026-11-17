<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    @php
        $robots = ($preview ?? false) ? 'noindex, nofollow' : ($robots ?? 'index, follow, max-image-preview:large');
        $ogType = isset($guide) ? 'article' : 'website';
        $structuredData = $structuredData ?? [];
        if (isset($guide) && !($preview ?? false)) {
            $structuredData[] = [
                '@type' => 'Article', '@id' => $canonical.'#article',
                'headline' => $guide->title, 'description' => $guide->excerpt,
                'mainEntityOfPage' => $canonical, 'inLanguage' => 'en',
                'image' => \App\Support\SiteUrl::to('/images/ayn-thor.png'),
                'datePublished' => $guide->published_at->toAtomString(),
                'dateModified' => $guide->updated_at->toAtomString(),
                'author' => ['@type' => $guide->author === 'AYN Thor Guides' ? 'Organization' : 'Person', 'name' => $guide->author],
                'publisher' => ['@type' => 'Organization', 'name' => 'AYN Thor Shipping & Guides', 'url' => \App\Support\SiteUrl::to('/about')],
                'isPartOf' => ['@id' => \App\Support\SiteUrl::to().'#website'],
            ];
            $structuredData[] = [
                '@type' => 'BreadcrumbList', 'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => \App\Support\SiteUrl::to()],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Guides', 'item' => \App\Support\SiteUrl::to('/guides')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $guide->title, 'item' => $canonical],
                ],
            ];
        }
        $structuredData[] = [
            '@type' => 'WebSite', '@id' => \App\Support\SiteUrl::to().'#website',
            'name' => 'AYN Thor Shipping & Guides', 'url' => \App\Support\SiteUrl::to(), 'inLanguage' => 'en',
        ];
    @endphp
    @include('partials.seo')
    <meta name="theme-color" content="#0c0c14">
    @if (isset($guide) && !($preview ?? false))
        <meta property="article:published_time" content="{{ $guide->published_at->toAtomString() }}">
        <meta property="article:modified_time" content="{{ $guide->updated_at->toAtomString() }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @if (isset($page))
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
        @inertiaHead
    @else
        @vite(['resources/css/app.css'])
    @endif
    @yield('head')
</head>
<body class="guide-site min-h-screen bg-[#0c0c14] text-[#e0e0e8] font-sans antialiased">
    <a href="#main-content" class="skip-link">Skip to content</a>
    <header class="border-b border-[#2a2a3a]">
        <nav aria-label="Main navigation" class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-6 py-5">
            <a href="/" class="flex items-center gap-3 font-semibold tracking-tight text-white">
                <span aria-hidden="true" class="flex h-9 w-9 items-center justify-center rounded-lg border border-[#3b3d67] bg-[#202036] font-mono text-sm text-[#a5b4fc]">T</span>
                AYN Thor <span class="font-normal text-[#a8a8bc]">/ Shipping &amp; guides</span>
            </a>
            <div class="flex flex-wrap items-center gap-5 text-sm">
                <a href="/" class="text-[#a8a8bc] hover:text-white" @if(request()->is('/')) aria-current="page" @endif>Estimator</a>
                <a href="{{ route('guides.index') }}" class="text-[#a8a8bc] hover:text-white" @if(request()->routeIs('guides.*')) aria-current="page" @endif>Guides</a>
                <a href="{{ route('about') }}" class="text-[#a8a8bc] hover:text-white" @if(request()->routeIs('about')) aria-current="page" @endif>About &amp; methodology</a>
            </div>
        </nav>
    </header>
    <main id="main-content" class="mx-auto max-w-6xl px-6 py-10 sm:py-16">@yield('content')</main>
    <footer class="mx-auto flex max-w-6xl flex-wrap justify-between gap-4 border-t border-[#2a2a3a] px-6 py-8 text-xs leading-6 text-[#a8a8bc]">
        <p>Independent shipping estimates and handheld guides. Not affiliated with AYN or Blizzard.</p>
        <a href="{{ route('about') }}" class="hover:text-white">Our data and editorial approach <span aria-hidden="true">→</span></a>
    </footer>
</body>
</html>
