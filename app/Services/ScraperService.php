<?php

namespace App\Services;

use App\Models\ModelVariant;
use App\Models\ShippingBatch;
use Illuminate\Support\Facades\Http;
use Spatie\Browsershot\Browsershot;

class ScraperService
{
    private const URL = 'https://www.ayntec.com/pages/shipment-dashboard';

    /**
     * Mapping of names found on the AYN page to our model variant slugs.
     */
    private const NAME_TO_SLUG = [
        'Rainbow Max' => 'rainbow-max',
        'Rainbow Pro' => 'rainbow-pro',
        'Black Max' => 'black-max',
        'Black Pro' => 'black-pro',
        'Black Base' => 'black-base',
        'Black Lite' => 'black-lite',
        'White Max' => 'white-max',
        'White Pro' => 'white-pro',
        'Clear Purple Max' => 'clear-purple-max',
        'Clear Purple Pro' => 'clear-purple-pro',
    ];

    public function run(): ScrapeResult
    {
        $startTime = hrtime(true);
        $fallbackNote = null;
        $usedFallback = false;
        $usedHttpFallback = false;
        $configuredChromePath = config('services.browsershot.chrome_path');
        $browserErrors = [];

        try {
            try {
                $html = $this->makeBrowsershot($configuredChromePath)->bodyHtml();
            } catch (\Throwable $primaryError) {
                $browserErrors[] = 'primary=' . $primaryError->getMessage();

                try {
                    $usedFallback = true;
                    $html = $this->makeBrowsershot(resetExecutablePathEnv: true)->bodyHtml();
                    $fallbackNote = sprintf(
                        'Configured browser failed (%s), fallback launch succeeded. Primary error: %s',
                        $configuredChromePath ?: '(unset)',
                        $primaryError->getMessage(),
                    );
                } catch (\Throwable $fallbackError) {
                    $browserErrors[] = 'fallback=' . $fallbackError->getMessage();
                    $usedHttpFallback = true;
                    $html = $this->fetchHtmlViaHttp();

                    $fallbackNote = sprintf('Browsershot failed, HTTP fallback succeeded. %s', implode(
                        ' | ',
                        $browserErrors,
                    ));
                }
            }

            $records = $this->parse($html);
            $new = $this->upsert($records);

            return new ScrapeResult(
                status: 'success',
                recordsFound: count($records),
                recordsNew: $new,
                error: $fallbackNote,
                runtimeContext: $this->buildRuntimeContext($configuredChromePath, $usedFallback, $usedHttpFallback),
                durationMs: $this->elapsed($startTime),
                htmlSnippet: mb_substr($html, 0, 2000),
            );
        } catch (\Throwable $e) {
            return new ScrapeResult(
                status: 'failed',
                error: $e->getMessage(),
                runtimeContext: $this->buildRuntimeContext($configuredChromePath, $usedFallback, $usedHttpFallback),
                durationMs: $this->elapsed($startTime),
            );
        }
    }

    private function buildRuntimeContext(
        ?string $configuredChromePath,
        bool $usedFallback,
        bool $usedHttpFallback,
    ): string {
        $runtimeUser = getenv('USER') ?: get_current_user();

        return sprintf(
            'user=%s; configured_chrome_path=%s; env_puppeteer_executable_path=%s; fallback=%s; http_fallback=%s',
            $runtimeUser ?: 'unknown',
            $configuredChromePath ?: '(unset)',
            getenv('PUPPETEER_EXECUTABLE_PATH') ?: '(unset)',
            $usedFallback ? 'yes' : 'no',
            $usedHttpFallback ? 'yes' : 'no',
        );
    }

    private function fetchHtmlViaHttp(): string
    {
        $response = Http::timeout(30)->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (X11; Linux arm64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',
        ])->get(self::URL);

        if (!$response->successful()) {
            throw new \RuntimeException('HTTP fallback failed with status ' . $response->status());
        }

        return $response->body();
    }

    private function makeBrowsershot(?string $chromePath = null, bool $resetExecutablePathEnv = false): Browsershot
    {
        $browsershot = Browsershot::url(self::URL)->setNodeModulePath(base_path(
            'node_modules/',
        ))->waitUntilNetworkIdle();

        if ($resetExecutablePathEnv) {
            $browsershot->setNodeEnv([
                'PUPPETEER_EXECUTABLE_PATH' => '',
            ]);
        }

        if (config('services.browsershot.no_sandbox')) {
            $browsershot->noSandbox();
        }

        $chromiumArgs = config('services.browsershot.chromium_args', []);
        if (!empty($chromiumArgs)) {
            $browsershot->addChromiumArguments($chromiumArgs);
        }

        if ($chromePath) {
            $browsershot->setChromePath($chromePath);
        }

        return $browsershot;
    }

    /**
     * Parse the shipping dashboard HTML into structured records.
     *
     * Expected format:
     *   2026/1/15
     *   AYN Thor Rainbow Max: 1062xx--1114xx
     *   AYN Thor Rainbow Pro: 1050xx--1109xx
     *
     * @return array<int, array{date: string, slug: string, start: int, end: int}>
     */
    public function parse(string $html): array
    {
        // Strip HTML tags to get plain text content
        $text = strip_tags($html);
        $lines = preg_split('/\r?\n/', $text);

        $records = [];
        $currentDate = null;

        foreach ($lines as $line) {
            $line = trim($line);

            // Match date heading: 2026/1/15 or 2026/01/15
            if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $line, $m)) {
                $currentDate = sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
                continue;
            }

            // Match shipping line: AYN Thor Rainbow Max: 1062xx--1114xx
            if ($currentDate && preg_match('/AYN\s+Thor\s+(.+?):\s*(\d{4})xx\s*--\s*(\d{4})xx/', $line, $m)) {
                $name = trim($m[1]);
                $slug = self::NAME_TO_SLUG[$name] ?? null;

                if ($slug) {
                    $records[] = [
                        'date' => $currentDate,
                        'slug' => $slug,
                        'start' => (int) $m[2],
                        'end' => (int) $m[3],
                    ];
                }
            }
        }

        return $records;
    }

    /**
     * Upsert parsed records into shipping_batches.
     * Returns count of newly created records.
     */
    private function upsert(array $records): int
    {
        $slugToId = ModelVariant::pluck('id', 'slug');
        $rowsByKey = [];
        $variantIds = [];
        $dates = [];
        $starts = [];
        $now = now();

        foreach ($records as $record) {
            $variantId = $slugToId[$record['slug']] ?? null;
            if (!$variantId) {
                continue;
            }

            $key = implode('|', [$variantId, $record['date'], $record['start']]);

            $rowsByKey[$key] = [
                'model_variant_id' => $variantId,
                'ship_date' => $record['date'],
                'order_range_start' => $record['start'],
                'order_range_end' => $record['end'],
                'scraped_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $variantIds[] = $variantId;
            $dates[] = $record['date'];
            $starts[] = $record['start'];
        }

        if (empty($rowsByKey)) {
            return 0;
        }

        $existing = ShippingBatch::query()
            ->whereIn('model_variant_id', array_values(array_unique($variantIds)))
            ->whereIn('ship_date', array_values(array_unique($dates)))
            ->whereIn('order_range_start', array_values(array_unique($starts)))
            ->get(['model_variant_id', 'ship_date', 'order_range_start'])
            ->map(function (ShippingBatch $batch): string {
                return implode('|', [
                    $batch->model_variant_id,
                    $batch->ship_date->toDateString(),
                    $batch->order_range_start,
                ]);
            })
            ->flip();

        $new = 0;
        foreach (array_keys($rowsByKey) as $key) {
            if (!isset($existing[$key])) {
                $new++;
            }
        }

        ShippingBatch::query()->upsert(
            array_values($rowsByKey),
            ['model_variant_id', 'ship_date', 'order_range_start'],
            ['order_range_end', 'scraped_at', 'updated_at'],
        );

        return $new;
    }

    private function elapsed(int $startNs): int
    {
        return (int) ((hrtime(true) - $startNs) / 1_000_000);
    }
}
