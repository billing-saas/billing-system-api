<?php
namespace App\Mail;

use App\Models\Invoice;
use App\Services\PdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice    $invoice,
        public PdfService $pdfService,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invoice {$this->invoice->invoice_number} — {$this->invoice->currency} {$this->invoice->total}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-sent',
            with: ['invoice' => $this->invoice],
        );
    }

    public function attachments(): array
    {
        $pdfContent = $this->pdfService->streamInvoicePdf($this->invoice);

        return [
            Attachment::fromData(
                fn () => $pdfContent,
                "{$this->invoice->invoice_number}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}