<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'invoice_number' => $this->invoice_number,
            'status'         => $this->status,
            'issue_date'     => $this->issue_date->toDateString(),
            'due_date'       => $this->due_date->toDateString(),
            'paid_at'        => $this->paid_at?->toDateTimeString(),
            'subtotal'       => (float) $this->subtotal,
            'tax_rate'       => (float) $this->tax_rate,
            'tax_amount'     => (float) $this->tax_amount,
            'total'          => (float) $this->total,
            'currency'       => $this->currency,
            'notes'          => $this->notes,
            'terms'          => $this->terms,
            'stripe_payment_url'       => $this->stripe_payment_url,
            'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
            'client'         => new ClientResource($this->whenLoaded('client')),
            'items'          => InvoiceItemResource::collection(
                $this->whenLoaded('items')
            ),
            'created_at'     => $this->created_at->toDateTimeString(),
            'updated_at'     => $this->updated_at->toDateTimeString(),
        ];
    }
}
