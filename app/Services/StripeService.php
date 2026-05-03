<?php

namespace App\Services;

use App\Models\Invoice;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createCheckoutSession(Invoice $invoice): Session
    {
        $lineItems = $invoice->items->map(fn($item) => [
            'price_data' => [
                'currency'     => strtolower($invoice->currency),
                'unit_amount'  => intval(round((float) $item->unit_price * 100)),
                'product_data' => [
                    'name' => $item->description,
                ],
            ],
            'quantity' => (int) $item->quantity,
        ])->toArray();

        // Ajoute la taxe comme line item séparé si > 0
        if ($invoice->tax_amount > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency'     => strtolower($invoice->currency),
                    'unit_amount'  => intval(round((float) $invoice->tax_amount * 100)),
                    'product_data' => [
                        'name' => "Tax ({$invoice->tax_rate}%)",
                    ],
                ],
                'quantity' => 1,
            ];
        }

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items'           => $lineItems,
            'mode'                 => 'payment',
            'success_url'          => config('app.frontend_url') . "/payment-success/{$invoice->id}",
            'cancel_url'           => config('app.frontend_url') . "/payment-success/{$invoice->id}?cancelled=true",
            'metadata'             => [
                'invoice_id' => $invoice->id,
                'user_id'    => $invoice->user_id,
            ],
            'customer_email' => $invoice->client->email,
        ]);

        return $session;
    }
}
