<?php

namespace Tests\Feature\Admin;

use App\Mail\VerifySubscription;
use App\Models\ModelVariant;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResendAllVerificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_resend_verification_to_every_unverified_subscriber(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $variant = ModelVariant::create([
            'name' => 'Test Variant',
            'slug' => 'test-variant',
            'display_order' => 1,
            'color_config' => [],
        ]);

        $unverified = collect([
            $this->createSubscriber($variant, 'first@example.com'),
            $this->createSubscriber($variant, 'second@example.com'),
        ]);
        $this->createSubscriber($variant, 'verified@example.com', now());

        $this->actingAs($admin)
            ->post('/admin/subscribers/resend-all-verifications')
            ->assertRedirect();

        Mail::assertQueuedCount(2);

        foreach ($unverified as $subscriber) {
            Mail::assertQueued(
                VerifySubscription::class,
                fn (VerifySubscription $mail): bool => $mail->subscriber->is($subscriber),
            );
        }
    }

    public function test_guest_cannot_resend_all_verifications(): void
    {
        Mail::fake();

        $this->post('/admin/subscribers/resend-all-verifications')
            ->assertRedirect('/admin/login');

        Mail::assertNothingQueued();
    }

    private function createSubscriber(
        ModelVariant $variant,
        string $email,
        mixed $verifiedAt = null,
    ): Subscriber {
        return Subscriber::create([
            'email' => $email,
            'model_variant_id' => $variant->id,
            'order_prefix' => fake()->unique()->numberBetween(1000, 9999),
            'email_verified_at' => $verifiedAt,
            'verification_token' => Str::random(64),
            'unsubscribe_token' => Str::random(64),
        ]);
    }
}
