<?php

namespace App\Services;

class GuidePasteCleaner
{
    public function clean(string $markdown): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $markdown);
        $output = [];
        $fence = null;
        $fenceLength = 0;
        $citations = 0;
        $images = 0;
        $screenshots = 0;

        for ($index = 0; $index < count($lines); $index++) {
            $line = $lines[$index];
            if (preg_match('/^ {0,3}(`{3,}|~{3,})(.*)$/', $line, $match)) {
                if ($fence === null) {
                    $fence = $match[1][0];
                    $fenceLength = strlen($match[1]);
                } elseif ($match[1][0] === $fence && strlen($match[1]) >= $fenceLength && trim($match[2]) === '') {
                    $fence = null;
                }
                $output[] = $line;
                continue;
            }
            if ($fence !== null || preg_match('/^(?: {4}|\t)/', $line)) {
                $output[] = $line;
                continue;
            }

            // Only exported citation favicons are removed; normal source links stay.
            if (preg_match('~^\s*!?\[image\]\(https://www\.google\.com/s2/favicons\?[^\s]+\)\s*$~i', $line)) {
                $citations++;
                $next = $index + 1;
                while ($next < count($lines) && trim($lines[$next]) === '') {
                    $next++;
                }
                if (isset($lines[$next]) && preg_match('/^GitHub(?:\+\d+)?$/i', trim($lines[$next]))) {
                    $index = $next;
                }
                continue;
            }

            if (! str_contains($line, '`')) {
                $line = str_replace(['&#x20;', '&#32;'], ' ', $line);
            }
            $line = preg_replace('/^(#{1,6})\s+\*\*(.*?)\*\*\s*$/', '$1 $2', $line);

            // Recover only standalone image-search exports, never arbitrary links.
            if (preg_match('~^\[([^\]\n]+)\]\((https://images\.openai\.com/static-rsc[^\s)]+)\)\s*$~', $line)) {
                $line = '!'.$line;
                $images++;
            }
            if (preg_match('/^(?:SCREENSHOT NEEDED\s*(?:—|–|-|:)\s*|Screenshot:\s*)(.+)$/i', $line, $match)) {
                $line = '> **Screenshot needed:** '.$match[1];
                $screenshots++;
            }

            // Collapse extra exported blank paragraphs outside code only.
            if (trim($line) === '' && ($output === [] || end($output) === '')) {
                continue;
            }
            $output[] = $line;
        }

        $messages = [];
        if ($citations) {
            $messages[] = "Copied citation blocks removed: {$citations}. Original source links are kept.";
        }
        if ($images) {
            $messages[] = "Image links converted to Markdown images: {$images}.";
        }
        if ($screenshots) {
            $messages[] = "Screenshot notes formatted as callouts: {$screenshots}.";
        }
        $body = implode("\n", $output);
        $warnings = [];
        if (str_contains($body, 'https://images.openai.com/static-rsc')) {
            $warnings[] = 'Some images use temporary image-search URLs. Replace them with your own hosted images before publishing.';
        }
        if ($screenshots || str_contains($body, '**Screenshot needed:**')) {
            $warnings[] = 'Screenshot notes are placeholders, not images. Add the actual screenshots when ready.';
        }

        return ['body' => $body, 'messages' => $messages, 'warnings' => $warnings];
    }
}
