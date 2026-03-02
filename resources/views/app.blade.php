<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>AYN Thor Ship Date Estimator — Check When Your Order Ships</title>
    <meta name="description"
        content="Check when your AYN Thor handheld gaming device will ship. Enter your 4-digit order number, pick your model (Rainbow, Black, White, Clear Purple — Max, Pro, Base, Lite), and get an estimated ship date based on real shipping data from AYN.">
    <meta name="keywords"
        content="AYN Thor, ship date, shipping estimate, order tracking, handheld gaming, AYN Thor Rainbow, AYN Thor Black, AYN Thor White, AYN Thor Clear Purple, ayntec">
    <link rel="canonical" href="{{ config('app.url') }}">

    {{-- Open Graph --}}
    <meta property="og:title" content="AYN Thor Ship Date Estimator">
    <meta property="og:description"
        content="Enter your order number and model to get an estimated ship date for your AYN Thor handheld, based on real shipping batch data.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ config('app.url') }}">
    <meta property="og:site_name" content="AYN Thor Ship Date Estimator">
    <meta property="og:locale" content="en_US">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="AYN Thor Ship Date Estimator">
    <meta name="twitter:description"
        content="Check when your AYN Thor handheld will ship based on real shipping data from AYN.">

    <meta name="theme-color" content="#0c0c14">
    <meta name="robots" content="index, follow">

    {{-- JSON-LD Structured Data --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebApplication",
        "name": "AYN Thor Ship Date Estimator",
        "url": "{{ config('app.url') }}",
        "description": "Check when your AYN Thor handheld gaming device will ship. Enter your order number and model variant to get an estimated ship date based on real shipping data.",
        "applicationCategory": "UtilityApplication",
        "operatingSystem": "Any",
        "offers": {
            "@@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        }
    }
    </script>

    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4645062662712175"
        crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>

<body>
    @inertia
</body>

</html>
