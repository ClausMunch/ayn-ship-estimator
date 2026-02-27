<?php

namespace App\Services;

class ScrapeResult
{
    public function __construct(
        public readonly string $status,
        public readonly int $recordsFound = 0,
        public readonly int $recordsNew = 0,
        public readonly ?string $error = null,
        public readonly ?int $durationMs = null,
        public readonly ?string $htmlSnippet = null,
    ) {}

    public function failed(): bool
    {
        return $this->status === 'failed';
    }
}
