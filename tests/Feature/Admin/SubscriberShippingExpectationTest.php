<?php

namespace Tests\Feature\Admin;

use App\Models\ModelVariant;
use App\Models\ShippingBatch;
use App\Models\Subscriber;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SubscriberShippingExpectationTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscriber_list_includes_calculated_delivery_expectation(): void
    {
        Carbon::setTestNow('2026-08-10 12:00:00');

        $variant = ModelVariant::create([
            'name' => 'Test',
            'slug' => 'test',
            'display_order' => 1,
            'color_config' => [],
        ]);

        foreach ([['2026-07-21', 1000], ['2026-07-31', 2000]] as [$date, $end]) {
            ShippingBatch::create([
                'model_variant_id' => $variant->id,
                'ship_date' => $date,
                'order_range_start' => $end - 100,
                'order_range_end' => $end,
            ]);
        }

        Subscriber::create([
            'email' => 'subscriber@example.com',
            'model_variant_id' => $variant->id,
            'order_prefix' => 1500,
            'verification_token' => Str::random(64),
            'unsubscribe_token' => Str::random(64),
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/admin/subscribers')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('subscribers.data.0.shipping_expectation.status', 'should_have_delivered')
                ->where('subscribers.data.0.shipping_expectation.ship_date', '2026-07-26')
                ->where('subscribers.data.0.shipping_expectation.delivery_date', '2026-08-09')
            );
    }
}
