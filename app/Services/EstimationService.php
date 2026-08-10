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
        return $this->estimateFromTimeline($this->buildTimeline($modelVariantId), $orderPrefix);
    }

    /**
     * @param  array<int, array{date: string, end: int}>  $timeline
     * @return array{type: string, date?: string, formatted: string}|null
     */
    public function estimateFromTimeline(array $timeline, int $orderPrefix): ?array
    {

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

        // Extrapolate beyond known data using the model's overall shipping rate.
        // Using the full history (first to last point) gives a stable, model-specific
        // rate that isn't skewed by small cleanup batches.
        if (count($timeline) >= 2) {
            $first = $timeline[0];
            $last = $timeline[count($timeline) - 1];
            $lastTs = $this->toTimestamp($last['date']);
            $firstTs = $this->toTimestamp($first['date']);

            $totalOrders = $last['end'] - $first['end'];
            $totalTime = $lastTs - $firstTs;

            if ($totalOrders <= 0 || $totalTime <= 0) {
                return null;
            }

            $overallRate = $totalTime / $totalOrders; // ms per order number

            $extra = ($orderPrefix - $last['end']) * $overallRate;

            return [
                'type' => 'extrapolated',
                'formatted' => $this->formatDate($lastTs + $extra),
            ];
        }

        return null;
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
            if (! isset($map[$date]) || $end > $map[$date]) {
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

        return $this->dateFromResult($result);
    }

    /**
     * @param  array<int, array{date: string, end: int}>  $timeline
     */
    public function estimateDateFromTimeline(array $timeline, int $orderPrefix): ?Carbon
    {
        return $this->dateFromResult($this->estimateFromTimeline($timeline, $orderPrefix));
    }

    /**
     * @param  array{type: string, date?: string, formatted: string}|null  $result
     */
    private function dateFromResult(?array $result): ?Carbon
    {

        if (! $result) {
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
        return Carbon::parse($date.'T00:00:00Z')->getTimestampMs();
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
