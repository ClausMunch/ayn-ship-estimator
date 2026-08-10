<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>Thank you — AYN Thor Ship Date Estimator</title>
</head>
<body style="font-family:monospace;background:#0c0c14;color:#e0e0e8;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:24px;box-sizing:border-box">
    <main style="background:#15151f;border:1px solid #2a2a3a;border-radius:16px;padding:40px;text-align:center;max-width:440px">
        <h1 style="color:#fff;font-size:22px">Thank you!</h1>
        <p style="color:#a8a8bc;line-height:1.6">
            Your {{ $milestone === 'delivered' ? 'delivery' : 'shipment' }} confirmation was recorded and will help improve future estimates.
        </p>
        <a href="/" style="color:#818cf8">Back to estimator</a>
    </main>
</body>
</html>
