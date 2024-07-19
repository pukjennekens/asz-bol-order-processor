<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrackingCode extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $trackingCode,
        public string $destination,
        public string $postalCode,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tracking Code',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $postNLTrackingUrl = sprintf('https://postnl.nl/tracktrace/?B=%s&D=%s&L=NL&T=C&P=%s', $this->trackingCode, $this->destination, $this->postalCode);

        return new Content(
            markdown: 'emails.TrackingCode',
            with: [
                'url' => $postNLTrackingUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
