<?php

namespace App\Console\Commands;

use App\Mail\EstimateChanged;
use App\Models\Subscriber;
use App\Services\EstimationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class Notify extends Command
{
    protected $signature = 'notify';
    protected $description = 'Check for estimate changes and notify verified subscribers';

    public function handle(EstimationService $estimator): int
    {
        $subscribers = Subscriber::whereNotNull('email_verified_at')
            ->with('modelVariant')
            ->get();

        $this->info("Checking estimates for {$subscribers->count()} verified subscribers...");

        $notified = 0;

        foreach ($subscribers as $subscriber) {
            $result = $estimator->estimate($subscriber->model_variant_id, $subscriber->order_prefix);

            if (!$result) {
                continue;
            }

            $newDate = $estimator->estimateDate($subscriber->model_variant_id, $subscriber->order_prefix);

            if (!$newDate) {
                continue;
            }

            $lastDate = $subscriber->last_estimated_date;

            // If no previous estimate, just set it without notifying
            if (!$lastDate) {
                $subscriber->update(['last_estimated_date' => $newDate->toDateString()]);
                continue;
            }

            // Only notify if estimate changed by >= 2 days
            $daysDiff = abs($lastDate->diffInDays($newDate));

            if ($daysDiff >= 2) {
                $oldFormatted = $estimator->formatDateForEmail($lastDate);
                $newFormatted = $estimator->formatDateForEmail($newDate);

                Mail::to($subscriber->email)->queue(
                    new EstimateChanged($subscriber, $oldFormatted, $newFormatted)
                );

                $subscriber->update(['last_estimated_date' => $newDate->toDateString()]);
                $notified++;

                $this->line("  Notified {$subscriber->email}: {$oldFormatted} → {$newFormatted}");
            }
        }

        $this->info("Done. Notified {$notified} subscribers.");

        return self::SUCCESS;
    }
}
