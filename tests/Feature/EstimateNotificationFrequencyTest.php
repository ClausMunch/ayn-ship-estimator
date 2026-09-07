<?php

namespace Tests\Feature;

use App\Mail\EstimateChanged;
use App\Models\ModelVariant;
use App\Models\ShippingBatch;
use App\Models\Subscriber;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class EstimateNotificationFrequencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimate_mail_is_suppressed_during_cooldown(): void
    {
        Carbon::setTestNow('2026-09-07 12:00:00');
        Mail::fake();
        $subscriber = $this->subscriber();
        $subscriber->update(['estimate_notification_sent_at' => now()->subDays(3)]);

        $this->artisan('notify')->assertSuccessful();

        Mail::assertNothingQueued();
    }

    public function test_estimate_mail_is_not_sent_after_shipping_is_confirmed(): void
    {
        Carbon::setTestNow('2026-09-07 12:00:00');
        Mail::fake();
        $subscriber = $this->subscriber();
        $subscriber->update(['shipped_confirmed_at' => now()]);

        $this->artisan('notify')->assertSuccessful();

        Mail::assertNothingQueued();
    }

    public function test_estimate_mail_is_sent_after_cooldown(): void
    {
        Carbon::setTestNow('2026-09-07 12:00:00');
        Mail::fake();
        $subscriber = $this->subscriber();
        $subscriber->update(['estimate_notification_sent_at' => now()->subDays(8)]);

        $this->artisan('notify')->assertSuccessful();

        Mail::assertQueued(EstimateChanged::class);
        $this->assertSame(now()->toDateTimeString(), $subscriber->refresh()->estimate_notification_sent_at->toDateTimeString());
    }

    private function subscriber(): Subscriber
    {
        $variant = ModelVariant::create([
            'name' => 'Test',
            'slug' => 'test-'.Str::random(6),
            'display_order' => 1,
            'color_config' => [],
        ]);
        ShippingBatch::create([
            'model_variant_id' => $variant->id,
            'ship_date' => '2026-09-10',
            'order_range_start' => 1000,
            'order_range_end' => 2000,
        ]);

        return Subscriber::create([
            'email' => Str::random(8).'@example.com',
            'model_variant_id' => $variant->id,
            'order_prefix' => 1500,
            'last_estimated_date' => '2026-09-01',
            'email_verified_at' => now(),
            'verification_token' => Str::random(64),
            'unsubscribe_token' => Str::random(64),
        ]);
    }
}
