<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\VerifySubscription;
use App\Models\Subscriber;
use App\Services\EstimationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SubscribersController extends Controller
{
    public function index(Request $request, EstimationService $estimator): Response
    {
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');

        if (! in_array($sort, ['email', 'order_prefix', 'email_verified_at', 'verification_sent_at', 'created_at'], true)) {
            $sort = 'created_at';
        }
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $subscribers = Subscriber::with('modelVariant:id,name')
            ->orderBy($sort, $direction)
            ->paginate(25)
            ->withQueryString();

        $timelines = [];
        $deliveryDays = max(0, (int) config('shipping.delivery_days', 14));
        $today = now()->startOfDay();

        $subscribers->through(function (Subscriber $subscriber) use (
            $estimator,
            &$timelines,
            $deliveryDays,
            $today,
        ): Subscriber {
            $variantId = $subscriber->model_variant_id;
            $timelines[$variantId] ??= $estimator->buildTimeline($variantId);
            $shipDate = $estimator->estimateDateFromTimeline(
                $timelines[$variantId],
                $subscriber->order_prefix,
            )?->startOfDay();

            $expectation = ['status' => 'unknown', 'ship_date' => null, 'delivery_date' => null];

            if ($shipDate) {
                $deliveryDate = $shipDate->copy()->addDays($deliveryDays);
                $status = $today->lt($shipDate)
                    ? 'not_due'
                    : ($today->lt($deliveryDate) ? 'should_have_shipped' : 'should_have_delivered');

                $expectation = [
                    'status' => $status,
                    'ship_date' => $shipDate->toDateString(),
                    'delivery_date' => $deliveryDate->toDateString(),
                ];
            }

            $subscriber->setAttribute('shipping_expectation', $expectation);

            return $subscriber;
        });

        return Inertia::render('Admin/Subscribers', [
            'subscribers' => $subscribers,
            'unverifiedCount' => Subscriber::whereNull('email_verified_at')
                ->where('delivery_status', '!=', 'bounced')->count(),
            'bouncedCount' => Subscriber::where('delivery_status', 'bounced')->count(),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function destroy(Subscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        return back();
    }

    public function resendVerification(Subscriber $subscriber): RedirectResponse
    {
        if ($subscriber->isVerified() || $subscriber->delivery_status === 'bounced') {
            throw ValidationException::withMessages([
                'resend' => $subscriber->isVerified()
                    ? 'Subscriber is already verified.'
                    : 'Subscriber is suppressed after a permanent delivery failure.',
            ]);
        }

        Mail::to($subscriber->email)->queue(
            (new VerifySubscription($subscriber))->onQueue('mail'),
        );

        return back();
    }

    public function resendAllVerifications(): RedirectResponse
    {
        $queued = 0;

        Subscriber::whereNull('email_verified_at')
            ->where('delivery_status', '!=', 'bounced')
            ->orderBy('id')
            ->chunkById(100, function ($subscribers) use (&$queued): void {
                foreach ($subscribers as $subscriber) {
                    Mail::to($subscriber->email)
                        ->queue((new VerifySubscription($subscriber))->onQueue('mail'));
                    $queued++;
                }
            });

        return back()->with('success', "Queued {$queued} verification emails.");
    }

    public function destroyBounced(): RedirectResponse
    {
        $deleted = Subscriber::where('delivery_status', 'bounced')->delete();

        return back()->with('success', "Deleted {$deleted} rejected subscribers.");
    }
}
