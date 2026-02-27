<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #c0392b;">AYN Shipping Scrape Alert</h2>

    <p><strong>{{ $alertMessage }}</strong></p>

    <table style="border-collapse: collapse; width: 100%; margin: 16px 0;">
        <tr><td style="padding: 6px 12px; border: 1px solid #ddd; font-weight: bold;">Timestamp</td><td style="padding: 6px 12px; border: 1px solid #ddd;">{{ $timestamp }}</td></tr>
        <tr><td style="padding: 6px 12px; border: 1px solid #ddd; font-weight: bold;">Status</td><td style="padding: 6px 12px; border: 1px solid #ddd;">{{ $status }}</td></tr>
        <tr><td style="padding: 6px 12px; border: 1px solid #ddd; font-weight: bold;">Records Found</td><td style="padding: 6px 12px; border: 1px solid #ddd;">{{ $recordsFound }}</td></tr>
        <tr><td style="padding: 6px 12px; border: 1px solid #ddd; font-weight: bold;">Records New</td><td style="padding: 6px 12px; border: 1px solid #ddd;">{{ $recordsNew }}</td></tr>
        <tr><td style="padding: 6px 12px; border: 1px solid #ddd; font-weight: bold;">Duration</td><td style="padding: 6px 12px; border: 1px solid #ddd;">{{ $durationMs }}ms</td></tr>
        @if($error)
        <tr><td style="padding: 6px 12px; border: 1px solid #ddd; font-weight: bold;">Error</td><td style="padding: 6px 12px; border: 1px solid #ddd; color: #c0392b;">{{ $error }}</td></tr>
        @endif
    </table>

    @if($htmlSnippet)
    <h3>HTML Snippet (first 2000 chars)</h3>
    <pre style="background: #f5f5f5; padding: 12px; border-radius: 4px; font-size: 11px; overflow-x: auto; white-space: pre-wrap; word-break: break-all;">{{ $htmlSnippet }}</pre>
    @endif
</body>
</html>
