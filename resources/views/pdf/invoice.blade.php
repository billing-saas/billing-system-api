<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 13px;
            color: #1e293b;
            background: #ffffff;
        }

        /* ── Header ── */
        .header {
            background: #0f172a;
            padding: 45px 50px 35px 50px;
        }

        .header-row {
            width: 100%;
        }

        .header-row table {
            width: 100%;
        }

        .header-row td {
            vertical-align: top;
        }

        .brand {
            font-size: 28px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: -1px;
        }

        .brand-dot {
            color: #3b82f6;
        }

        .brand-sub {
            font-size: 11px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 4px;
        }

        .invoice-number-block {
            text-align: right;
        }

        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: -1px;
            opacity: 0.15;
        }

        .invoice-number {
            font-size: 14px;
            color: #94a3b8;
            margin-top: 4px;
        }

        .invoice-number span {
            color: #ffffff;
            font-weight: bold;
        }

        /* ── Blue accent bar ── */
        .accent-bar {
            height: 4px;
            background: #3b82f6;
            width: 100%;
        }

        /* ── Meta strip ── */
        .meta-strip {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 50px;
        }

        .meta-strip table {
            width: 100%;
        }

        .meta-strip td {
            vertical-align: middle;
        }

        .meta-item {
            display: inline-block;
            margin-right: 40px;
        }

        .meta-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            font-weight: bold;
        }

        .meta-value {
            font-size: 13px;
            color: #0f172a;
            font-weight: bold;
            margin-top: 2px;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-draft   { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }
        .status-sent    { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .status-paid    { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .status-overdue { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        /* ── Body ── */
        .body {
            padding: 40px 50px;
        }

        /* ── Parties ── */
        .parties {
            width: 100%;
            margin-bottom: 40px;
            padding-bottom: 40px;
            border-bottom: 1px solid #f1f5f9;
        }

        .parties table {
            width: 100%;
        }

        .parties td {
            width: 33.33%;
            vertical-align: top;
        }

        .party-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #94a3b8;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .party-name {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .party-detail {
            font-size: 12px;
            color: #64748b;
            line-height: 1.8;
        }

        /* ── Paid Stamp ── */
        .paid-stamp-wrapper {
            text-align: right;
            padding-top: 10px;
        }

        .paid-stamp {
            display: inline-block;
            border: 3px solid #16a34a;
            color: #16a34a;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 4px;
            padding: 8px 20px;
            border-radius: 6px;
            opacity: 0.6;
            transform: rotate(-12deg);
        }

        /* ── Items Table ── */
        .items-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #94a3b8;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table thead tr {
            background: #0f172a;
        }

        .items-table thead th {
            padding: 12px 16px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            font-weight: bold;
        }

        .items-table thead th:first-child {
            border-radius: 6px 0 0 6px;
        }

        .items-table thead th:last-child {
            border-radius: 0 6px 6px 0;
            text-align: right;
        }

        .items-table thead th.text-right {
            text-align: right;
        }

        .items-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
        }

        .items-table tbody tr:last-child {
            border-bottom: 2px solid #e2e8f0;
        }

        .items-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .items-table tbody td {
            padding: 14px 16px;
            font-size: 13px;
            color: #334155;
        }

        .items-table tbody td.text-right {
            text-align: right;
        }

        .item-description {
            font-weight: 600;
            color: #0f172a;
        }

        .item-qty {
            color: #64748b;
        }

        /* ── Totaux ── */
        .totals-wrapper {
            width: 100%;
            margin-bottom: 40px;
        }

        .totals-wrapper table {
            width: 100%;
        }

        .totals-wrapper > table > tr > td {
            vertical-align: top;
        }

        .totals-left {
            width: 52%;
            padding-right: 30px;
        }

        .totals-right {
            width: 48%;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        .totals-table tr {
            border-bottom: 1px solid #f1f5f9;
        }

        .totals-table tr:last-child {
            border-bottom: none;
        }

        .totals-table td {
            padding: 12px 18px;
            font-size: 13px;
        }

        .totals-table .label {
            color: #64748b;
        }

        .totals-table .value {
            text-align: right;
            font-weight: 600;
            color: #0f172a;
        }

        .subtotal-row {
            background: #ffffff;
        }

        .tax-row {
            background: #fafafa;
        }

        .total-row {
            background: #1d4ed8;
        }

        .total-row td {
            padding: 16px 18px !important;
        }

        .total-row .label {
            color: #bfdbfe !important;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .total-row .value {
            color: #ffffff !important;
            font-size: 20px;
            font-weight: bold;
        }

        .paid-row td {
            padding: 10px 18px !important;
            background: #f0fdf4;
        }

        .paid-row .label {
            color: #15803d !important;
            font-size: 12px;
        }

        .paid-row .value {
            color: #15803d !important;
            font-size: 12px;
        }

        /* ── Notes & Terms ── */
        .notes-section {
            background: #f8fafc;
            border-left: 3px solid #3b82f6;
            border-radius: 0 6px 6px 0;
            padding: 16px 20px;
            margin-bottom: 14px;
        }

        .notes-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #3b82f6;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .notes-text {
            font-size: 12px;
            color: #64748b;
            line-height: 1.7;
        }

        /* ── Footer ── */
        .footer {
            background: #0f172a;
            padding: 20px 50px;
        }

        .footer table {
            width: 100%;
        }

        .footer td {
            vertical-align: middle;
        }

        .footer-brand {
            font-size: 13px;
            font-weight: bold;
            color: #ffffff;
        }

        .footer-brand-dot {
            color: #3b82f6;
        }

        .footer-sub {
            font-size: 10px;
            color: #475569;
            margin-top: 2px;
        }

        .footer-right {
            text-align: right;
            font-size: 11px;
            color: #475569;
        }

        .footer-right span {
            color: #94a3b8;
        }

        /* ── Divider ── */
        .divider {
            height: 1px;
            background: #f1f5f9;
            margin: 0 50px 30px 50px;
        }
    </style>
</head>
<body>

    {{-- ── HEADER ── --}}
    <div class="header">
        <div class="header-row">
            <table>
                <tr>
                    <td>
                        <div class="brand">
                            Facturo<span class="brand-dot">.</span>
                        </div>
                        <div class="brand-sub">Billing System</div>
                    </td>
                    <td>
                        <div class="invoice-number-block">
                            <div class="invoice-title">INVOICE</div>
                            <div class="invoice-number">
                                <span>{{ $invoice->invoice_number }}</span>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ── ACCENT BAR ── --}}
    <div class="accent-bar"></div>

    {{-- ── META STRIP ── --}}
    <div class="meta-strip">
        <table>
            <tr>
                <td>
                    <table>
                        <tr>
                            <td style="padding-right: 40px;">
                                <div class="meta-label">Issue Date</div>
                                <div class="meta-value">
                                    {{ $invoice->issue_date->format('M d, Y') }}
                                </div>
                            </td>
                            <td style="padding-right: 40px;">
                                <div class="meta-label">Due Date</div>
                                <div class="meta-value">
                                    {{ $invoice->due_date->format('M d, Y') }}
                                </div>
                            </td>
                            <td style="padding-right: 40px;">
                                <div class="meta-label">Currency</div>
                                <div class="meta-value">{{ $invoice->currency }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="text-align: right;">
                    <span class="status-badge status-{{ $invoice->status }}">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    {{-- ── BODY ── --}}
    <div class="body">

        {{-- Parties --}}
        <div class="parties">
            <table>
                <tr>
                    <td>
                        <div class="party-label">Bill To</div>
                        <div class="party-name">{{ $invoice->client->name }}</div>
                        <div class="party-detail">
                            {{ $invoice->client->email }}<br>
                            @if($invoice->client->phone)
                                {{ $invoice->client->phone }}<br>
                            @endif
                            @if($invoice->client->company_name)
                                {{ $invoice->client->company_name }}<br>
                            @endif
                            @if($invoice->client->address)
                                {{ $invoice->client->address }}<br>
                            @endif
                            @if($invoice->client->city || $invoice->client->country)
                                {{ implode(', ', array_filter([
                                    $invoice->client->city,
                                    $invoice->client->country
                                ])) }}
                            @endif
                            @if($invoice->client->tax_number)
                                <br>TAX: {{ $invoice->client->tax_number }}
                            @endif
                        </div>
                    </td>
                    <td style="width: 33%;">
                    </td>
                    <td style="width: 33%; text-align: right;">
                        @if($invoice->isPaid())
                            <div class="paid-stamp-wrapper">
                                <div class="paid-stamp">Paid</div>
                            </div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        {{-- Items --}}
        <div class="items-section">
            <div class="section-title">Invoice Items</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 48%;">Description</th>
                        <th class="text-right" style="width: 12%;">Qty</th>
                        <th class="text-right" style="width: 20%;">Unit Price</th>
                        <th class="text-right" style="width: 20%;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr>
                            <td>
                                <span class="item-description">
                                    {{ $item->description }}
                                </span>
                            </td>
                            <td class="text-right item-qty">
                                {{ $item->quantity }}
                            </td>
                            <td class="text-right">
                                {{ $invoice->currency }}
                                {{ number_format($item->unit_price, 2) }}
                            </td>
                            <td class="text-right" style="font-weight: 600;">
                                {{ $invoice->currency }}
                                {{ number_format($item->total, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Totaux --}}
        <div class="totals-wrapper">
            <table>
                <tr>
                    <td class="totals-left">
                        @if($invoice->notes)
                            <div class="notes-section">
                                <div class="notes-label">Notes</div>
                                <div class="notes-text">{{ $invoice->notes }}</div>
                            </div>
                        @endif
                        @if($invoice->terms)
                            <div class="notes-section">
                                <div class="notes-label">Terms & Conditions</div>
                                <div class="notes-text">{{ $invoice->terms }}</div>
                            </div>
                        @endif
                    </td>
                    <td class="totals-right">
                        <table class="totals-table">
                            <tr class="subtotal-row">
                                <td class="label">Subtotal</td>
                                <td class="value">
                                    {{ $invoice->currency }}
                                    {{ number_format($invoice->subtotal, 2) }}
                                </td>
                            </tr>
                            <tr class="tax-row">
                                <td class="label">
                                    Tax
                                    @if($invoice->tax_rate > 0)
                                        ({{ $invoice->tax_rate }}%)
                                    @endif
                                </td>
                                <td class="value">
                                    {{ $invoice->currency }}
                                    {{ number_format($invoice->tax_amount, 2) }}
                                </td>
                            </tr>
                            <tr class="total-row">
                                <td class="label">Total Due</td>
                                <td class="value">
                                    {{ $invoice->currency }}
                                    {{ number_format($invoice->total, 2) }}
                                </td>
                            </tr>
                            @if($invoice->paid_at)
                                <tr class="paid-row">
                                    <td class="label">✓ Paid On</td>
                                    <td class="value">
                                        {{ $invoice->paid_at->format('M d, Y') }}
                                    </td>
                                </tr>
                            @endif
                        </table>
                    </td>
                </tr>
            </table>
        </div>

    </div>


</body>
</html>