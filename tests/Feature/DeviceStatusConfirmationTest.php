<?php

namespace Tests\Feature;

use App\Mail\DeviceStatusConfirmation;
use App\Models\ModelVariant;
use App\Models\ShippingBatch;
use App\Models\Subscriber;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeviceStatusConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_subscriber_receives_delivery_confirmation_request(): void
    {
        Carbon::setTestNow('2026-08-10 12:00:00');
        Mail::fake();
        $subscriber = $this->subscriberWithShippingData('2026-07-20');

        $this->artisan('subscribers:request-device-confirmations')->assertSuccessful();

        Mail::assertQueued(DeviceStatusConfirmation::class, function ($mail) use ($subscriber): bool {
            return $mail->subscriber->is($subscriber) && $mail->milestone === 'delivered';
        });
    }

    public function test_signed_delivery_link_records_both_milestones(): void
    {
        $subscriber = $this->subscriberWithShippingData('2026-08-01');
        $url = URL::temporarySignedRoute(
            'device-status.confirm',
            now()->addDay(),
            ['subscriber' => $subscriber->id, 'milestone' => 'delivered'],
        );

        $this->get($url)->assertOk();

        $subscriber->refresh();
        $this->assertNotNull($subscriber->shipped_confirmed_at);
        $this->assertNotNull($subscriber->delivered_confirmed_at);
    }

    public function test_unsigned_confirmation_link_is_rejected(): void
    {
        $subscriber = $this->subscriberWithShippingData('2026-08-01');

        $this->get("/confirm-device-status/{$subscriber->id}/shipped")->assertForbidden();
    }

    private function subscriberWithShippingData(string $shipDate): Subscriber
    {
        $variant = ModelVariant::create([
            'name' => 'Test',
            'slug' => 'test-'.Str::random(6),
            'display_order' => 1,
            'color_config' => [],
        ]);
        ShippingBatch::create([
            'model_variant_id' => $variant->id,
            'ship_date' => $shipDate,
            'order_range_start' => 1000,
            'order_range_end' => 2000,
        ]);

        return Subscriber::create([
            'email' => Str::random(8).'@example.com',
            'model_variant_id' => $variant->id,
            'order_prefix' => 1500,
            'email_verified_at' => now(),
            'verification_token' => Str::random(64),
            'unsubscribe_token' => Str::random(64),
        ]);
    }
}
