<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Picqer\BolRetailerV10\Model\Order;
use Illuminate\Mail\Mailables\Attachment;

class Invoice extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Order $order,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Factuur',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.Invoice',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $mpdf = new \Mpdf\Mpdf( array(
            'mode'                 => 'utf-8',
            'format'               => 'A4',
            'orientation'          => 'P',
            'margin_left'          => 10,
            'margin_right'         => 10,
            'margin_top'           => 10,
            'margin_bottom'        => 10,
            'margin_header'        => 0,
            'margin_footer'        => 0,
            'setAutoTopMargin'     => 'stretch',
            'setAutoBottomMargin'  => 'stretch',
            'default_font_size'    => 10,
            'default_font'         => 'helvetica',
        ) );

        $html = view('pdf.invoice', [
            'order' => $this->order,
        ])->render();
        $mpdf->WriteHTML($html);
    
        // Output the PDF as a string
        $pdf = $mpdf->Output('', 'S');
    
        // Create the attachment and return it in an array
        return [
            Attachment::fromData(fn() => $pdf, 'Factuur.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
