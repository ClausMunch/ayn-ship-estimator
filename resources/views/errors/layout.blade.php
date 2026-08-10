<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>@yield('title') — AYN Thor Ship Date Estimator</title>
    <style>
        body {
            font-family: 'JetBrains Mono', monospace;
            background: #0c0c14;
            color: #e0e0e8;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 24px;
            box-sizing: border-box;
        }
        .card {
            background: #15151f;
            border: 1px solid #2a2a3a;
            border-radius: 16px;
            padding: 48px;
            text-align: center;
            max-width: 400px;
            width: 100%;
            box-sizing: border-box;
        }
        .error-image {
            display: block;
            width: min(100%, 320px);
            height: auto;
            margin: -24px auto -12px;
        }
        .code {
            font-size: 48px;
            font-weight: 700;
            color: #818cf8;
            margin: 0 0 8px;
        }
        h1 { font-size: 18px; margin: 0 0 12px; font-weight: 500; }
        p { color: #666680; font-size: 13px; margin: 0 0 24px; line-height: 1.6; }
        a { color: #818cf8; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        @hasSection('image')
            <img
                class="error-image"
                src="@yield('image')"
                alt="@yield('image_alt')"
                width="640"
                height="640"
            >
        @endif
        <div class="code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        @unless(View::hasSection('hide_home_link'))
            <a href="/">Back to estimator</a>
        @endunless
    </div>
</body>
</html>
