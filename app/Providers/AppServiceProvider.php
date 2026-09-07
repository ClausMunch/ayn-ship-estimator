<?php

namespace App\Providers;

use App\Models\EmailLog;
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
                    self::recordEmail($event);

                    return;
                }

                [$subscriberId, $milestone] = explode(':', $confirmation->getBodyAsString(), 2);
                $column = $milestone === 'delivered'
                    ? 'delivered_confirmation_sent_at'
                    : 'shipped_confirmation_sent_at';

                Subscriber::whereKey((int) $subscriberId)->update([$column => now()]);

                self::recordEmail($event);

                return;
            }

            Subscriber::whereKey((int) $header->getBodyAsString())->update([
                'verification_sent_at' => now(),
                'delivery_status' => 'active',
                'delivery_error' => null,
            ]);

            self::recordEmail($event);
        });
    }

    private static function recordEmail(MessageSent $event): void
    {
        $headers = $event->message->getHeaders();
        $verification = $headers->get('X-AYN-Verification-Subscriber');
        $estimate = $headers->get('X-AYN-Estimate-Subscriber');
        $confirmation = $headers->get('X-AYN-Device-Confirmation');

        if (! $verification && ! $estimate && ! $confirmation) {
            return;
        }

        if ($verification) {
            $type = 'verification';
            $subscriberId = (int) $verification->getBodyAsString();
        } elseif ($estimate) {
            $type = 'estimate_changed';
            $subscriberId = (int) $estimate->getBodyAsString();
        } else {
            [$subscriberId, $milestone] = explode(':', $confirmation->getBodyAsString(), 2);
            $subscriberId = (int) $subscriberId;
            $type = "device_{$milestone}";
        }

        $recipients = $event->message->getTo();
        $recipient = ($recipients[0] ?? null)?->getAddress();

        if (! $recipient) {
            return;
        }

        EmailLog::create([
            'subscriber_id' => $subscriberId,
            'recipient' => $recipient,
            'type' => $type,
            'subject' => (string) $event->message->getSubject(),
            'message_id' => $event->message->getMessageId(),
            'sent_at' => now(),
        ]);
    }
}
