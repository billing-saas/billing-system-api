<?php

namespace App\Http\Requests;

class UpdateInvoiceRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id'          => ['sometimes', 'integer', 'exists:clients,id'],
            'issue_date'         => ['sometimes', 'date'],
            'due_date'           => ['sometimes', 'date', 'after_or_equal:issue_date'],
            'tax_rate'           => ['nullable', 'numeric', 'min:0', 'max:100'],
            'currency'           => ['nullable', 'string', 'size:3'],
            'notes'              => ['nullable', 'string'],
            'terms'              => ['nullable', 'string'],
            'items'              => ['sometimes', 'array', 'min:1'],
            'items.*.description' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity'   => ['required_with:items', 'numeric', 'min:1'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
        ];
    }
}
