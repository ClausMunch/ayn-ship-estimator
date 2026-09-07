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
            ->whereNull('delivered_confirmed_at')
            ->where(function ($query): void {
                $query->where(function ($query): void {
                    $query->whereNull('shipped_confirmed_at')
                        ->whereNull('shipped_confirmation_sent_at')
                        ->whereNull('shipped_not_yet_at');
                })->orWhere(function ($query): void {
                    $query->whereNotNull('shipped_confirmed_at')
                        ->whereNull('delivered_confirmation_sent_at')
                        ->whereNull('delivered_not_yet_at');
                });
            })
            ->orderBy('id')
            ->lazyById();

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

            $shippingPromptDate = $shipDate->copy()->addDays($followUpDays);
            $milestone = null;

            // Delivery is only inferred from a shipment the subscriber actually confirmed.
            // This avoids asking whether an unshipped device has arrived.
            $deliveryPromptDate = $subscriber->shipped_confirmed_at
                ? $subscriber->shipped_confirmed_at->copy()->startOfDay()->addDays($deliveryDays + $followUpDays)
                : null;

            if ($deliveryPromptDate
                && ! $subscriber->delivered_confirmation_sent_at
                && ! $subscriber->delivered_not_yet_at
                && $today->gte($deliveryPromptDate)) {
                $milestone = 'delivered';
            } elseif (! $subscriber->shipped_confirmed_at
                && ! $subscriber->shipped_confirmation_sent_at
                && ! $subscriber->shipped_not_yet_at
                && $today->gte($shippingPromptDate)) {
                $milestone = 'shipped';
            }

            if (! $milestone) {
                continue;
            }

            Mail::to($subscriber->email)->queue(
                (new DeviceStatusConfirmation($subscriber, $milestone))->onQueue('mail'),
            );
            $queued++;

            if ($queued >= $limit) {
                break;
            }
        }

        $this->info("Queued {$queued} device confirmation request(s).");

        return self::SUCCESS;
    }
}
