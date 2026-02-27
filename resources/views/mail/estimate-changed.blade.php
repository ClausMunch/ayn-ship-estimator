<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2>Your shipping estimate changed</h2>

    <p>The estimated ship date for your <strong>AYN Thor {{ $modelName }}</strong> order #{{ $orderPrefix }}xx has changed:</p>

    <table style="margin: 16px 0; border-collapse: collapse;">
        <tr>
            <td style="padding: 8px 16px; border: 1px solid #ddd; font-weight: bold;">Previous estimate</td>
            <td style="padding: 8px 16px; border: 1px solid #ddd;">{{ $oldDate }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 16px; border: 1px solid #ddd; font-weight: bold;">New estimate</td>
            <td style="padding: 8px 16px; border: 1px solid #ddd; color: #818cf8; font-weight: bold;">{{ $newDate }}</td>
        </tr>
    </table>

    <p style="margin: 24px 0;">
        <a href="{{ $siteUrl }}" style="display: inline-block; background: #818cf8; color: #fff; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 600;">
            View your estimate
        </a>
    </p>

    <hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">

    <p style="font-size: 12px; color: #999;">
        <a href="{{ $unsubscribeUrl }}" style="color: #999;">Unsubscribe</a> from shipping estimate notifications.
    </p>
</body>
</html>
