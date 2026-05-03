@extends('emails.layout')

@section('content')
    <div class="greeting">
        Payment Received! 🎉
    </div>

    <p class="text">
        Hi <strong>{{ $invoice->client->name }}</strong>, we have successfully
        received your payment for invoice
        <strong>{{ $invoice->invoice_number }}</strong>. Thank you!
    </p>

    <div class="alert-box alert-success">
        ✅ Payment of <strong>{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</strong>
        was received on <strong>{{ $invoice->paid_at->format('M d, Y') }}</strong>.
    </div>

    <div class="invoice-card">
        <table>
            <tr>
                <td class="label">Invoice Number</td>
                <td class="value">{{ $invoice->invoice_number }}</td>
            </tr>
            <tr>
                <td class="label">Paid On</td>
                <td class="value" style="color: #15803d;">
                    {{ $invoice->paid_at->format('M d, Y') }}
                </td>
            </tr>
            <tr>
                <td class="total-label">Amount Paid</td>
                <td class="total-value" style="color: #15803d;">
                    {{ $invoice->currency }}
                    {{ number_format($invoice->total, 2) }}
                </td>
            </tr>
        </table>
    </div>

    <p class="text">
        Your invoice PDF is attached to this email for your records.
    </p>

    <div class="divider"></div>

    <p class="text" style="font-size: 12px; color: #94a3b8;">
        Thank you for your prompt payment. We look forward to working with you again.
    </p>
@endsection