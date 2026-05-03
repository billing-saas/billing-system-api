@extends('emails.layout')

@section('content')
    <div class="greeting">
        Overdue Payment Notice ⚠️
    </div>

    <p class="text">
        Hi <strong>{{ $invoice->client->name }}</strong>, invoice
        <strong>{{ $invoice->invoice_number }}</strong> was due on
        <strong>{{ $invoice->due_date->format('M d, Y') }}</strong>
        and payment has not been received.
    </p>

    <div class="alert-box alert-danger">
        🚨 This invoice is now <strong>{{ now()->diffInDays($invoice->due_date) }} days overdue</strong>.
        Please make payment immediately to avoid further action.
    </div>

    <div class="invoice-card">
        <table>
            <tr>
                <td class="label">Invoice Number</td>
                <td class="value">{{ $invoice->invoice_number }}</td>
            </tr>
            <tr>
                <td class="label">Was Due On</td>
                <td class="value" style="color: #dc2626;">
                    {{ $invoice->due_date->format('M d, Y') }}
                </td>
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
        <a href="{{ $invoice->stripe_payment_url }}" class="btn btn-warning">
            Pay Immediately
        </a>
    </div>
@endsection