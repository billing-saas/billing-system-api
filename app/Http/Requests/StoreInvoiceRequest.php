<?php

namespace App\Http\Requests;

class StoreInvoiceRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id'          => ['required', 'integer', 'exists:clients,id'],
            'issue_date'         => ['required', 'date'],
            'due_date'           => ['required', 'date', 'after_or_equal:issue_date'],
            'tax_rate'           => ['nullable', 'numeric', 'min:0', 'max:100'],
            'currency'           => ['nullable', 'string', 'size:3'],
            'notes'              => ['nullable', 'string'],
            'terms'              => ['nullable', 'string'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
