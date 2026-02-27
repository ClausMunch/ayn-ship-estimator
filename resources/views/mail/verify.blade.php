<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2>Verify your subscription</h2>

    <p>Click the button below to verify your email. You'll be notified when the estimated ship date for your <strong>AYN Thor {{ $modelName }}</strong> order #{{ $orderPrefix }}xx changes.</p>

    <p style="margin: 24px 0;">
        <a href="{{ $verifyUrl }}" style="display: inline-block; background: #818cf8; color: #fff; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 600;">
            Verify Email
        </a>
    </p>

    <p style="font-size: 13px; color: #888;">If you didn't request this, you can safely ignore this email.</p>
</body>
</html>
