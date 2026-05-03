<?php

namespace App\Http\Controllers;

use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        // Vérifier la signature Stripe
        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid signature.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        match ($event->type) {
            'checkout.session.completed'  => $this->handleCheckoutCompleted($event),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event),
            default => Log::info("Stripe webhook event ignored: {$event->type}"),
        };

        return response()->json(['success' => true]);
    }

    private function handleCheckoutCompleted(object $event): void
    {
        $session   = $event->data->object;
        $invoiceId = $session->metadata->invoice_id ?? null;

        if (!$invoiceId) {
            Log::warning('Stripe webhook: invoice_id missing in metadata');
            return;
        }

        try {
            // Récupère la facture et met à jour le payment_intent_id
            $invoice = \App\Models\Invoice::find($invoiceId);

            if ($invoice) {
                $invoice->update([
                    'stripe_payment_intent_id' => $session->payment_intent,
                ]);
            }

            $this->invoiceService->markAsPaidByWebhook((int) $invoiceId);

            Log::info('Invoice marked as paid via Stripe webhook', [
                'invoice_id'        => $invoiceId,
                'session_id'        => $session->id,
                'payment_intent_id' => $session->payment_intent,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error marking invoice as paid', [
                'invoice_id' => $invoiceId,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    private function handlePaymentFailed(object $event): void
    {
        $paymentIntent = $event->data->object;

        Log::warning('Stripe payment failed', [
            'payment_intent_id' => $paymentIntent->id,
            'error'             => $paymentIntent->last_payment_error?->message,
        ]);
    }
}
