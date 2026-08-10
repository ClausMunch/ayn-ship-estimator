<?php

namespace App\Mail;

use App\Mail\Concerns\HasBounceReturnPath;
use App\Mail\Concerns\HasReliableDelivery;
use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mime\Email;

class DeviceStatusConfirmation extends Mailable implements ShouldQueue
{
    use HasBounceReturnPath, HasReliableDelivery, Queueable, SerializesModels;

    public function __construct(
        public readonly Subscriber $subscriber,
        public readonly string $milestone,
    ) {}

    public function envelope(): Envelope
    {
        $action = $this->milestone === 'delivered' ? 'arrived' : 'shipped';

        return new Envelope(
            subject: "Has your AYN Thor {$action}?",
            using: array_filter([
                $this->bounceEnvelopeCallback(),
                function (Email $message): void {
                    $message->getHeaders()->addTextHeader(
                        'X-AYN-Device-Confirmation',
                        "{$this->subscriber->id}:{$this->milestone}",
                    );
                },
            ]),
        );
    }

    public function content(): Content
    {
        $subscriber = $this->subscriber->load('modelVariant');
        $confirmationUrl = URL::temporarySignedRoute(
            'device-status.confirm',
            now()->addDays(30),
            ['subscriber' => $subscriber->id, 'milestone' => $this->milestone],
        );

        return new Content(
            view: 'mail.device-status-confirmation',
            text: 'mail.device-status-confirmation-text',
            with: [
                'subscriber' => $subscriber,
                'milestone' => $this->milestone,
                'confirmationUrl' => $confirmationUrl,
            ],
        );
    }
}
