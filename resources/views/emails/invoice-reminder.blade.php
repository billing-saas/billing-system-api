@extends('emails.layout')

@section('content')
    <div class="greeting">
        Payment Reminder 🔔
    </div>

    <p class="text">
        Hi <strong>{{ $invoice->client->name }}</strong>, this is a friendly reminder
        that invoice <strong>{{ $invoice->invoice_number }}</strong> is due in
        <strong>{{ now()->diffInDays($invoice->due_date) }} days</strong>.
    </p>

    <div class="alert-box alert-warning">
        ⚠️ Please ensure payment is completed before
        <strong>{{ $invoice->due_date->format('M d, Y') }}</strong>
        to avoid any late fees.
    </div>

    <div class="invoice-card">
        <table>
            <tr>
                <td class="label">Invoice Number</td>
                <td class="value">{{ $invoice->invoice_number }}</td>
            </tr>
            <tr>
                <td class="label">Due Date</td>
                <td class="value">{{ $invoice->due_date->format('M d, Y') }}</td>
            </tr>
            <tr>
                <td class="total-label">Amount Due</td>
                <td class="total-value">
                    {{ $invoice->currency }}
                    {{ number_format($invoice->total, 2) }}
                </td>
            </tr>
        </table>
    </div>

    <div class="btn-wrapper">
        <a href="{{ $invoice->stripe_payment_url }}" class="btn btn-primary">
            Pay Now
        </a>
    </div>
@endsection