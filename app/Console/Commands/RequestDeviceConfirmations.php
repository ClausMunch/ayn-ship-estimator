<?php

namespace App\Console\Commands;

use App\Mail\DeviceStatusConfirmation;
use App\Models\Subscriber;
use App\Services\EstimationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class RequestDeviceConfirmations extends Command
{
    protected $signature = 'subscribers:request-device-confirmations {--limit=100}';

    protected $description = 'Queue shipment or delivery confirmation requests for eligible subscribers';

    public function handle(EstimationService $estimator): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 1000],
        ]);

        if ($limit === false) {
            $this->error('--limit must be an integer between 1 and 1000.');

            return self::FAILURE;
        }

        $deliveryDays = max(0, (int) config('shipping.delivery_days', 14));
        $followUpDays = max(0, (int) config('shipping.confirmation_follow_up_days', 2));
        $today = now()->startOfDay();
        $timelines = [];
        $queued = 0;

        $subscribers = Subscriber::whereNotNull('email_verified_at')
            ->where('delivery_status', '!=', 'bounced')
            ->where(function ($query): void {
                $query->whereNull('shipped_confirmation_sent_at')
                    ->orWhereNull('delivered_confirmation_sent_at');
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($subscribers as $subscriber) {
            $variantId = $subscriber->model_variant_id;
            $timelines[$variantId] ??= $estimator->buildTimeline($variantId);
            $shipDate = $estimator->estimateDateFromTimeline(
                $timelines[$variantId],
                $subscriber->order_prefix,
            )?->startOfDay();

            if (! $shipDate) {
                continue;
            }

            $deliveryPromptDate = $shipDate->copy()->addDays($deliveryDays + $followUpDays);
            $shippingPromptDate = $shipDate->copy()->addDays($followUpDays);
            $milestone = null;

            if (! $subscriber->delivered_confirmation_sent_at && $today->gte($deliveryPromptDate)) {
                $milestone = 'delivered';
            } elseif (! $subscriber->shipped_confirmation_sent_at && $today->gte($shippingPromptDate)) {
                $milestone = 'shipped';
            }

            if (! $milestone) {
                continue;
            }

            Mail::to($subscriber->email)->queue(
                (new DeviceStatusConfirmation($subscriber, $milestone))->onQueue('mail'),
            );
            $queued++;
        }

        $this->info("Queued {$queued} device confirmation request(s).");

        return self::SUCCESS;
    }
}
