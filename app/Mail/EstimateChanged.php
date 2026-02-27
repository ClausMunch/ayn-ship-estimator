<?php

namespace App\Mail;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EstimateChanged extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Subscriber $subscriber,
        public readonly string $oldDate,
        public readonly string $newDate,
    ) {}

    public function envelope(): Envelope
    {
        $modelName = $this->subscriber->modelVariant->name;

        return new Envelope(
            subject: "Your AYN Thor {$modelName} estimate changed",
        );
    }

    public function content(): Content
    {
        $subscriber = $this->subscriber->load('modelVariant');

        return new Content(
            view: 'mail.estimate-changed',
            text: 'mail.estimate-changed-text',
            with: [
                'modelName' => $subscriber->modelVariant->name,
                'orderPrefix' => $subscriber->order_prefix,
                'oldDate' => $this->oldDate,
                'newDate' => $this->newDate,
                'siteUrl' => config('app.url'),
                'unsubscribeUrl' => url("/unsubscribe/{$subscriber->unsubscribe_token}"),
            ],
        );
    }
}
