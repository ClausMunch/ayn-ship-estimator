<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,sans-serif;background:#0c0c14;color:#e0e0e8;padding:24px">
    <div style="max-width:560px;margin:auto;background:#15151f;border:1px solid #2a2a3a;border-radius:12px;padding:28px">
        <h1 style="font-size:20px;color:#fff">
            Has your AYN Thor {{ $milestone === 'delivered' ? 'arrived' : 'shipped' }}?
        </h1>
        <p style="line-height:1.6;color:#a8a8bc">
            Our estimate suggests that order {{ $subscriber->order_prefix }}xx for
            {{ $subscriber->modelVariant->name }} should have
            {{ $milestone === 'delivered' ? 'arrived by now' : 'shipped by now' }}.
        </p>
        <p style="line-height:1.6;color:#a8a8bc">
            Confirming helps improve estimates for everyone. The button records only that this milestone happened.
        </p>
        <p style="margin:28px 0">
            <a href="{{ $confirmationUrl }}" style="background:#4f46e5;color:#fff;padding:12px 18px;border-radius:8px;text-decoration:none">
                Confirm {{ $milestone === 'delivered' ? 'device received' : 'device shipped' }}
            </a>
        </p>
        <p style="font-size:12px;color:#666680">If it has not happened yet, simply ignore this email.</p>
    </div>
</body>
</html>
