<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AYN Thor Ship Date Estimator</title>
    <meta name="description"
        content="Find out when your AYN Thor handheld will ship. Enter your order number and get an estimated ship date based on real shipping data.">
    <meta property="og:title" content="AYN Thor Ship Date Estimator">
    <meta property="og:description"
        content="Find out when your AYN Thor handheld will ship. Enter your order number and get an estimated ship date.">
    <meta property="og:type" content="website">
    <meta name="theme-color" content="#0c0c14">
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
