<?php

namespace App\Mail;

use App\Services\ScrapeResult;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScrapeAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $alertMessage,
        public readonly ScrapeResult $result,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[ALERT] AYN shipping scrape failed',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.scrape-alert',
            text: 'mail.scrape-alert-text',
            with: [
                'alertMessage' => $this->alertMessage,
                'timestamp' => now()->toIso8601String(),
                'status' => $this->result->status,
                'recordsFound' => $this->result->recordsFound,
                'recordsNew' => $this->result->recordsNew,
                'error' => $this->result->error,
                'durationMs' => $this->result->durationMs,
                'htmlSnippet' => $this->result->htmlSnippet,
            ],
        );
    }
}
