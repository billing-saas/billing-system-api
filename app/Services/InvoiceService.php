<?php

namespace App\Services;

use App\Helpers\AuthHelper;
use App\Models\Invoice;
use App\Repositories\InvoiceRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class InvoiceService
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private StripeService $stripeService
    ) {}

    public function listInvoices(array $filters = []): LengthAwarePaginator
    {
        return $this->invoiceRepository->getAllByUser(
            AuthHelper::id(),
            $filters
        );
    }

    public function getInvoice(int $id): Invoice
    {
        $invoice = $this->invoiceRepository->findByIdAndUser(
            $id,
            AuthHelper::id()
        );

        if (!$invoice) {
            $this->notFound();
        }

        return $invoice;
    }

    public function createInvoice(array $data): Invoice
    {
        $items    = $data['items'] ?? [];
        $totals   = $this->calculateTotals($items, $data['tax_rate'] ?? 0);

        return $this->invoiceRepository->create([
            'user_id'        => AuthHelper::id(),
            'client_id'      => $data['client_id'],
            'invoice_number' => $this->generateInvoiceNumber(),
            'status'         => 'draft',
            'issue_date'     => $data['issue_date'],
            'due_date'       => $data['due_date'],
            'subtotal'       => $totals['subtotal'],
            'tax_rate'       => $data['tax_rate'] ?? 0,
            'tax_amount'     => $totals['tax_amount'],
            'total'          => $totals['total'],
            'currency'       => $data['currency'] ?? 'USD',
            'notes'          => $data['notes'] ?? null,
            'terms'          => $data['terms'] ?? null,
        ], $this->prepareItems($items));
    }

    public function updateInvoice(int $id, array $data): Invoice
    {
        $invoice = $this->getInvoice($id);

        if (!$invoice->isDraft()) {
            $this->forbidden('Only draft invoices can be edited.');
        }

        $items  = $data['items'] ?? [];
        $totals = $this->calculateTotals($items, $data['tax_rate'] ?? $invoice->tax_rate);

        return $this->invoiceRepository->update($invoice, [
            'client_id'  => $data['client_id']  ?? $invoice->client_id,
            'issue_date' => $data['issue_date']  ?? $invoice->issue_date,
            'due_date'   => $data['due_date']    ?? $invoice->due_date,
            'subtotal'   => $totals['subtotal'],
            'tax_rate'   => $data['tax_rate']    ?? $invoice->tax_rate,
            'tax_amount' => $totals['tax_amount'],
            'total'      => $totals['total'],
            'currency'   => $data['currency']    ?? $invoice->currency,
            'notes'      => $data['notes']       ?? $invoice->notes,
            'terms'      => $data['terms']       ?? $invoice->terms,
        ], $this->prepareItems($items));
    }

    public function sendInvoice(int $id): Invoice
    {
        $invoice = $this->getInvoice($id);

        if (!$invoice->isDraft()) {
            $this->forbidden('Only draft invoices can be sent.');
        }

        $invoice->load(['client', 'items']);

        // Créer la session Stripe
        $session = $this->stripeService->createCheckoutSession($invoice);

        // Stocker le lien de paiement sur la facture
        $invoice->update([
            'stripe_payment_intent_id' => $session->payment_intent,
            'stripe_payment_url'       => $session->url,
        ]);

        return $this->invoiceRepository->updateStatus($invoice, 'sent');
    }

    public function markAsPaid(int $id): Invoice
    {
        $invoice = $this->getInvoice($id);

        if ($invoice->isPaid()) {
            $this->forbidden('Invoice is already paid.');
        }

        $invoice->update(['paid_at' => now()]);

        return $this->invoiceRepository->updateStatus($invoice, 'paid');
    }

    public function markAsPaidByWebhook(int $id): Invoice
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            throw new \Exception("Invoice {$id} not found.");
        }

        if ($invoice->isPaid()) {
            return $invoice;
        }

        $invoice->update(['paid_at' => now()]);

        return $this->invoiceRepository->updateStatus($invoice, 'paid');
    }

    public function deleteInvoice(int $id): void
    {
        $invoice = $this->getInvoice($id);

        if (!$invoice->isDraft()) {
            $this->forbidden('Only draft invoices can be deleted.');
        }

        $this->invoiceRepository->delete($invoice);
    }

    public function checkAndMarkOverdue(): int
    {
        $invoices = $this->invoiceRepository->getOverdueInvoices();
        $count    = 0;

        foreach ($invoices as $invoice) {
            $this->invoiceRepository->updateStatus($invoice, 'overdue');
            $count++;
        }

        return $count;
    }

    private function calculateTotals(array $items, float $taxRate): array
    {
        $subtotal  = collect($items)->sum(
            fn($item) => ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0)
        );
        $taxAmount = $subtotal * ($taxRate / 100);

        return [
            'subtotal'   => round($subtotal,  2),
            'tax_amount' => round($taxAmount, 2),
            'total'      => round($subtotal + $taxAmount, 2),
        ];
    }

    private function prepareItems(array $items): array
    {
        return collect($items)->map(fn($item) => [
            'description' => $item['description'],
            'quantity'    => $item['quantity']   ?? 1,
            'unit_price'  => $item['unit_price'] ?? 0,
            'total'       => ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0),
        ])->toArray();
    }

    private function generateInvoiceNumber(): string
    {
        $year     = now()->format('Y');
        $last     = Invoice::where('invoice_number', 'like', "INV-{$year}-%")
            ->orderByDesc('id')
            ->first();
        $sequence = $last
            ? (int) substr($last->invoice_number, -4) + 1
            : 1;

        return sprintf('INV-%s-%04d', $year, $sequence);
    }

    private function notFound(): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Invoice not found.',
                'data'    => null,
                'errors'  => [],
            ], Response::HTTP_NOT_FOUND)
        );
    }

    private function forbidden(string $message): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $message,
                'data'    => null,
                'errors'  => [],
            ], Response::HTTP_FORBIDDEN)
        );
    }
}
