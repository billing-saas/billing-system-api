@extends('emails.layout')

@section('content')
    <div class="greeting">
        Hello, {{ $invoice->client->name }} 👋
    </div>

    <p class="text">
        You have received a new invoice from <strong>Facturo</strong>.
        Please review the details below and proceed with payment before the due date.
    </p>

    {{-- Invoice Card --}}
    <div class="invoice-card">
        <table>
            <tr>
                <td class="label">Invoice Number</td>
                <td class="value">{{ $invoice->invoice_number }}</td>
            </tr>
            <tr>
                <td class="label">Issue Date</td>
                <td class="value">{{ $invoice->issue_date->format('M d, Y') }}</td>
            </tr>
            <tr>
                <td class="label">Due Date</td>
                <td class="value">{{ $invoice->due_date->format('M d, Y') }}</td>
            </tr>
            <tr>
                <td class="label">Currency</td>
                <td class="value">{{ $invoice->currency }}</td>
            </tr>
            <tr>
                <td class="total-label">Total Due</td>
                <td class="total-value">
                    {{ $invoice->currency }}
                    {{ number_format($invoice->total, 2) }}
                </td>
            </tr>
        </table>
    </div>

    <p class="text">
        Click the button below to view and pay your invoice securely via Stripe.
    </p>

    <div class="btn-wrapper">
        <a href="{{ $invoice->stripe_payment_url }}" class="btn btn-primary">
            Pay Invoice Now
        </a>
    </div>

    <div class="divider"></div>

    <p class="text" style="font-size: 12px; color: #94a3b8;">
        If you have any questions about this invoice, please contact us directly.
        This payment link is secure and powered by Stripe.
    </p>
@endsection