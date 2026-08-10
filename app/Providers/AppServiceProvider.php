<?php

namespace App\Providers;

use App\Models\Subscriber;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(MessageSent::class, function (MessageSent $event): void {
            $header = $event->message->getHeaders()->get('X-AYN-Verification-Subscriber');

            if (! $header) {
                $confirmation = $event->message->getHeaders()->get('X-AYN-Device-Confirmation');

                if (! $confirmation) {
                    return;
                }

                [$subscriberId, $milestone] = explode(':', $confirmation->getBodyAsString(), 2);
                $column = $milestone === 'delivered'
                    ? 'delivered_confirmation_sent_at'
                    : 'shipped_confirmation_sent_at';

                Subscriber::whereKey((int) $subscriberId)->update([$column => now()]);

                return;
            }

            Subscriber::whereKey((int) $header->getBodyAsString())->update([
                'verification_sent_at' => now(),
                'delivery_status' => 'active',
                'delivery_error' => null,
            ]);
        });
    }
}
