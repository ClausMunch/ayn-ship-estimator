<?php

namespace App\Services;

use App\Models\ShippingBatch;
use Carbon\Carbon;

class EstimationService
{
    /**
     * Estimate ship date for a given model variant and 4-digit order prefix.
     *
     * @return array{type: string, date?: string, formatted: string}|null
     */
    public function estimate(int $modelVariantId, int $orderPrefix): ?array
    {
        $timeline = $this->buildTimeline($modelVariantId);

        if (empty($timeline)) {
            return null;
        }

        // Already shipped
        if ($orderPrefix <= $timeline[0]['end']) {
            return [
                'type' => 'shipped',
                'date' => $timeline[0]['date'],
                'formatted' => $this->formatDate($this->toTimestamp($timeline[0]['date'])),
            ];
        }

        // Interpolate within known range
        for ($i = 1; $i < count($timeline); $i++) {
            if ($orderPrefix <= $timeline[$i]['end']) {
                $prevEnd = $timeline[$i - 1]['end'];
                $currEnd = $timeline[$i]['end'];
                $prevTs = $this->toTimestamp($timeline[$i - 1]['date']);
                $currTs = $this->toTimestamp($timeline[$i]['date']);

                if ($currEnd === $prevEnd) {
                    return [
                        'type' => 'shipped',
                        'date' => $timeline[$i]['date'],
                        'formatted' => $this->formatDate($currTs),
                    ];
                }

                $ratio = ($orderPrefix - $prevEnd) / ($currEnd - $prevEnd);
                $estimatedTs = $prevTs + $ratio * ($currTs - $prevTs);

                return [
                    'type' => 'estimated',
                    'formatted' => $this->formatDate($estimatedTs),
                ];
            }
        }

        // Extrapolate beyond known data using the global timeline (all models combined).
        // Order numbers come from a single pool, so the global rate is more accurate.
        $globalTimeline = $this->buildGlobalTimeline();
        $extTimeline = count($globalTimeline) >= 2 ? $globalTimeline : $timeline;

        if (count($extTimeline) >= 2) {
            $last = $extTimeline[count($extTimeline) - 1];
            $lastTs = $this->toTimestamp($last['date']);

            // If order is within the global frontier, interpolate on the global timeline
            if ($extTimeline === $globalTimeline && $orderPrefix <= $last['end']) {
                for ($i = 1; $i < count($globalTimeline); $i++) {
                    if ($orderPrefix <= $globalTimeline[$i]['end']) {
                        $prevEnd = $globalTimeline[$i - 1]['end'];
                        $currEnd = $globalTimeline[$i]['end'];
                        $prevTs = $this->toTimestamp($globalTimeline[$i - 1]['date']);
                        $currTs = $this->toTimestamp($globalTimeline[$i]['date']);

                        if ($currEnd === $prevEnd) {
                            return [
                                'type' => 'extrapolated',
                                'formatted' => $this->formatDate($currTs),
                            ];
                        }

                        $ratio = ($orderPrefix - $prevEnd) / ($currEnd - $prevEnd);
                        $estimatedTs = $prevTs + $ratio * ($currTs - $prevTs);

                        return [
                            'type' => 'extrapolated',
                            'formatted' => $this->formatDate($estimatedTs),
                        ];
                    }
                }
            }

            // Collect rates (ms per order) and order volumes from up to 5 most recent pairs
            $maxPairs = min(count($extTimeline) - 1, 5);
            $pairs = [];
            for ($i = count($extTimeline) - 1; $i >= count($extTimeline) - $maxPairs; $i--) {
                $orderDiff = $extTimeline[$i]['end'] - $extTimeline[$i - 1]['end'];
                if ($orderDiff > 0) {
                    $timeDiff = $this->toTimestamp($extTimeline[$i]['date']) - $this->toTimestamp($extTimeline[$i - 1]['date']);
                    $pairs[] = ['rate' => $timeDiff / $orderDiff, 'volume' => $orderDiff];
                }
            }

            if (empty($pairs)) {
                return null;
            }

            // Weighted average: weight by recency (most recent first) × order volume
            $weightedSum = 0;
            $weightTotal = 0;
            for ($i = 0; $i < count($pairs); $i++) {
                $recency = count($pairs) - $i;
                $weight = $recency * $pairs[$i]['volume'];
                $weightedSum += $pairs[$i]['rate'] * $weight;
                $weightTotal += $weight;
            }

            $avgRate = $weightedSum / $weightTotal;
            $extra = ($orderPrefix - $last['end']) * $avgRate;

            return [
                'type' => 'extrapolated',
                'formatted' => $this->formatDate($lastTs + $extra),
            ];
        }

        return null;
    }

    /**
     * Build a global timeline from ALL models' batches combined.
     * Uses the max order_range_end across all models per date.
     *
     * @return array<int, array{date: string, end: int}>
     */
    public function buildGlobalTimeline(): array
    {
        $batches = ShippingBatch::all();

        // Group by date, take max end across ALL models per date
        $map = [];
        foreach ($batches as $batch) {
            $date = $batch->ship_date->format('Y-m-d');
            $end = $batch->order_range_end;
            if (!isset($map[$date]) || $end > $map[$date]) {
                $map[$date] = $end;
            }
        }

        // Sort by date (chronological), then compute running max of end
        // so that both date and end are monotonically increasing
        ksort($map);

        $points = [];
        $runningMax = 0;
        foreach ($map as $date => $end) {
            if ($end > $runningMax) {
                $runningMax = $end;
                $points[] = ['date' => $date, 'end' => $runningMax];
            }
        }

        return $points;
    }

    /**
     * Build deduplicated timeline sorted by cumulative end points.
     * Matches the JS buildTimeline() function.
     *
     * @return array<int, array{date: string, end: int}>
     */
    public function buildTimeline(int $modelVariantId): array
    {
        $batches = ShippingBatch::where('model_variant_id', $modelVariantId)
            ->orderBy('order_range_end')
            ->get();

        return $this->buildTimelineFromBatches($batches);
    }

    /**
     * @return array<int, array{date: string, end: int}>
     */
    private function buildTimelineFromBatches($batches): array
    {
        // Group by date, keep max end per date
        $map = [];
        foreach ($batches as $batch) {
            $date = $batch->ship_date->format('Y-m-d');
            $end = $batch->order_range_end;
            if (!isset($map[$date]) || $end > $map[$date]) {
                $map[$date] = $end;
            }
        }

        // Convert to sorted array
        $points = [];
        foreach ($map as $date => $end) {
            $points[] = ['date' => $date, 'end' => $end];
        }
        usort($points, fn ($a, $b) => $a['end'] <=> $b['end']);

        // Deduplicate: if two points have the same end, keep the earlier date
        $deduped = [];
        foreach ($points as $p) {
            if (empty($deduped) || $p['end'] > $deduped[count($deduped) - 1]['end']) {
                $deduped[] = $p;
            }
        }

        return $deduped;
    }

    /**
     * Get estimated date as a Carbon instance (for comparison in notify command).
     */
    public function estimateDate(int $modelVariantId, int $orderPrefix): ?Carbon
    {
        $result = $this->estimate($modelVariantId, $orderPrefix);

        if (!$result) {
            return null;
        }

        $dateStr = $result['date'] ?? $this->parseDateFromFormatted($result['formatted']);

        return Carbon::parse($dateStr);
    }

    /**
     * Convert YYYY-MM-DD to millisecond timestamp (matching JS Date.getTime()).
     */
    private function toTimestamp(string $date): float
    {
        return Carbon::parse($date . 'T00:00:00Z')->getTimestampMs();
    }

    /**
     * Format a millisecond timestamp to match JS output: "Wed, Jan 15, 2026"
     */
    private function formatDate(float $ms): string
    {
        $carbon = Carbon::createFromTimestampMs($ms, 'UTC');

        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        return sprintf(
            '%s, %s %d, %d',
            $days[$carbon->dayOfWeek],
            $months[$carbon->month - 1],
            $carbon->day,
            $carbon->year,
        );
    }

    /**
     * Format a Carbon date for use in notification emails.
     */
    public function formatDateForEmail(Carbon $date): string
    {
        return $this->formatDate($date->getTimestampMs());
    }

    /**
     * Parse "Wed, Jan 15, 2026" back to "2026-01-15".
     */
    private function parseDateFromFormatted(string $formatted): string
    {
        // Remove day name prefix
        $dateStr = preg_replace('/^\w+,\s*/', '', $formatted);
        return Carbon::parse($dateStr)->format('Y-m-d');
    }
}
