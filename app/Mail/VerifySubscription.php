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
use Symfony\Component\Mime\Email;

class VerifySubscription extends Mailable implements ShouldQueue
{
    use HasBounceReturnPath, HasReliableDelivery, Queueable, SerializesModels;

    public function __construct(
        public readonly Subscriber $subscriber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify your shipping estimate subscription',
            using: array_filter([
                $this->bounceEnvelopeCallback(),
                function (Email $message): void {
                    $message->getHeaders()->addTextHeader(
                        'X-AYN-Verification-Subscriber',
                        (string) $this->subscriber->id,
                    );
                },
            ]),
        );
    }

    public function content(): Content
    {
        $subscriber = $this->subscriber->load('modelVariant');

        return new Content(view: 'mail.verify', text: 'mail.verify-text', with: [
            'verifyUrl' => url("/verify/{$subscriber->verification_token}"),
            'modelName' => $subscriber->modelVariant->name,
            'orderPrefix' => $subscriber->order_prefix,
        ]);
    }
}
