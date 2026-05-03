<?php

namespace App\Jobs;

use App\Mail\InvoiceSentMail;
use App\Models\Invoice;
use App\Services\PdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Invoice $invoice) {}

    public function handle(PdfService $pdfService): void
    {
        $this->invoice->load(['client', 'items']);

        Mail::to($this->invoice->client->email)
            ->send(new InvoiceSentMail($this->invoice, $pdfService));
    }
}
