<?php

namespace Tests\Feature;

use App\Mail\VerifySubscription;
use App\Models\ModelVariant;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriberDeliveryTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_verification_delivery_records_sent_time(): void
    {
        $subscriber = $this->subscriber();

        Mail::mailer('array')->to($subscriber->email)->send(new VerifySubscription($subscriber));

        $subscriber->refresh();
        $this->assertNotNull($subscriber->verification_sent_at);
        $this->assertSame('active', $subscriber->delivery_status);
    }

    public function test_pruner_deletes_only_expired_unverified_subscribers(): void
    {
        $expired = $this->subscriber(['verification_sent_at' => now()->subDays(8)]);
        $recent = $this->subscriber(['email' => 'recent@example.com', 'verification_sent_at' => now()->subDays(6)]);
        $verified = $this->subscriber([
            'email' => 'verified@example.com',
            'verification_sent_at' => now()->subDays(8),
            'email_verified_at' => now()->subDays(7),
        ]);

        $this->artisan('subscribers:prune-unverified')->assertSuccessful();

        $this->assertModelMissing($expired);
        $this->assertModelExists($recent);
        $this->assertModelExists($verified);
    }

    private function subscriber(array $attributes = []): Subscriber
    {
        $variant = ModelVariant::firstOrCreate(['slug' => 'test'], [
            'name' => 'Test',
            'display_order' => 1,
            'color_config' => [],
        ]);

        return Subscriber::create(array_merge([
            'email' => 'subscriber@example.com',
            'model_variant_id' => $variant->id,
            'order_prefix' => random_int(1000, 9999),
            'verification_token' => Str::random(64),
            'unsubscribe_token' => Str::random(64),
        ], $attributes));
    }
}
