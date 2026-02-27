AYN SHIPPING SCRAPE ALERT
=========================

{{ $alertMessage }}

Timestamp:     {{ $timestamp }}
Status:        {{ $status }}
Records Found: {{ $recordsFound }}
Records New:   {{ $recordsNew }}
Duration:      {{ $durationMs }}ms
@if($error)
Error:         {{ $error }}
@endif
@if($htmlSnippet)

HTML Snippet (first 2000 chars):
{{ $htmlSnippet }}
@endif
